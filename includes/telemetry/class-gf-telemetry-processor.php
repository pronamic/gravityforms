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

		if ( ! isset( $batch['data'] ) ) {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Batch data is missing. Aborting sending telemetry data.' ) );
			return false;
		}

		$raw_response = null;
		if ( is_array( $batch['data'] ) ) {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing a batch of %d telemetry events.', count( $batch['data'] ) ) );
			$data = array();
			foreach ( $batch['data'] as $item ) {

				if ( ! is_object( $item ) || ! property_exists( $item, 'data' ) ) {
					continue;
				}

				$data[] = $item->data;
			}
			$raw_response = GF_Telemetry_Data::send_data( $data );
		} else {
			\GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing a batch with snapshot data.' ) );

			if ( ! is_object( $batch['data'] ) || ! property_exists( $batch['data'], 'data' ) ) {
				\GFCommon::log_debug( __METHOD__ . sprintf( '(): Snapshot data is missing. Aborting sending telemetry data.' ) );
				return false;
			}

			// snapshot data is sent to a different endpoint.
			$raw_response = GF_Telemetry_Data::send_data( $batch['data']->data, 'version.php' );
		}

		if ( ! is_array( $batch['data'] ) ) {
			$batch['data'] = array( $batch['data'] );
		}

		foreach ( $batch['data'] as $item ) {
			if ( ! is_object( $item ) ) {
				\GFCommon::log_debug( __METHOD__ . sprintf( '(): Snapshot data is missing. Aborting running data_sent method on this entry.' ) );
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
