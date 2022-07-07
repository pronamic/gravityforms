<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_REST_Form_Submissions_Validation_Controller extends GF_REST_Controller {

	/**
	 * The base of this controller's route.
	 *
	 * @since 2.6.4
	 *
	 * @var string
	 */
	public $rest_base = 'forms/(?P<form_id>[\d]+)/submissions/validation';

	/**
	 * Registers the route.
	 *
	 * @since 2.6.4
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_form' ),
				'permission_callback' => array( $this, 'validate_form_permissions_check' ),
			),
		) );
	}

	/**
	 * Validates submitted values for the specified form.
	 *
	 * @since 2.6.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function validate_form( $request ) {
		$params       = $request->get_json_params();
		$input_values = $params;

		if ( empty( $params ) ) {
			$params       = $request->get_body_params();
			$input_values = array(); // The input values are already in $_POST.
		}

		$field_values = rgar( $params, 'field_values', array() );
		$target_page  = rgar( $params, 'target_page', 0 );
		$source_page  = rgar( $params, 'source_page', 1 );

		$result = GFAPI::validate_form( rgar( $request->get_url_params(), 'form_id' ), $input_values, $field_values, $target_page, $source_page );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}

		return $this->prepare_item_for_response( $result, $request );
	}

	/**
	 * All users can submit values for validation.
	 *
	 * @since 2.6.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function validate_form_permissions_check( $request ) {
		return true;
	}


	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 2.6.4
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$status = $item['is_valid'] ? 200 : 400;

		return new WP_REST_Response( $item, $status );
	}

}
