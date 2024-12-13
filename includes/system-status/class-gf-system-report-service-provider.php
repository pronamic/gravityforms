<?php

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class System_Report_Service_Provider
 *
 * Service provider for the system report.
 *
 * @package Gravity_Forms\Gravity_Forms\System_Report;
 */
class GF_System_Report_Service_Provider extends GF_Service_Provider {
	const SYSTEM_REPORT = 'system_report';

	/**
	 * Register services to the container.
	 *
	 * @since 2.7.1
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/class-gf-system-report.php' );

		$this->system_report( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.7.1
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {

		add_action( 'admin_init', function () use ( $container ) {
			if ( GFForms::get_page_query_arg() == 'gf_system_status' ) {
				$container->get( self::SYSTEM_REPORT )->remove_emoji_script();
			}
		} );

	}

	/**
	 * Register System Report services.
	 *
	 * @since 2.7.1
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function system_report( GF_Service_Container $container ) {

		$container->add( self::SYSTEM_REPORT, function () use ( $container ) {
			return new GF_System_Report();
		} );
	}

}
