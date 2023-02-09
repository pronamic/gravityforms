<?php

namespace Gravity_Forms\Gravity_Forms\Setup_Wizard;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Embed_Form\Config\GF_Setup_Wizard_Endpoints_Config;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Config\GF_Setup_Wizard_Config;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Config\GF_Setup_Wizard_Config_I18N;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Save_Prefs;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Validate_License;
use Gravity_Forms\Gravity_Forms\License\GF_License_Service_Provider;

/**
 * Class GF_Setup_Wizard_Service_Provider
 *
 * Service provider for the Setup_Wizard Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Setup_Wizard;
 */
class GF_Setup_Wizard_Service_Provider extends GF_Service_Provider {

	// Configs
	const SETUP_WIZARD_CONFIG           = 'setup_wizard_config';
	const SETUP_WIZARD_ENDPOINTS_CONFIG = 'setup_wizard_endpoints_config';
	const SETUP_WIZARD_CONFIG_I18N      = 'setup_wizard_config_i18n';

	// Endpoints
	const SAVE_PREFS_ENDPOINT       = 'setup_wizard_save_prefs_endpoint';
	const VALIDATE_LICENSE_ENDPOINT = 'setup_wizard_validate_license_endpoint';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::SETUP_WIZARD_CONFIG           => GF_Setup_Wizard_Config::class,
		self::SETUP_WIZARD_ENDPOINTS_CONFIG => GF_Setup_Wizard_Endpoints_Config::class,
		self::SETUP_WIZARD_CONFIG_I18N      => GF_Setup_Wizard_Config_I18N::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-setup-wizard-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-setup-wizard-endpoints-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-setup-wizard-config-i18n.php' );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-setup-wizard-endpoint-save-prefs.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-setup-wizard-endpoint-validate-license.php' );

		$container->add( self::SAVE_PREFS_ENDPOINT, function () {
			$api = new \Gravity_Api();
			return new GF_Setup_Wizard_Endpoint_Save_Prefs( $api );
		} );

		$container->add( self::VALIDATE_LICENSE_ENDPOINT, function () use ( $container ) {
			return new GF_Setup_Wizard_Endpoint_Validate_License( $container->get( GF_License_Service_Provider::LICENSE_API_CONNECTOR ) );
		} );

		$this->add_configs( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		add_action( 'wp_ajax_' . GF_Setup_Wizard_Endpoint_Save_Prefs::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SAVE_PREFS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . GF_Setup_Wizard_Endpoint_Validate_License::ACTION_NAME, function () use ( $container ) {
			$container->get( self::VALIDATE_LICENSE_ENDPOINT )->handle();
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( GF_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

}

