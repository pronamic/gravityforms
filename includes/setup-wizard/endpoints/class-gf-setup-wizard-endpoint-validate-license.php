<?php

namespace Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints;

use Gravity_Forms\Gravity_Forms\License\GF_License_API_Connector;

/**
 * AJAX Endpoint for validating a license key.
 *
 * @since   2.7
 *
 * @package Gravity_Forms\Gravity_Forms\Setup_wizard\Endpoints
 */
class GF_Setup_Wizard_Endpoint_Validate_License {

	// Strings
	const ACTION_NAME = 'gf_setup_wizard_validate_license';

	// Parameters
	const PARAM_LICENSE = 'license';

	/**
	 * @var GF_License_API_Connector
	 */
	private $license_api;

	public function __construct( GF_License_API_Connector $license_api ) {
		$this->license_api = $license_api;
	}

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		if ( ! $this->current_user_can_update_settings() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to update settings.', 'gravityforms' ) ), 403 );
		}

		$license = rgpost( self::PARAM_LICENSE );

		// Check if $license has not alphanumeric values to prevent malformed requests to the API.
		if ( ! ctype_alnum( $license ) ) {
			return wp_send_json_error( __( 'The license is invalid.', 'gravityforms' ) );
		}

		/**
		 * @var \Gravity_Forms\Gravity_Forms\License\GF_License_API_Response $info
		 */
		$info     = $this->license_api->check_license( $license, false );
		$is_valid = $info->can_be_used();

		if ( ! $is_valid ) {
			return wp_send_json_error( $info->get_error_message() );
		}

		wp_send_json_success( $license );
	}

	/**
	 * Determines if the current user can update setup wizard settings.
	 *
	 * @since 3.0.3
	 *
	 * @return bool
	 */
	protected function current_user_can_update_settings() {
		return \GFCommon::current_user_can_any( 'gravityforms_edit_settings' );
	}
}
