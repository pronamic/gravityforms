<?php

namespace Gravity_Forms\Gravity_Forms\Author_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

defined( 'ABSPATH' ) || die();

/**
 * Class GF_Author_Select_Service_Provider
 *
 * Service provider for the Author Select functionality
 *
 * @since 2.9.20
 */
class GF_Author_Select_Service_Provider extends GF_Service_Provider {

	const AUTHOR_SELECT        = 'author_select';
	const AUTHOR_SELECT_CONFIG = 'author_select_config';

	/**
	 * Register services to the container
	 *
	 * @since 2.9.20
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-author-select.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-author-select-config.php' );

		$container->add( self::AUTHOR_SELECT, function() {
			return new GF_Author_Select();
		} );

		$container->add( self::AUTHOR_SELECT_CONFIG, function() use ( $container ) {
			$data_parser = $container->get( GF_Config_Service_Provider::DATA_PARSER );
			return new GF_Author_Select_Config( $data_parser );
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
		$container->get( self::AUTHOR_SELECT )->init();

		if ( \GFForms::get_page() === 'form_editor' ) {
			$config_collection = $container->get( GF_Config_Service_Provider::CONFIG_COLLECTION );
			$config_collection->add_config( $container->get( self::AUTHOR_SELECT_CONFIG ) );
		}
	}
}