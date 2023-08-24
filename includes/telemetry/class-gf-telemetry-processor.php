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

		$raw_response = null;
		if ( is_array( $batch['data'] ) ) {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing a batch of %d telemetry events.', count( $batch['data'] ) ) );
			$data = array();
			foreach ( $batch['data'] as $item ) {
				$data[] = $item->data;
			}
			$raw_response = GF_Telemetry_Data::send_data( $data );
		} else {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing a batch with snapshot data.' ) );
			// snapshot data is sent to a different endpoint.
			$raw_response = GF_Telemetry_Data::send_data( $batch['data']->data, 'version.php' );
		}

		if ( ! is_array( $batch['data'] ) ) {
			$batch['data'] = array( $batch['data'] );
		}

		foreach ( $batch['data'] as $item ) {
			$classname = get_class( $item );
			if ( method_exists( $classname, 'data_sent' ) ) {
				$classname::data_sent( $raw_response );
			}
		}

		return false;
	}
}
