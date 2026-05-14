<?php

namespace Gravity_Forms\Gravity_Forms\Webapi\User_Select;

defined( 'ABSPATH' ) || die();

use GFCommon;

/**
 * Class GF_User_Select_Handler
 *
 * Handles AJAX requests for the user select dropdown in the REST API settings page
 *
 * @since 2.10.2
 */
class GF_User_Select_Handler {

	/**
	 * Initialize the user select functionality
	 *
	 * @since 2.10.2
	 */
	public function init() {
		add_action( 'wp_ajax_gfwebapi_get_users', array( $this, 'ajax_get_users' ) );
	}

	/**
	 * Handle AJAX request to get users
	 *
	 * @since 2.10.2
	 */
	public function ajax_get_users() {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_api_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'gravityforms' ) ) );
		}

		check_ajax_referer( 'gfwebapi_get_users', 'nonce' );

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		wp_send_json_success( \GFWebAPI::get_users( array( 'number' => 10 ), $search ) );
	}
}
