<?php

namespace Gravity_Forms\Gravity_Forms\Telemetry;
use GFCommon;
use function tad\WPBrowser\debug;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/libraries/gf-background-process.php';
}

/**
 * GF_Telemetry_Processor Class.
 */
class GF_Telemetry_Processor extends \GF_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'gf_telemetry_processor';

	/**
	 * Task
	 *
	 * Process a single batch of telemetry data.
	 *
	 * @param mixed $batch
	 * @return mixed
	 */
	protected function task( $batch ) {

		if ( ! is_array( $batch ) ) {
			$batch = array( $batch );
		}

		$raw_response = null;
		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing a batch of %d telemetry data.', count( $batch ) ) );
		$data = array();
		foreach ( $batch as $item ) {

			if ( ! is_object( $item ) || ! property_exists( $item, 'data' ) ) {
				continue;
			}

			// attach type & tag, required by the telemetry API.
			$item->data['type'] = $item->key === 'snapshot' ? 'snapshot' : 'event';
			$item->data['tag']  = $item->key;
			$data[]             = $item->data;
		}
		$raw_response = GF_Telemetry_Data::send_data( $data );

		if ( is_wp_error( $raw_response ) ) {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Failed sending telemetry data. Code: %s; Message: %s.', $raw_response->get_error_code(), $raw_response->get_error_message() ) );
			return false;
		}

		foreach ( $batch as $item ) {
			if ( ! is_object( $item ) ) {
				\GFCommon::log_debug( __METHOD__ . sprintf( '(): Telemetry data is missing. Aborting running data_sent method on this entry.' ) );
				continue;
			}
			$classname = get_class( $item );
			if ( method_exists( $classname, 'data_sent' ) ) {
				$classname::data_sent( $raw_response );
			}
		}

		return false;
	}
}
