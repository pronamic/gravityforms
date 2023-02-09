<?php

namespace Gravity_Forms\Gravity_Forms\Query;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

use Gravity_Forms\Gravity_Forms\Query\JSON_Handlers\GF_String_JSON_Handler;
use Gravity_Forms\Gravity_Forms\Query\JSON_Handlers\GF_Query_JSON_Handler;

/**
 * Class GF_Query_Service_Provider
 *
 * Service provider for the Query Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Query;
 */
class GF_Query_Service_Provider extends GF_Service_Provider {
	const JSON_STRING_HANDLER = 'json_string_handler';
	const JSON_QUERY_HANDLER  = 'json_query_handler';

	/**
	 * Register services to the container.
	 *
	 * @since 
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-gf-json-handler.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-gf-query-json-handler.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-gf-string-json-handler.php' );

		$container->add( self::JSON_STRING_HANDLER, function() {
			return new GF_String_JSON_Handler();
		});

		$container->add( self::JSON_QUERY_HANDLER, function() {
			return new GF_Query_JSON_Handler();
		});
	}
	
}

