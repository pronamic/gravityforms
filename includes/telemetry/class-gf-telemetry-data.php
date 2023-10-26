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
	 * @var string
	 */
	const TELEMETRY_ENDPOINT = 'https://in.gravity.io/';

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

		update_option( 'gf_telemetry_data', $existing_data, false );
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
	 * @param array  $entries The data to send.
	 *
	 * @return array|WP_Error
	 */
	public static function send_data( $entries ) {
		// allow overriding the endpoint to use the local or staging environment for testing purposes.
		$endpoint = defined( 'GF_TELEMETRY_ENDPOINT' ) ? GF_TELEMETRY_ENDPOINT : self::TELEMETRY_ENDPOINT;
		$site_url = get_site_url();
		$data = array(
			'license_key_md5' => md5( get_option( 'rg_gforms_key', '' ) ),
			'site_url'        => $site_url,
			'product'         => 'gravityforms',
			'tag'             => 'system_report',
			'data'            => $entries,
		);

		return wp_remote_post(
			$endpoint . 'api/telemetry_data_bulk',
			array(
				'headers'     => array(
					'Content-Type'  => 'application/json',
					'Authorization' => sha1( $site_url ),
				),
				'method'      => 'POST',
				'data_format' => 'body',
				'body'        => json_encode( $data ),
			)
		);
	}
}
