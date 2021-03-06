<?php
/**
 * Application.
 *
 * @since 2.0.0
 * @package  aubreypwd\wpkickstart
 */

namespace aubreypwd\wpkickstart;

use Exception;

/**
 * Application Loader.
 *
 * Everything starts here. If you create a new service,
 * attach it to this class using attach_services() method below
 * and you can call it with app().
 *
 * @since 1.0.0
 */
class App {

	/**
	 * Plugin basename.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $basename = '';

	/**
	 * URL of plugin directory.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $path = '';

	/**
	 * Is WP_DEBUG set?
	 *
	 * @since  1.1.0
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 *
	 * @var boolean
	 */
	public $wp_debug = false;

	/**
	 * The plugin file.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 *
	 * @var string
	 */
	public $plugin_file = '';

	/**
	 * The plugin headers.
	 *
	 * @since  1.1.0
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 *
	 * @var array
	 */
	public $plugin_headers = [];

	/**
	 * Construct.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.0.0
	 *
	 * @param string $plugin_file The plugin file, usually __FILE__ of the base plugin.
	 *
	 * @throws Exception If $plugin_file parameter is invalid (prevents plugin from loading).
	 */
	public function __construct( $plugin_file ) {

		// Check input validity.
		if ( empty( $plugin_file ) || ! stream_resolve_include_path( $plugin_file ) ) {

			// Translators: Displays a message if a plugin file is not passed.
			throw new Exception( sprintf( esc_html__( 'Invalid plugin file %1$s supplied to %2$s', 'company-slug-project-slug' ), $plugin_file, __METHOD__ ) );
		}

		// Plugin setup.
		$this->plugin_file = $plugin_file;
		$this->basename    = plugin_basename( $plugin_file );
		$this->url         = plugin_dir_url( $plugin_file );
		$this->path        = plugin_dir_path( $plugin_file );
		$this->wp_debug    = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// Plugin information.
		$this->plugin_headers = get_file_data( $plugin_file, array(
			'Plugin Name' => 'Plugin Name',
			'Description' => 'Description',
			'Version'     => 'Version',
			'Author'      => 'Author',
			'Author URI'  => 'Author URI',
			'Text Domain' => 'Text Domain',
			'Network'     => 'Network',
			'License'     => 'License',
			'License URI' => 'License URI',
		), 'plugin' );

		// Load language files.
		load_plugin_textdomain( 'company-slug-project-slug', false, basename( dirname( $plugin_file ) ) . '/languages' );

		$this->composer_autoload();
		$this->auto_loader();
	}

