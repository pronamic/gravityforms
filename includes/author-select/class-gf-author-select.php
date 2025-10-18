<?php

namespace Gravity_Forms\Gravity_Forms\Author_Select;

defined( 'ABSPATH' ) || die();

use GFCommon;

/**
 * Class GF_Author_Select
 *
 * Handles AJAX requests for the author select dropdown in the form editor
 *
 * @since 2.9.20
 */
class GF_Author_Select {

	/**
	 * Initialize the author select functionality
	 *
	 * @since 2.9.20
	 */
	public function init() {
		add_action( 'wp_ajax_gf_get_users', array( $this, 'ajax_get_users' ) );
	}

	/**
	 * Handle AJAX request to get users
	 *
	 * @since 2.9.20
	 */
	public function ajax_get_users() {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied', 'gravityforms' ) ) );
		}

		check_ajax_referer( 'gf_get_users', 'nonce' );

		$search  = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		$args = array(
			'number' => 10,
			'fields' => array( 'ID', 'display_name' ),
		);

		/**
		 * Filter the arguments used to query users for the author dropdown.
		 * Originally added to limit users for performance.
		 * Now with AJAX and 10-result limit by default, useful for role restrictions or custom filtering.
		 *
		 * @since 1.3.10
		 *
		 * @param array $args WP_User_Query arguments for get_users()
		 */
		$args = gf_apply_filters( array( 'gform_author_dropdown_args', $form_id ), $args );

		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$users = get_users( $args );

		$response = array();
		foreach ( $users as $user ) {
			$response[] = array(
				'value' => $user->ID,
				'label' => esc_html( $user->display_name ),
			);
		}

		wp_send_json_success( $response );
	}
}
