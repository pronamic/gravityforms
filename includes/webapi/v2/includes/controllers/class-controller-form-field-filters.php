<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_REST_Form_Field_Filters_Controller extends GF_REST_Controller {

	/**
	 * The base of this controller's route.
	 *
	 * @since 2.4.22
	 *
	 * @var string
	 */
	public $rest_base = 'forms/(?P<form_id>[\d]+)/field-filters';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 2.4.22
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );
	}

	/**
	 * Returns the field filters for the specified form.
	 *
	 * @since 2.4.22
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$form = GFAPI::get_form( $request['form_id'] );

		if ( ! $form ) {
			return new WP_Error( 'gf_not_found', __( 'Form not found', 'gravityforms' ), array( 'status' => 404 ) );
		}

		if ( ! empty( $request['_admin_labels'] ) ) {
			/** @var GF_Field $field The field object. */
			foreach ( $form['fields'] as $field ) {
				$field->set_context_property( 'use_admin_label', true );
			}
		}

		return new WP_REST_Response( GFCommon::get_field_filter_settings( $form ) );
	}

	/**
	 * Check if the user for the current request has permission to get the field filters.
	 *
	 * @since 2.4.22
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		/**
		 * Filters the capability required to get the field filters via the REST API.
		 *
		 * @since 2.4.22
		 *
		 * @param string|array    $capability The capability required for this endpoint.
		 * @param WP_REST_Request $request    Full data about the request.
		 */
		$capability = apply_filters( 'gform_rest_api_capability_get_field_filters', 'gravityforms_view_entries', $request );

		return $this->current_user_can_any( $capability, $request );
	}

	/**
	 * Returns an array of supported query params for this endpoint.
	 *
	 * @since 2.4.22
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'_admin_labels' => array(
				'description' => 'Whether to include the field admin labels in the response, if configured.',
				'type'        => 'integer',
			),
		);
	}

}
