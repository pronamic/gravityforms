<?php

namespace Gravity_Forms\Gravity_Forms\Post_Custom_Field_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

defined( 'ABSPATH' ) || die();

/**
 * Class GF_Post_Custom_Field_Select_Service_Provider
 *
 * Service provider for the Post Custom Field Select functionality
 *
 * @since 2.9.20
 */
class GF_Post_Custom_Field_Select_Service_Provider extends GF_Service_Provider {

	const POST_CUSTOM_SELECT        = 'post_custom_select';
	const POST_CUSTOM_SELECT_CONFIG = 'post_custom_select_config';

	/**
	 * Register services to the container
	 *
	 * @since 2.9.20
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-post-custom-field-select.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-post-custom-field-select-config.php' );

		$container->add( self::POST_CUSTOM_SELECT, function() {
			return new GF_Post_Custom_Field_Select();
		} );

		$container->add( self::POST_CUSTOM_SELECT_CONFIG, function() use ( $container ) {
			$data_parser = $container->get( GF_Config_Service_Provider::DATA_PARSER );
			return new GF_Post_Custom_Field_Select_Config( $data_parser );
		} );
	}

	/**
	 * Initialize any actions or hooks
	 *
	 * @since 2.9.20
	 *
	 * @param GF_Service_Container $container
	 */
	public function init( GF_Service_Container $container ) {
		$container->get( self::POST_CUSTOM_SELECT )->init();

		if ( \GFForms::get_page() === 'form_editor' ) {
			$config_collection = $container->get( GF_Config_Service_Provider::CONFIG_COLLECTION );
			$config_collection->add_config( $container->get( self::POST_CUSTOM_SELECT_CONFIG ) );
		}
	}
}
