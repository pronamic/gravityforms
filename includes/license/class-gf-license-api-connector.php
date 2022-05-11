<?php

namespace Gravity_Forms\Gravity_Forms\License;

use GFCommon;
use WP_Error;
use Gravity_Forms\Gravity_Forms\External_API\GF_API_Connector;
use Gravity_Forms\Gravity_Forms\External_API\GF_API_Response;
use Gravity_Forms\Gravity_Forms\External_API\GF_API_Response_Factory;

/**
 * Class GF_License_API_Connector
 *
 * Connector providing methods to communicate with the License API.
 *
 * @since 2.5.11
 *
 * @package Gravity_Forms\Gravity_Forms\License
 */
class GF_License_API_Connector extends GF_API_Connector {

	/**
	 * @var \Gravity_Api $strategy
	 */
	protected $strategy;

	/**
	 * @var \GFCache $cache
	 */
	protected $cache;

	/**
	 * @var GF_API_Response_Factory $response_factory
	 */
	protected $response_factory;

	public function __construct( $strategy, $cache, GF_API_Response_Factory $response_factory ) {
		$this->response_factory = $response_factory;

		parent::__construct( $strategy, $cache );
	}

	/**
	 * Check if cache debug is enabled.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function is_debug() {
		return defined( 'GF_CACHE_DEBUG' ) && GF_CACHE_DEBUG;
	}

	/**
	 * If the site was registered with the legacy process.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function is_legacy_registration() {

		return $this->strategy->is_legacy_registration();

	}

	/**
	 * Clear the cache for a given key.
	 *
	 * @since 2.5.11
	 *
	 * @param string $key
	 */
	public function clear_cache_for_key( $key ) {
		$this->cache->delete( 'rg_gforms_license_info_' . $key );
	}

	/**
	 * Get the license info.
	 *
	 * @since 2.5.11
	 *
	 * @param string $key
	 * @param bool   $cache
	 *
	 * @return GF_API_Response
	 */
	public function check_license( $key = false, $cache = true ) {
		$license_info      = false;
		$key               = $key ? trim( $key ) : $this->strategy->get_key();
		$license_info_data = $this->cache->get( 'rg_gforms_license_info_' . $key );

		if ( $this->is_debug() ) {
			$cache = false;
		}

		if ( $license_info_data && $cache ) {
			$license_info = GFCommon::safe_unserialize( $license_info_data, GF_API_Response::class );
			if ( $license_info ) {
				return $license_info;
			} else {
				$this->clear_cache_for_key( $key );
			}
		}

		$license_info = $this->response_factory->create(
			$this->strategy->check_license( $key ),
			false
		);

		if ( $license_info->can_be_used() ) {
			$this->cache->set( 'rg_gforms_license_info_' . $key, serialize( $license_info ), true, DAY_IN_SECONDS );
		}

		return $license_info;
	}

	/**
	 * Check if the saved license key is valid.
	 *
	 * @since 2.5.11
	 *
	 * @return true|WP_Error
	 */
	public function is_valid_license() {
		$license_info = $this->check_license();

		return $license_info->is_valid();
	}

	/**
	 * Registers a site to the specified key, or if $new_key is blank, unlinks a key from an existing site.
	 * Requires that the $new_key is saved in options before calling this function
	 *
	 * @since 2.5.11 Implement the license enforcement process.
	 *
	 * @param string $new_key Unhashed Gravity Forms license key.
	 *
	 * @return GF_License_API_Response
	 */
	public function update_site_registration( $new_key, $is_md5 = false ) {
		// Get new license key information.
		$version_info = GFCommon::get_version_info( false );

		if ( $version_info['is_valid_key'] ) {

			$data = $this->strategy->check_license( $new_key );

			$result = $this->response_factory->create( $data );
		} else {

			// Invalid key, do not change site registration.
			$error = new WP_Error( GF_License_Statuses::INVALID_LICENSE_KEY, GF_License_Statuses::get_message_for_code( GF_License_Statuses::INVALID_LICENSE_KEY ) );
			GFCommon::log_error( 'Invalid license. Site cannot be registered' );

			$result = $this->response_factory->create( $error );
		}

		if ( ! $result->can_be_used() ) {
			GFCommon::log_error( 'Failed to update site registration with Gravity Manager. ' . print_r( $result->get_error_message(), true ) );
		}

		return $result;
	}

	/**
	 * Purge site credentials if the license info contains certain errors.
	 *
	 * @since 2.5.11
	 *
	 * @return void
	 */
	public function maybe_purge_site_credentials() {

		// Check if the license info contains the revoke site error.
		$license_info = $this->check_license();

		$errors = array(
			'gravityapi_site_revoked',
			'gravityapi_fail_authentication',
			'gravityapi_site_url_changed',
		);

		if ( is_wp_error( $license_info ) && in_array( $license_info->get_error_code(), $errors, true ) ) {

			GFCommon::log_debug( __METHOD__ . '(): purging the site credentials because of the following license error: ' . $license_info->get_error_message() );

			// Purge site data to ensure we can get a fresh start.
			$this->strategy->purge_site_credentials();
		}

	}

	/**
	 * Retrieve a list of plugins from the API.
	 *
	 * @since 2.5.11
	 *
	 * @param bool $cache Whether to respect the cached data.
	 *
	 * @return mixed
	 */
	public function get_plugins( $cache = true ) {
		$plugins = $this->cache->get( 'rg_gforms_plugins', $found_in_cache );

		if ( $this->is_debug() ) {
			$cache = false;
		}

		if ( $found_in_cache && $cache ) {
			return $plugins;
		}

		$plugins = $this->strategy->get_plugins_info();

		$this->cache->set( 'rg_gforms_plugins', $plugins, true, DAY_IN_SECONDS );

		return $plugins;
	}
}
