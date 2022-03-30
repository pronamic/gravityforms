<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Embed_Form\Config\GF_Embed_Config;
use Gravity_Forms\Gravity_Forms\Embed_Form\Config\GF_Embed_Config_I18N;
use Gravity_Forms\Gravity_Forms\Embed_Form\Config\GF_Embed_Endpoints_Config;
use Gravity_Forms\Gravity_Forms\Embed_Form\Dom\GF_Embed_Button;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Create_With_Block;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Get_Posts;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Embed_Service_Provider
 *
 * Service provider for the Embed Form Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Embed_Form;
 */
class GF_Embed_Service_Provider extends GF_Service_Provider {

		// Configs
		const EMBED_CONFIG           = 'embed_config';
		const EMBED_CONFIG_I18N      = 'embed_config_i18n';
		const EMBED_CONFIG_ENDPOINTS = 'embed_config_endpoints';

		// Endpoints
		const ENDPOINT_GET_POSTS         = 'endpoint_get_posts';
		const ENDPOINT_CREATE_WITH_BLOCK = 'endpoint_create_with_block';

	// DOM
	const DOM_EMBED_BUTTON = 'dom_embed_button';

	// Strings
	const ADD_BLOCK_PARAM = 'gfAddBlock';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::EMBED_CONFIG           => GF_Embed_Config::class,
		self::EMBED_CONFIG_I18N      => GF_Embed_Config_I18N::class,
		self::EMBED_CONFIG_ENDPOINTS => GF_Embed_Endpoints_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-embed-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-embed-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-embed-endpoints-config.php' );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-embed-endpoint-get-posts.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-embed-endpoint-create-with-block.php' );

		// Dom
		require_once( plugin_dir_path( __FILE__ ) . '/dom/class-gf-embed-button.php' );

		$this->add_configs( $container );
		$this->add_endpoints( $container );
		$this->dom( $container );
	}

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
		add_action( 'wp_ajax_' . GF_Embed_Endpoint_Get_Posts::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_GET_POSTS )->handle();
		} );

		add_action( 'wp_ajax_' . GF_Embed_Endpoint_Create_With_Block::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_CREATE_WITH_BLOCK )->handle();
		} );

		add_action( 'gform_before_toolbar_buttons', function () use ( $container ) {
			$container->get( self::DOM_EMBED_BUTTON )->output_button();
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
	private function add_configs( GF_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

	/**
	 * Register AJAX endpoints for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( GF_Service_Container $container ) {
		$container->add( self::ENDPOINT_GET_POSTS, function () use ( $container ) {
			return new GF_Embed_Endpoint_Get_Posts();
		} );

		$container->add( self::ENDPOINT_CREATE_WITH_BLOCK, function () use ( $container ) {
			return new GF_Embed_Endpoint_Create_With_Block();
		} );
	}

	/**
	 * Register DOM-related services.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function dom( GF_Service_Container $container ) {
		$container->add( self::DOM_EMBED_BUTTON, function() {
			return new GF_Embed_Button();
		});
	}

}
