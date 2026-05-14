<?php

namespace Gravity_Forms\Gravity_Forms\Webapi\User_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

defined( 'ABSPATH' ) || die();

class GF_User_Select_Service_Provider extends GF_Service_Provider {

	const USER_SELECT        = 'webapi_user_select';
	const USER_SELECT_CONFIG = 'webapi_user_select_config';

	/**
	 * Register services to the container
	 *
	 * @since 2.10.2
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-gf-user-select-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-gf-user-select-config.php';

		$container->add(
			self::USER_SELECT,
			function () {
				return new GF_User_Select_Handler();
			}
		);

		$container->add(
			self::USER_SELECT_CONFIG,
			function () use ( $container ) {
				$data_parser = $container->get( GF_Config_Service_Provider::DATA_PARSER );

				return new GF_User_Select_Config( $data_parser );
			}
		);
	}

	/**
	 * Initialize any actions or hooks
	 *
	 * @since 2.10.2
	 *
	 * @param GF_Service_Container $container
	 */
	public function init( GF_Service_Container $container ) {
		$container->get( self::USER_SELECT )->init();

		if ( \GFForms::get_page() === 'settings' && rgget( 'subview' ) === 'gravityformswebapi' ) {
			$config_collection = $container->get( GF_Config_Service_Provider::CONFIG_COLLECTION );
			$config_collection->add_config( $container->get( self::USER_SELECT_CONFIG ) );
		}
	}
}
