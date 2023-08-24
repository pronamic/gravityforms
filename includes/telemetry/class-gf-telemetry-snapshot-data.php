<?php

namespace Gravity_Forms\Gravity_Forms\Telemetry;

use GFCommon;
use GFFormsModel;

class GF_Telemetry_Snapshot_Data extends GF_Telemetry_Data {

	/**
	 * @var string $key Identifier for this data object.
	 */
	public $key = 'snapshot';

	public function __construct() {
		/**
		 * Array of callback functions returning an array of data to be included in the telemetry snapshot.
		 *
		 * @since 2.8
		 */
		$callbacks = array(
			array( $this, 'get_site_basic_info' ),
		);

		// Merges the default callbacks with any additional callbacks added via the gform_telemetry_snapshot_data filter. Default callbacks are added last so they can't be overridden.
		$callbacks = array_merge( $this->get_callbacks(), $callbacks );

		foreach ( $callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				$this->data = array_merge( $this->data, call_user_func( $callback ) );
			}
		}
	}

	/**
	 * Get additional callbacks that return data to be included in the telemetry snapshot.
	 *
	 * @since 2.8
	 *
	 * @return array
	 */
	public function get_callbacks() {
		/**
		 * Filters the non-default data to be included in the telemetry snapshot.
		 *
		 * @since 2.8
		 *
		 * @param array $new_callbacks An array of callbacks returning an array of data to be included in the telemetry snapshot. Default empty array.
		 */
		return apply_filters( 'gform_telemetry_snapshot_data', array() );
	}

	/**
	 * Get basic site info for telemetry.
	 *
	 * @since 2.8
	 *
	 * @return array
	 */
	public function get_site_basic_info() {
		return GFCommon::get_remote_post_params();
	}

	/**
	 * Stores the response from the version.php endpoint, to be used by the license service.
	 *
	 * @since 2.8
	 *
	 * @param array $response Raw response from the API endpoint.
	 */
	public static function data_sent( $response ) {
		$version_info = array(
			'is_valid_key' => '1',
			'version'      => '',
			'url'          => '',
			'is_error'     => '1',
		);

		if ( is_wp_error( $response ) || rgars( $response, 'response/code' ) != 200 ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded = json_decode( $response['body'], true );

		if ( empty( $decoded ) ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded['timestamp'] = time();

		update_option( 'gform_version_info', $decoded, false );

		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Version info cached.' ) );
	}
}
