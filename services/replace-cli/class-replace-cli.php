<?php

/**
 * Command line replacements.
 *
 * You'll want to modify, delete, or keep this file around
 * for easy duplication.
 *
 * @since 2.0.0
 * @package  __YourCompanyName__\__YourPluginName__
 */

namespace __YourCompanyName__\__YourPluginName__\Service;

use function \__YourCompanyName__\__YourPluginName__\app;

class Replace_CLI {
	private $line_removals = [
		'		// An example service so you can see how things work, below cli command should remove this.',
		'		$this->example_service = new Service\Example_Service();',
	];

	private $file_removals = [];

	private $cli_args;

	private $extensions = [
		'.php',
		'.md',
		'.js',
	];

	private $ignore_dirs = [
		'vendor',
	];

	public function hooks() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}
	}

	public function __construct() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		$this->cli_args = new \WebDevStudios\CLI_Args\CLI_Args();
	}

	public function run() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command( 'kickstart', [ $this, 'kickstart' ], [
			'shortdesc' => __( 'Will help you convert the installed wpkickstart plugin into a new plugin and perform all of the search/replacements.', 'wds-migrate-subsite' ),
			'synopsis'  => [
				[
					'type'        => 'assoc',
					'name'        => 'since',
					'optional'    => true,
					'description' => __( 'What @since will be set to, defaults to `1.0.0`.', 'wpkickstart' ),
					'default'     => '1.0.0',
				],
			],
		] );
	}

	public function kickstart( array $args, array $assoc_args ) {
		$this->cli_args->set_args( $args, $assoc_args ); // Ensure we have an easy way to get arguments.

		$this->remove_lines();
	}

	private function remove_lines() {
		$recursive_dir = new \RecursiveDirectoryIterator( dirname( app()->plugin_file ) );

		foreach ( new \RecursiveIteratorIterator( $recursive_dir ) as $file => $file_obj ) {
			if ( is_dir( $file ) ) {
				continue;
			}

			if ( ! app()->is_our_file( $file ) ) {
				continue;
			}

			if ( ! $this->has_valid_extension( $file ) ) {
				continue;
			}

			if ( $this->ignore( $file ) ) {
				continue;
			}

			$code = file_get_contents( $file );

			foreach ( $this->line_removals as $remove ) {
				$code = str_replace( "{$remove}\n", '', $code );

				error_log( print_r( (object) array(
					'line' => __LINE__,
					'file' => __FILE__,
					'dump' => array(
						$remove => $file,
					),
				), true ) );
			}

			file_put_contents( $file, $code );
		}
	}

	private function ignore( string $file ) {
		foreach ( $this->ignore_dirs as $ignore_dir ) {
			if ( stristr( $file, trailingslashit( $ignore_dir ) ) ) {
				return true;
			}
		}

		return false;
	}

	private function has_valid_extension( string $file ) {
		foreach ( $this->extensions as $extension ) {
			if ( stristr( $file, $extension ) ) {
				return true;
			}
		}

		return false;
	}
}
