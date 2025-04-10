<?php

namespace Gravity_Forms\Gravity_Forms\Form_Switcher;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Form_Switcher\Config\GF_Form_Switcher_Config;
use Gravity_Forms\Gravity_Forms\Form_Switcher\Endpoints\GF_Form_Switcher_Endpoint_Get_Forms;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Form_Switcher_Service_Provider
 *
 * Service provider for the Form_Switcher Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Form_Switcher;
 */
class GF_Form_Switcher_Service_Provider extends GF_Service_Provider {

	// Configs
	const FORM_SWITCHER_CONFIG = 'form_switcher_config';

	const ENDPOINT_GET_FORMS = 'endpoint_get_forms';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.9.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::FORM_SWITCHER_CONFIG => GF_Form_Switcher_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.9.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-form-switcher-config.php' );
		$this->add_configs( $container );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-form-switcher-endpoint-get-forms.php' );
		$this->add_endpoints( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.9.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		add_action( 'wp_ajax_' . GF_Form_Switcher_Endpoint_Get_Forms::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_GET_FORMS )->handle();
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.9.6
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

	/**
	 * For each endpoint defined in $endpoints, instantiate and add to container.
	 *
	 * @since 2.9.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( GF_Service_Container $container ) {
		$container->add( self::ENDPOINT_GET_FORMS, function () {
			return new GF_Form_Switcher_Endpoint_Get_Forms();
		} );
	}

}
