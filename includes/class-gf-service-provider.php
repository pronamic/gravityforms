<?php

namespace Gravity_Forms\Gravity_Forms;

use Gravity_Forms\Gravity_Forms\Config\GF_App_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;

/**
 * Class GF_Service_Provider
 *
 * An abstraction which provides a contract for defining Service Providers. Service Providers facilitate
 * organizing Services into discreet modules, as opposed to having to register each service in a single location.
 *
 * @since 2.5
 *
 * @package Gravity_Forms\Gravity_Forms
 */
abstract class GF_Service_Provider {

	/**
	 * @var GF_Service_Container $container
	 */
	protected $container;

	public function set_container( GF_Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register new services to the Service Container.
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	abstract public function register( GF_Service_Container $container );

	/**
	 * Noop by default - used to initialize hooks and filters for the given module.
	 */
	public function init( GF_Service_Container $container ) {}

	//----------------------------------------
	//---------- App Registration ------------
	//----------------------------------------

	/**
	 * Register a JS app with the given arguments.
	 *
	 * @since 2.7.1
	 *
	 * @param array $args
	 */
	public function register_app( $args ) {
		$config = new GF_App_Config( $this->container->get( GF_Config_Service_Provider::DATA_PARSER ) );
		$config->set_data( $args );

		$this->container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $config );

		$should_display = is_callable( $args['enqueue'] ) ? call_user_func( $args['enqueue'] ) : $args['enqueue'];

		if ( ! $should_display ) {
			return;
		}

		if ( ! empty( $args['css'] ) ) {
			$this->enqueue_app_css( $args );
		}

		if ( ! empty( $args['root_element'] ) ) {
			$this->add_root_element( $args['root_element'] );
		}
	}

	/**
	 * Enqueue the CSS assets for the app.
	 *
	 * @since 2.7.1
	 *
	 * @param $args
	 */
	protected function enqueue_app_css( $args ) {
		$css_asset = $args['css'];

		add_action( 'wp_enqueue_scripts', function () use ( $css_asset ) {
			call_user_func_array( 'wp_enqueue_style', $css_asset );
		} );

		add_action( 'admin_enqueue_scripts', function () use ( $css_asset ) {
			call_user_func_array( 'wp_enqueue_style', $css_asset );
		} );
	}

	/**
	 * Add the root element to the footer output for bootstrapping.
	 *
	 * @since 2.7.1
	 *
	 * @param string $root
	 */
	protected function add_root_element( $root ) {
		add_action( 'admin_footer', function() use ( $root ) {
			echo '<div data-js="' . $root . '"></div>';
		}, 10, 0 );
	}

}
