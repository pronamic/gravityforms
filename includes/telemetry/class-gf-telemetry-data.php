<?php

namespace Gravity_Forms\Gravity_Forms\Telemetry;

use GFCommon;
use GFFormsModel;

/**
 * Class GF_Telemetry_Data
 *
 * Base class for telemetry data.
 *
 * @package Gravity_Forms\Gravity_Forms\Telemetry
 */
abstract class GF_Telemetry_Data {

	/**
	 * @var array $data Data to be sent.
	 */
	public $data = array();

	/**
	 * @var string $key Unique identifier for this data object.
	 */
	public $key = '';

	/**
	 * Get the current telemetry data.
	 *
	 * @since 2.8
	 *
	 * @return array
	 */
	public static function get_data() {
		$existing_data = get_option( 'gf_telemetry_data', [] );

		return $existing_data;
	}

	/**
	 * Save telemetry data.
	 *
	 * @since 2.8
	 *
	 * @param GF_Telemetry_Data $data The data to save.
	 *
	 * @return void
	 */
	public static function save_data( GF_Telemetry_Data $data ) {
		$existing_data = self::get_data();

		if ( ! $existing_data ) {
			$existing_data = array(
				'snapshot' => array(),
				'events'   => array(),
			);
		}

		if ( $data->key == 'snapshot' ) {
			$existing_data[ $data->key ] = $data;
		} else {
			$existing_data['events'][] = $data;
		}

		update_option( 'gf_telemetry_data', $existing_data );
	}

	/**
	 * Take a snapshot of the current site data.
	 *
	 * @since 2.8
	 *
	 * @return void
	 */
	public static function take_snapshot() {
		$snapshot = new GF_Telemetry_Snapshot_Data();
		self::save_data( $snapshot );
	}

	/**
	 * Send data to the telemetry endpoint.
	 *
	 * @since 2.8
	 *
	 * @param array  $data     The data to send.
	 * @param string $endpoint The endpoint to send the data to.
	 *
	 * @return array|WP_Error
	 */
	public static function send_data( $data, $endpoint = 'telemetry' ) {

		$options = array(
			'headers' => array(
				'referrer'     => 'GF_Telemetry',
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
			),
			'method'  => 'POST',
			'timeout' => 15,
			'body'    => $data,
		);

		return GFCommon::post_to_manager( $endpoint, 'nocache=1', $options );
	}
}
