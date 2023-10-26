<?php

namespace Gravity_Forms\Gravity_Forms\Telemetry;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use GFCommon;
use GFFormsModel;


/**
 * Class GF_Telemetry_Service_Provider
 *
 * Service provider for the telemetry Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Telemetry;
 */
class GF_Telemetry_Service_Provider extends GF_Service_Provider {
	const TELEMETRY_SCHEDULED_TASK = 'gravityforms_telemetry_dispatcher';
	const BATCH_SIZE = 10;

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once GF_PLUGIN_DIR_PATH . 'includes/telemetry/class-gf-telemetry-data.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/telemetry/class-gf-telemetry-snapshot-data.php';
	}

	/**
	 * Initialize the scheduler.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		add_action( self::TELEMETRY_SCHEDULED_TASK, array( $this, 'enqueue_telemetry_batches' ) );
	}

	/**
	 * Enqueue batches of telemetry events to be processed in the background.
	 *
	 * @since
	 *
	 * @return void
	 */
	public function enqueue_telemetry_batches() {
		// Only run once a week.
		$last_run     = get_option( 'gf_last_telemetry_run', 0 );
		$current_time = time();
		if ( $current_time - $last_run < 60 * 60 * 24 * 7 ) {
			return;
		}
		update_option( 'gf_last_telemetry_run', $current_time );

		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Enqueuing telemetry batches' ) );
		GF_Telemetry_Data::take_snapshot();

		$processor = $this->container->get( \Gravity_Forms\Gravity_Forms\Async\GF_Background_Process_Service_Provider::TELEMETRY );

		$full_telemetry_data = GF_Telemetry_Data::get_data();

		$snapshot = $full_telemetry_data['snapshot'];

		// Enqueue the snapshot first, alone, to be sent to its own endpoint.
		$processor->push_to_queue( $snapshot );
		$processor->save()->dispatch();

		$full_telemetry_data = array_chunk( $full_telemetry_data['events'], self::BATCH_SIZE, true );
		foreach ( $full_telemetry_data as $batch ) {
			$processor->push_to_queue( $batch );
			$processor->save()->dispatch();
		}

		// Clear saved telemetry data except the snapshot.
		update_option(
			'gf_telemetry_data',
			array(
				'snapshot' => $snapshot,
				'events'   => array(),
			),
			false
		);
	}
}

