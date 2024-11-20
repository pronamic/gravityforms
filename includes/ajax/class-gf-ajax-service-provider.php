<?php
/**
 * Service Provider for AJAX Service
 *
 * @package Gravity_Forms\Gravity_Forms\Ajax
 */

namespace Gravity_Forms\Gravity_Forms\Ajax;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Ajax\Config\GF_Ajax_Config;

/**
 * Class GF_Ajax_Service_Provider
 *
 * Service provider for the Ajax Service.
 */
class GF_Ajax_Service_Provider extends GF_Service_Provider {

	const GF_AJAX_HANDLER = 'gf_ajax_handler';
	const GF_AJAX_CONFIG  = 'gf_ajax_config';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param GF_Service_Container $container Container singleton object.
	 */
	public function register( GF_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-ajax-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'config/class-gf-ajax-config.php';

		// Registering handler
		$container->add(
			self::GF_AJAX_HANDLER,
			function () {
				return new GF_Ajax_Handler();
			}
		);

		// Registering config
		$container->add(
			self::GF_AJAX_CONFIG,
			function () use ( $container ) {
				return new GF_Ajax_Config( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			}
		);
		$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( self::GF_AJAX_CONFIG ) );

	}

	/**
	 * Initializes service.
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function init( GF_Service_Container $container ) {
		parent::init( $container );

		$ajax_handler = $container->get( self::GF_AJAX_HANDLER );

		// Register nonce lifespan hook.
		add_filter( 'nonce_life', array( $ajax_handler, 'nonce_life' ), 10, 2 );

		// Register AJAX validation.
		add_action( 'wp_ajax_gform_validate_form', array( $ajax_handler, 'validate_form' ) );
		add_action( 'wp_ajax_nopriv_gform_validate_form', array( $ajax_handler, 'validate_form' ) );

		// Register AJAX submission.
		add_action( 'wp_ajax_gform_submit_form', array( $ajax_handler, 'submit_form' ) );
		add_action( 'wp_ajax_nopriv_gform_submit_form', array( $ajax_handler, 'submit_form' ) );
	}
}