	/**
	 * Load any composer dependancies.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	private function composer_autoload() {
		$plugin_dir = untrailingslashit( dirname( $this->plugin_file ) );

		$autoload = "{$plugin_dir}/vendor/autoload.php";

		if ( file_exists( $autoload ) ) {
			require_once $autoload;
		}
	}

	/**
	 * Register the autoloader.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 */
	private function auto_loader() {

		// Register our autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Require classes.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.0.0
	 *
	 * @param string $class_name Fully qualified name of class to try and load.
	 */
	public function autoload( $class_name ) {

		// Autoload files from parts.
		$this->autoload_from_parts( explode( '\\', $class_name ) );
	}

	/**
	 * Autoload files from self::autoload() parts.
	 *
	 * Note, if you pass any class in here it will look for it in:
	 *
	 * - /app/
	 * - /components/
	 * - /services/
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.1.0
	 *
	 * @param  array $parts  The parts from self::autoload().
	 * @return void          Early bail once we load the thing.
	 */
	private function autoload_from_parts( array $parts ) {

		// app/.
		if ( $this->is_our_file( $this->autoload_app_file( $parts ) ) ) {
			require_once $this->autoload_app_file( $parts );
			return;
		}

		if ( $this->is_our_file( $this->autoload_component_file( $parts ) ) ) {
			require_once $this->autoload_component_file( $parts );
			return;
		}

		// service/.
		if ( $this->is_our_file( $this->autoload_service_file( $parts ) ) ) {
			require_once $this->autoload_service_file( $parts );
			return;
		}

		// Try and find a file in all the directories (recursive), maybe you're using some new file that you aren't even attaching to App.
		if ( $this->is_our_file( $this->autoload_recursive_file( $parts ) ) ) {
			require_once $this->autoload_recursive_file( $parts );
			return;
		}
	}

	/**
	 * Is a file in our plugin?
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  mixed $file  The file (should be string, but can also be file handler).
	 * @return boolean      True if it is and exists.
	 */
	public function is_our_file( $file ) {
		if ( ! is_string( $file ) ) {
			return false;
		}

		return stristr( $file, dirname( $this->plugin_file ) ) && stream_resolve_include_path( $file );
	}

	/**
	 * Autoload a service e.g. service/class-service.php.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that service class file.
	 */
	public function autoload_recursive_file( array $parts ) {
		$class = end( $parts );

		// Where would it be?
		$file  = $this->autoload_class_file( $parts );
		$class = strtolower( str_replace( '_', '-', $class ) );

		$dirs = [

			// Search these directories in the structure.
			$this->autoload_dir( 'app' ),
			$this->autoload_dir( 'components' ),
			$this->autoload_dir( 'services' ),
		];

		foreach ( $dirs as $dir ) {

			$recursive_dir = new \RecursiveDirectoryIterator( $dir );

			foreach ( new \RecursiveIteratorIterator( $recursive_dir ) as $recursive_file => $file_obj ) {
				if ( ! stristr( $recursive_file, '.php' ) ) {
					continue;
				}

				if ( basename( $recursive_file ) !== $file ) {
					continue;
				}

				return $recursive_file;
			}
		}
	}

	/**
	 * Autoload a service e.g. service/class-service.php.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that service class file.
	 */
	public function autoload_service_file( array $parts ) {
		$dirs = [
			'services',
			'features', // This is here for backwards compatibility.
		];

		$class = end( $parts );

		foreach ( $dirs as $dir ) {

			// Where would it be?
			$file = $this->autoload_class_file( $parts );
			$dir  = $this->autoload_dir( trailingslashit( $dir ) . strtolower( str_replace( '_', '-', $class ) ) );
			$path = "{$dir}{$file}";

			if ( ! file_exists( $path ) ) {
				continue; // Try again in another directory.
			}

			// Pass back that path.
			return $path;
		}
	}

	/**
	 * Get a file for including from app/.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that file.
	 */
	private function autoload_app_file( array $parts ) {
		return $this->autoload_dir( 'app' ) . $this->autoload_class_file( $parts );
	}

	/**
	 * Get a file for including from components/.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that file.
	 */
	private function autoload_component_file( array $parts ) {
		$class = end( $parts );

		// Where would it be?
		$file = $this->autoload_class_file( $parts );
		$dir  = $this->autoload_dir( 'components/' . strtolower( str_replace( '_', '-', $class ) ) );

		// Pass back that path.
		return "{$dir}{$file}";
	}

	/**
	 * Get a directory for autoload.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.1.0
	 *
	 * @param  string $dir What dir, e.g. app.
	 * @return string      The path to that directory.
	 */
	private function autoload_dir( $dir ) {
		return trailingslashit( $this->path ) . trailingslashit( $dir );
	}

	/**
	 * Generate a class filename to autoload.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  array $parts  The parts from self::autoload().
	 * @return string        The class filename.
	 */
	private function autoload_class_file( array $parts ) {
		return 'class-' . strtolower( str_replace( '_', '-', end( $parts ) ) ) . '.php';
	}

	/**
	 * Get the plugin version.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.1.0
	 *
	 * @return string The version of this plugin.
	 */
	public function version() {
		return $this->header( 'Version' );
	}

	/**
	 * Get a header.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.1.0
	 *
	 * @param  string $header The header you want, e.g. Version, Author, etc.
	 * @return string         The value of the header.
	 */
	public function header( $header ) {
		return isset( $this->plugin_headers[ $header ] )
			? trim( (string) $this->plugin_headers[ $header ] )
			: '';
	}

	/**
	 * Attach items to our app.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.0.0
	 */
	public function attach() {
		$this->attach_services();
	}

	/**
	 * Load and attach app services to the app class.
	 *
	 * To add a new service go add a new class to e.g. `services/my-service/class-my-service.php`,
	 * then add it below like:
	 *
	 *     $this->my_service = new My_Service();
	 *
	 * The app will autoload it, run hooks and run methods automatically.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	public function attach_services() {

		// Attach your things, e.g.:
		// $this->example_service = new Example_Service();

		// Adds wp kickstart build for replacements to make this framework into your own plugin.
		$this->build_cli = new \aubreypwd\wpkickstart\Build_CLI();

		// Adds wp kickstart release so we can distribute a zip file for use in installation.
		$this->release_cli = new \aubreypwd\wpkickstart\Release_CLI();
	}

	/**
	 * Fire hooks!
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.0.0
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0           Calls automatically now.
	 */
	public function hooks() {
		$this->auto_call_hooks(); // If you want to run your own hook methods, just strip this.
	}

	/**
	 * Autoload hooks method.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	private function auto_call_hooks() {
		$this->autocall( 'hooks' );
	}

	/**
	 * Run the app.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	public function run() {
		$this->auto_call_run();
	}

	/**
	 * Automatically call run methods.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	private function auto_call_run() {
		$this->autocall( 'run' );
	}

	/**
	 * Call a property on attached objects.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 *
	 * @param  string $call The call.
	 */
	private function autocall( $call ) {
		foreach ( get_object_vars( $this ) as $prop ) {
			if ( is_object( $prop ) ) {
				if ( method_exists( $prop, $call ) ) {
					$prop->$call();
				}
			}
		}
	}

	/**
	 * This plugin's url.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  1.0.0
	 *
	 * @param  string $path (Optional) appended path.
	 * @return string       URL and path.
	 */
	public function url( $path ) {
		return is_string( $path ) && ! empty( $path ) ?
			trailingslashit( $this->url ) . $path :
			trailingslashit( $this->url );
	}

	/**
	 * Re-attribute user content to site author.
	 *
	 * @author Aubrey Portwood <code@aubreypwd.com>
	 * @since  2.0.0
	 */
	public function deactivate_plugin() {
		foreach ( get_object_vars( $this ) as $prop ) {
			if ( is_object( $prop ) ) {
				if ( method_exists( $prop, 'deactivate_plugin' ) ) {
					$prop->deactivate_plugin();
				}
			}
		}
	}
}
