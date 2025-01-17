<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! defined( 'GRAVITY_API_URL' ) ) {
	define( 'GRAVITY_API_URL', 'https://gravityapi.com/wp-json/gravityapi/v1' );
}

if ( ! class_exists( 'Gravity_Api' ) ) {

	/**
	 * Client-side API wrapper for interacting with the Gravity APIs.
	 *
	 * @package    Gravity Forms
	 * @subpackage Gravity_Api
	 * @since      1.9
	 * @access     public
	 */
	class Gravity_Api {

		private static $instance = null;

		private static $raw_response = null;

		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Retrieves site key and site secret key from remote API and stores them as WP options. Returns false if license key is invalid; otherwise, returns true.
		 *
		 * @since  2.3
		 *
		 * @param string $license_key License key to be registered.
		 * @param boolean $is_md5 Specifies if $license_key provided is an MD5 or unhashed license key.
		 *
		 * @return bool|WP_Error
		 */
		public function register_current_site( $license_key, $is_md5 = false ) {

			$body              = array();
			$body['site_name'] = get_bloginfo( 'name' );
			$body['site_url']  = get_bloginfo( 'url' );

			if ( $is_md5 ) {
				$body['license_key_md5'] = $license_key;
			} else {
				$body['license_key'] = $license_key;
			}

			GFCommon::log_debug( __METHOD__ . '(): registering site' );

			$result = $this->request( 'sites', $body, 'POST', array( 'headers' => $this->get_license_auth_header( $license_key ) ) );
			$result = $this->prepare_response_body( $result, true );

			if ( is_wp_error( $result ) ) {
				GFCommon::log_error( __METHOD__ . '(): error registering site. ' . $result->get_error_message() );

				return $result;
			}

			update_option( 'gf_site_key', $result['key'] );
			update_option( 'gf_site_secret', $result['secret'] );

			GFCommon::log_debug( __METHOD__ . '(): site registration successful. Site Key: ' . $result['key'] );

			return true;
		}

		/**
		 * Updates license key for a site that has already been registered.
		 *
		 * @since  2.3
		 * @since  2.5 Returns License Response on success.
		 *
		 * @access public
		 *
		 * @param string $new_license_key_md5 Hash license key to be updated
		 *
		 * @return \Gravity_Forms\Gravity_Forms\License\GF_License_API_Response|WP_Error
		 */
		public function update_current_site( $new_license_key_md5 ) {

			$site_key    = $this->get_site_key();
			$site_secret = $this->get_site_secret();
			if ( empty( $site_key ) || empty( $site_secret ) ) {

				return false;
			}

			$body                    = GFCommon::get_remote_post_params();
			$body['site_name']       = get_bloginfo( 'name' );
			$body['site_url']        = get_bloginfo( 'url' );
			$body['site_key']        = $site_key;
			$body['site_secret']     = $site_secret;
			$body['license_key_md5'] = $new_license_key_md5;

			GFCommon::log_debug( __METHOD__ . '(): refreshing license info' );

			$result = $this->request( 'sites/' . $site_key, $body, 'PUT', array( 'headers' => $this->get_site_auth_header( $site_key, $site_secret ) ) );
			$result = $this->prepare_response_body( $result, true );

			if ( is_wp_error( $result ) ) {

				GFCommon::log_debug( __METHOD__ . '(): error updating site registration. ' . print_r( $result, true ) );

				return $result;

			}

			return $result;
		}

		/***
		 * Removes a license key from a registered site. NOTE: It doesn't actually deregister the site.
		 *
		 * @deprecated Use gapi()->update_current_site('') instead.
		 *
		 * @return bool|WP_Error
		 */
		public function deregister_current_site() {

			$site_key    = $this->get_site_key();
			$site_secret = $this->get_site_secret();

			if ( empty( $site_key ) ) {
				return false;
			}

			GFCommon::log_debug( __METHOD__ . '(): deregistering' );

			$body = array(
				'license_key_md5' => '',
			);

			$result = $this->request( 'sites/' . $site_key, $body, 'PUT', array( 'headers' => $this->get_site_auth_header( $site_key, $site_secret ) ) );
			$result = $this->prepare_response_body( $result, true );

			if ( is_wp_error( $result ) ) {

				GFCommon::log_debug( __METHOD__ . '(): error updating site registration. ' . print_r( $result, true ) );

				return $result;

			}

			return true;
		}

		/**
		 * Check the given license key to get its information from the API.
		 *
		 * @since 2.5
		 *
		 * @param string $key The license key.
		 *
		 * @return array|false|WP_Error
		 */
		public function check_license( $key ) {

			GFCommon::log_debug( __METHOD__ . '(): getting site and license info' );

			$params = array(
				'site_url'     => get_option( 'home' ),
				'is_multisite' => is_multisite(),
			);

			$resource = 'licenses/' . $key . '/check?' . build_query( $params );
			$result   = $this->request( $resource, null );
			$result   = $this->prepare_response_body( $result, true );

			if ( is_wp_error( $result ) ) {

				GFCommon::log_debug( __METHOD__ . '(): error getting site and license information. ' . $result->get_error_message() );

				return $result;

			}

			$response = $result;

			if ( rgar( $result, 'license' ) ) {
				$response = rgar( $result, 'license' );
			}

			// Set the license object to the transient.
			set_transient( 'rg_gforms_license', $response, DAY_IN_SECONDS );

			return $response;
		}

		/**
		 * Get GF core and add-on family information.
		 *
		 * @since 2.5
		 *
		 * @return false|array
		 */
		public function get_plugins_info() {
			$version_info = $this->get_version_info();

			if ( empty( $version_info['offerings'] ) ) {
				return false;
			}

			return $version_info['offerings'];
		}

		/**
		 * Get version information from the Gravity Manager API.
		 *
		 * @since 2.5
		 *
		 * @param false $cache
		 *
		 * @return array
		 */
		private function get_version_info( $cache = false ) {

			$version_info = null;

			if ( $cache ) {
				$cached_info = get_option( 'gform_version_info' );

				// Checking cache expiration
				$cache_duration  = DAY_IN_SECONDS; // 24 hours.
				$cache_timestamp = $cached_info && isset( $cached_info['timestamp'] ) ? $cached_info['timestamp'] : 0;

				// Is cache expired? If not, set $version_info to the cached data.
				if ( $cache_timestamp + $cache_duration >= time() ) {
					$version_info = $cached_info;
				}
			}

			if ( is_wp_error( $version_info ) || isset( $version_info['headers'] ) ) {
				// Legacy ( < 2.1.1.14 ) version info contained the whole raw response.
				$version_info = null;
			}

			// If we reach this point with a $version_info array, it's from cache, and we can return it.
			if ( $version_info ) {
				return $version_info;
			}

			//Getting version number
			$options = array(
				'method'  => 'POST',
				'timeout' => 20,
			);

			$options['headers'] = array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
			);

			$options['body']    = GFCommon::get_remote_post_params();
			$options['timeout'] = 15;

			$nocache = $cache ? '' : 'nocache=1'; //disabling server side caching

			// Store the raw_response for this page load. This will keep us from hitting the api multiple times per pageload.
			if ( is_null( self::$raw_response ) ) {
				self::$raw_response = GFCommon::post_to_manager( 'version.php', $nocache, $options );
			}

			$raw_response = self::$raw_response;;
			$version_info = array(
				'is_valid_key' => '1',
				'version'      => '',
				'url'          => '',
				'is_error'     => '1',
			);

			if ( is_wp_error( $raw_response ) || rgars( $raw_response, 'response/code' ) != 200 ) {
				$version_info['timestamp'] = time();

				return $version_info;
			}

			$decoded = json_decode( $raw_response['body'], true );

			if ( empty( $decoded ) ) {
				$version_info['timestamp'] = time();

				return $version_info;
			}

			$decoded['timestamp'] = time();

			// Caching response.
			update_option( 'gform_version_info', $decoded, false ); //caching version info

			return $decoded;
		}

		/**
		 * Update the usage data (call version.php in Gravity Manager). We will replace it once we have statistics API endpoints.
		 *
		 * @since 2.5
		 */
		public function update_site_data() {

			// Whenever we update the plugins info, we call the versions.php to update usage data.
			$options            = array( 'method' => 'POST' );
			$options['headers'] = array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
				'Referer'      => get_bloginfo( 'url' ),
			);
			$options['body']    = GFCommon::get_remote_post_params();
			// Set the version to 3 which lightens the burden of version.php, it won't return anything to us anymore.
			$options['body']['version'] = '3';
			$options['timeout']         = 15;

			$nocache = 'nocache=1'; //disabling server side caching

			GFCommon::post_to_manager( 'version.php', $nocache, $options );
		}

		public function send_email_to_hubspot( $email ) {
			GFCommon::log_debug( __METHOD__ . '(): Sending installation wizard to hubspot.' );

			$body = array(
				'email' => $email,
			);

			$result = $this->request( 'emails/installation/add-to-list', $body, 'POST', array( 'headers' => $this->get_license_info_header( $site_secret ) ) );
			$result = $this->prepare_response_body( $result, true );

			if ( is_wp_error( $result ) ) {
				GFCommon::log_debug( __METHOD__ . '(): error sending installation wizard to hubspot. ' . print_r( $result, true ) );

				return $result;
			}

			return true;
		}

		// # HELPERS

		/**
		 * @return false|mixed|void
		 */
		public function get_key() {
			return GFCommon::get_key();
		}

		/**
		 * @param $site_key
		 * @param $site_secret
		 *
		 * @return string[]
		 */
		private function get_site_auth_header( $site_key, $site_secret ) {

			$auth = base64_encode( "{$site_key}:{$site_secret}" );

			return array( 'Authorization' => 'GravityAPI ' . $auth );

		}

		/**
		 * @param $site_secret
		 *
		 * @return string[]
		 */
		private function get_license_info_header( $site_secret ) {
			$auth = base64_encode( "gravityforms.com:{$site_secret}" );

			return array( 'Authorization' => 'GravityAPI ' . $auth );
		}

		/**
		 * @param $license_key_md5
		 *
		 * @return string[]
		 */
		private function get_license_auth_header( $license_key_md5 ) {

			$auth = base64_encode( "license:{$license_key_md5}" );

			return array( 'Authorization' => 'GravityAPI ' . $auth );

		}

		/**
		 * Prepare response body.
		 *
		 * @since unknown
		 * @since 2.5     Support a WP_Error being returned.
		 * @since 2.5     Allow results to be returned as array with second param.
		 *
		 * @param WP_Error|WP_REST_Response $raw_response The API response.
		 * @param bool                      $as_array     Whether to return the response as an array or object.
		 *
		 * @return array|object|WP_Error
		 */
		public function prepare_response_body( $raw_response, $as_array = false ) {

			if ( is_wp_error( $raw_response ) ) {
				return $raw_response;
			}

			$response_body    = json_decode( wp_remote_retrieve_body( $raw_response ), $as_array );
			$response_code    = wp_remote_retrieve_response_code( $raw_response );
			$response_message = wp_remote_retrieve_response_message( $raw_response );

			if ( $response_code > 200 ) {

				// If a WP_Error was returned in the body.
				if ( rgar( $response_body, 'code' ) ) {

					// Restore the WP_Error.
					$error = new WP_Error( $response_body['code'], $response_body['message'], $response_body['data'] );
				} else {
					$error_code = $response_code == 429 ? 'http_request_blocked' : 'http_request_failed';
					$error = new WP_Error( $error_code, 'Error from server: ' . $response_message );
				}

				return $error;

			}

			return $response_body;
		}

		/**
		 * Purge the site credentials.
		 *
		 * @since unknown
		 * @since 2.5     Added the deletion of the gf_site_registered option.
		 */
		public function purge_site_credentials() {

			delete_option( 'gf_site_key' );
			delete_option( 'gf_site_secret' );
			delete_option( 'gf_site_registered' );

		}

		/**
		 * Making API requests.
		 *
		 * @since unknown
		 * @since 2.5     Purge the registration data on site if certain errors matched.
		 *
		 * @param string $resource The API route.
		 * @param array $body The request body.
		 * @param string $method The method.
		 * @param array $options The options.
		 *
		 * @return array|WP_Error
		 */
		public function request( $resource, $body, $method = 'POST', $options = array() ) {
			$body['timestamp'] = time();

			// set default options
			$options = wp_parse_args( $options, array(
				'method'    => $method,
				'timeout'   => 10,
				'body'      => in_array( $method, array( 'GET', 'DELETE' ) ) ? null : json_encode( $body ),
				'headers'   => array(),
				'sslverify' => false,
			) );

			// set default header options
			$options['headers'] = wp_parse_args( $options['headers'], array(
				'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ),
				'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
				'Referer'      => get_bloginfo( 'url' ),
			) );

			// WP docs say method should be uppercase
			$options['method'] = strtoupper( $options['method'] );

			$request_url = $this->get_gravity_api_url() . $resource;

			return wp_remote_request( $request_url, $options );
		}

		/**
		 * @return false|mixed|void
		 */
		public function get_site_key() {

			if ( defined( 'GRAVITY_API_SITE_KEY' ) ) {
				return GRAVITY_API_SITE_KEY;
			}

			$site_key = get_option( 'gf_site_key' );
			if ( empty( $site_key ) ) {
				return false;
			}

			return $site_key;

		}

		/**
		 * @return false|mixed|void
		 */
		public function get_site_secret() {
			if ( defined( 'GRAVITY_API_SITE_SECRET' ) ) {
				return GRAVITY_API_SITE_SECRET;
			}
			$site_secret = get_option( 'gf_site_secret' );
			if ( empty( $site_secret ) ) {
				return false;
			}

			return $site_secret;
		}

		/**
		 * @return string
		 */
		public function get_gravity_api_url() {
			return trailingslashit( GRAVITY_API_URL );
		}

		/**
		 * Check if the site has the gf_site_key and gf_site_secret options.
		 *
		 * @since unknown
		 *
		 * @return bool
		 */
		public function is_site_registered() {
			return $this->get_site_key() && $this->get_site_secret();
		}

		/**
		 * Check if the site has the gf_site_key, gf_site_secret and also the gf_site_registered options.
		 *
		 * @since 2.5
		 *
		 * @return bool
		 */
		public function is_legacy_registration() {

			return $this->is_site_registered() && ! get_option( 'gf_site_registered' );

		}

	}

	function gapi() {
		return Gravity_Api::get_instance();
	}

	gapi();

}
