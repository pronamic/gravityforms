<?php

namespace Gravity_Forms\Gravity_Forms\Post_Custom_Field_Select;

defined( 'ABSPATH' ) || die();

use GFCommon;

/**
 * Class GF_Post_Custom_Field_Select
 *
 * Handles AJAX requests for the post custom field name select dropdown in the form editor
 *
 * @since 2.9.20
 */
class GF_Post_Custom_Field_Select {

	/**
	 * Initialize the post custom field select functionality
	 *
	 * @since 2.9.20
	 */
	public function init() {
		add_action( 'wp_ajax_gf_get_custom_fields', array( $this, 'ajax_custom_fields' ) );
	}

	/**
	 * Handle AJAX request to get custom field names
	 *
	 * @since 2.9.20
	 */
	public function ajax_custom_fields() {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied', 'gravityforms' ) ) );
		}

		check_ajax_referer( 'gf_get_custom_fields', 'nonce' );

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$custom_fields = $this->get_custom_field_names( $search );

		$response = array();
		foreach ( $custom_fields as $field ) {
			$response[] = array(
				'value' => $field,
				'label' => $field,
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Get all unique custom field names from the postmeta table, optionally filtered by search.
	 *
	 * @since 2.9.20
	 *
	 * @param string $search custom field name search term
	 * @return array
	 */
	private function get_custom_field_names( $search = '' ) {
		global $wpdb;

		$not_like = '\_%';

		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key NOT LIKE %s AND meta_key LIKE %s ORDER BY meta_key ASC LIMIT 10", $not_like, $like ) );
		} else {
			$results = wp_cache_get( 'gf_custom_fields_all', 'gf_custom_fields' );
			if ( false === $results ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key NOT LIKE %s ORDER BY meta_key ASC LIMIT 10", $not_like ) );
				wp_cache_set( 'gf_custom_fields_all', $results, 'gf_custom_fields', 300 );
			}
		}

		return $results;
	}
}
