<?php

namespace Gravity_Forms\Gravity_Forms\Config;

use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Admin;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Block_Editor;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Global;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_I18n;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Legacy_Check;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Legacy_Check_Multi;
use Gravity_Forms\Gravity_Forms\Config\Items\GF_Config_Multifile;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Config_Service_Provider
 *
 * Service provider for the Config Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Config
 */
class GF_Config_Service_Provider extends GF_Service_Provider {

	// Organizational services
	const CONFIG_COLLECTION = 'config_collection';
	const DATA_PARSER       = 'data_parser';

	// Config services
	const I18N_CONFIG         = 'i18n_config';
	const ADMIN_CONFIG        = 'admin_config';
	const LEGACY_CONFIG       = 'legacy_config';
	const LEGACY_MULTI_CONFIG = 'legacy_multi_config';
	const MULTIFILE_CONFIG    = 'multifile_config';
	const GLOBAL_CONFIG       = 'global_config';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::I18N_CONFIG         => GF_Config_I18n::class,
		self::ADMIN_CONFIG        => GF_Config_Admin::class,
		self::LEGACY_CONFIG       => GF_Config_Legacy_Check::class,
		self::LEGACY_MULTI_CONFIG => GF_Config_Legacy_Check_Multi::class,
		self::MULTIFILE_CONFIG    => GF_Config_Multifile::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {

		// Include required files.
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-config-collection.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-config-data-parser.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-app-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'items/class-gf-config-global.php' );

		// Add to container
		$container->add( self::CONFIG_COLLECTION, function () {
			return new GF_Config_Collection();
		} );

		$container->add( self::DATA_PARSER, function () {
			return new GF_Config_Data_Parser();
		} );

		$container->add( self::GLOBAL_CONFIG, function () {
			return new GF_Config_Global();
		} );

		// Add configs to container.
		$this->register_config_items( $container );
		$this->register_configs_to_collection( $container );
	}

	/**
	 * Whether the config has been localized.
	 *
	 * @since 2.9.0
	 *
	 * @var bool
	 */
	private static $is_localized = false;

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {

		// Need to pass $this to callbacks; save as variable.
		$self = $this;

		add_action( 'wp_enqueue_scripts', function () use ( $container ) {
			// Only localize during wp_enqueue_scripts if none of the other more specific events have been fired.
			if ( ! self::$is_localized ) {
				$container->get( self::CONFIG_COLLECTION )->handle();
			}
		}, 9999 );

		add_action( 'admin_enqueue_scripts', function () use ( $container ) {
			// Only localize during admin_enqueue_scripts if none of the other more specific events have been fired.
			if ( ! self::$is_localized ) {
				$container->get( self::CONFIG_COLLECTION )->handle();
			}
		}, 9999 );

		add_action( 'gform_output_config', function ( $form_ids = null ) use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle( true, $form_ids );
			self::$is_localized = true;
		} );

		add_action( 'gform_post_enqueue_scripts', function ( $found_forms, $found_blocks, $post ) use ( $container ) {
			$form_ids = array_column( $found_forms, 'formId' );
			$container->get( self::CONFIG_COLLECTION )->handle( true, array( 'form_ids' => $form_ids ) );
			self::$is_localized = true;
		}, 10, 3);

		add_action( 'gform_preview_init', function ( $form_id ) use ( $container ) {
			$form_ids = array( $form_id );
			$container->get( self::CONFIG_COLLECTION )->handle( true, array( 'form_ids' => $form_ids ) );
			self::$is_localized = true;
		}, 10, 2);

		add_action('wp_ajax_gform_get_config', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle_ajax();
		});

		add_action('wp_ajax_nopriv_gform_get_config', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle_ajax();
		});

		add_action( 'rest_api_init', function () use ( $container, $self ) {
			register_rest_route( 'gravityforms/v2', '/tests/mock-data', array(
				'methods'             => 'GET',
				'callback'            => array( $self, 'config_mocks_endpoint' ),
				'permission_callback' => function () {
					return true;
				},
			) );
		} );

		// Add global config data to admin and theme.
		add_filter( 'gform_localized_script_data_gform_admin_config', function ( $data ) use ( $self ) {
			return $self->add_global_config_data( $data );
		} );

		add_filter( 'gform_localized_script_data_gform_theme_config', function ( $data ) use ( $self ) {
			return $self->add_global_config_data( $data );
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function register_config_items( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-gf-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-gf-config-admin.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-gf-config-legacy-check.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-gf-config-legacy-check-multi.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-gf-config-multifile.php' );

		$parser = $container->get( self::DATA_PARSER );

		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $class, $parser ) {
				return new $class( $parser );
			} );
		}
	}

	/**
	 * Register each config defined in $configs to the GF_Config_Collection.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function register_configs_to_collection( GF_Service_Container $container ) {
		$collection = $container->get( self::CONFIG_COLLECTION );

		foreach ( $this->configs as $name => $config ) {
			$config_class = $container->get( $name );
			$collection->add_config( $config_class );
		}
	}

	/**
	 * Callback for the Config Mocks REST endpoint.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public function config_mocks_endpoint() {
		define( 'GFORMS_DOING_MOCK', true );
		$container = \GFForms::get_service_container();
		$data      = $container->get( self::CONFIG_COLLECTION )->handle( false );

		return $data;
	}

	/**
	 * Add global data to both admin and theme configs so that it is available everywhere
	 * within the system.
	 *
	 * @since 2.7
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function add_global_config_data( $data ) {
		$container = \GFForms::get_service_container();
		$global    = $container->get( self::GLOBAL_CONFIG )->data();

		return array_merge( $data, $global );
	}
}
