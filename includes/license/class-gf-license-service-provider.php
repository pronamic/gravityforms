<?php

namespace Gravity_Forms\Gravity_Forms\License;

use Gravity_Forms\Gravity_Forms\External_API\GF_API_Debug;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Transients\GF_WP_Transient_Strategy;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;

/**
 * Class GF_License_Service_Provider
 *
 * Service provider for the License Service.
 *
 * @package Gravity_Forms\Gravity_Forms\License
 */
class GF_License_Service_Provider extends GF_Service_Provider {

	const GF_API                = 'gf_api';
	const RESPONSE_FACTORY      = 'gf_license_response_factory';
	const LICENSE_API_CONNECTOR = 'license_api_connector';

	/**
	 * Register the various classes for this service.
	 *
	 * @since 2.5.11
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		\GFForms::include_gravity_api();

		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-license-api-connector.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-license-api-connector.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-license-statuses.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-license-api-response.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-license-api-response-factory.php' );

		$container->add( self::GF_API, function () {
			return \Gravity_Api::get_instance();
		} );

		$container->add( self::RESPONSE_FACTORY, function () use ( $container ) {
			return new GF_License_API_Response_Factory( $container->get( GF_Util_Service_Provider::TRANSIENT_STRAT ) );
		} );

		$container->add( self::LICENSE_API_CONNECTOR, function () use ( $container ) {
			return new GF_License_API_Connector( $container->get( self::GF_API ), $container->get( GF_Util_Service_Provider::GF_CACHE ), $container->get( self::RESPONSE_FACTORY ) );
		} );
	}
}