<?php

namespace Gravity_Forms\Gravity_Forms\Environment_Config;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;

/**
 * Class GF_Environment_Config_Service_Provider
 *
 * Service provider for the Environment_Config Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Environment_Config;
 */
class GF_Environment_Config_Service_Provider extends GF_Service_Provider {

	const GF_ENVIRONMENT_CONFIG_HANDLER = 'gf_environment_config_handler';

	/**
	 * Register services to the container.
	 *
	 * @since 2.7
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function register( GF_Service_Container $container ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-gf-environment-config-handler.php';

		$container->add(
			self::GF_ENVIRONMENT_CONFIG_HANDLER,
			function () use ( $container ) {
				return new GF_Environment_Config_Handler( $container->get( GF_Util_Service_Provider::GF_CACHE ) );
			}
		);
	}

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 2.7
	 *
	 * @param GF_Service_Container $container Service Container.
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {

		$handler = $container->get( self::GF_ENVIRONMENT_CONFIG_HANDLER );

		// Gets environment license key.
		add_filter( 'pre_option_rg_gforms_key', array( $handler, 'maybe_override_rg_gforms_key' ) );

		// Maybe bypass installation wizard.
		add_filter( 'pre_option_gform_pending_installation', array( $handler, 'maybe_override_gform_pending_installation' ) );

		// Maybe hides license key setting and license key details.
		add_filter( 'gform_plugin_settings_fields', array( $handler, 'maybe_hide_setting' ) );

		// Maybe hide plugin auto update messages.
		add_filter( 'init', array( $handler, 'maybe_hide_plugin_page_message' ), 20 );
		add_filter( 'gform_updates_list', array( $handler, 'maybe_hide_update_page_message' ), 20 );
	}
}
