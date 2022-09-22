<?php

namespace Gravity_Forms\Gravity_Forms\Save_Form\Endpoints;

use Gravity_Forms\Gravity_Forms\Save_Form\Config\GF_Admin_Form_Save_Config;
use Gravity_Forms\Gravity_Forms\Form_Editor\Save_Form\Config\GF_Form_Editor_Form_Save_Config;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Form_CRUD_Handler;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;

/**
 * AJAX Endpoint for Saving the form in the admin area.
 *
 * @since   2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Save_Form\Endpoints
 */
class GF_Save_Form_Endpoint_Admin {

	// AJAX action name.
	const ACTION_NAME = 'admin_save_form';
	// The required parameters keys in the request.
	const PARAM_FORM_ID   = 'form_id';
	const PARAM_FORM_JSON = 'data';

	/**
	 * The ID of the form we are working with.
	 *
	 * @since 2.6
	 *
	 * @var int
	 */
	protected $form_id;

	/**
	 * The JSON representation of the form.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $form_json;

	/**
	 * An instance of the CRUD service.
	 *
	 * @since 2.6
	 *
	 * @var GF_Form_CRUD_Handler
	 */
	protected $form_crud_handler;

	/**
	 * An instance of GFFormsModel to call common static functions.
	 *
	 * @since 2.6
	 *
	 * @var \GFFormsModel
	 */
	protected $gf_forms_model;

	/**
	 * An instance of GFForms to call common static functions.
	 *
	 * @since 2.6
	 *
	 * @var \GFForms
	 */
	protected $gf_forms;


	/**
	 * The required parameters to execute the endpoint.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $required_params = array(
		self::PARAM_FORM_JSON,
		self::PARAM_FORM_ID,
	);


	/**
	 * GF_Save_Form_Endpoint_Admin constructor.
	 *
	 * @since 2.6
	 *
	 * @param array $dependencies Array of dependency objects.
	 */
	public function __construct( $dependencies ) {
		$this->form_crud_handler = rgar( $dependencies, 'gf_form_crud_handler' );
		$this->gf_forms_model    = rgar( $dependencies, 'gf_forms_model' );
		$this->gf_forms          = rgar( $dependencies, 'gf_forms' );
	}

	/**
	 * Handle the AJAX save request.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function handle() {

		if ( ! $this->validate() ) {
			wp_send_json_error( 'Missing required parameter', 400 );
		}

		$this->gather_required_params();

		$result = $this->save();

		if ( rgar( $result, 'status' ) === GF_Form_CRUD_Handler::STATUS_SUCCESS ) {
			wp_send_json_success( $this->get_success_status_response( $result ) );
		} else {
			wp_send_json_error( $this->get_error_status_response( $result ) );
		}

	}

	/**
	 * Validates the request and makes sure it has the required parameters.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	protected function validate() {
		check_ajax_referer( static::ACTION_NAME );

		foreach ( $this->required_params as $key ) {
			if ( empty( rgpost( $key ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Assign the required parameters to their corresponding properties.
	 *
	 * @since 2.6
	 */
	protected function gather_required_params() {
		$this->form_id   = rgpost( self::PARAM_FORM_ID );
		$this->form_json = rgpost( self::PARAM_FORM_JSON );
	}

	/**
	 * Saves the form.
	 *
	 * @since 2.6
	 *
	 * @return array The status of the operation and the form data.
	 */
	protected function save() {

		return $this->form_crud_handler->save( $this->form_id, $this->form_json );

	}

	/**
	 * Handles a successful operation and returns the desired response.
	 *
	 * @since 2.6
	 *
	 * @param array $result The result of the operation.
	 *
	 * @return mixed
	 */
	protected function get_success_status_response( $result ) {
		return $this->wrap_json_response( $result );
	}

	/**
	 * Handles a failed operation and returns the desired response.
	 *
	 * @since 2.6
	 *
	 * @param array $result The result of the operation.
	 *
	 * @return mixed
	 */
	protected function get_error_status_response( $result ) {
		$status = rgar( $result, 'status', GF_Form_CRUD_Handler::STATUS_FAILURE );

		if ( $status === GF_Form_CRUD_Handler::STATUS_DUPLICATE_TITLE ) {
			$result['error'] = esc_html_e( 'Please enter a unique form title, this title is used for an existing form.', 'gravityforms' );
		} elseif ( $status === 0 || ! is_numeric( $status ) ) {
			$result['error'] = esc_html__( 'There was an error while saving your form.', 'gravityforms' ) . sprintf( esc_html__( 'Please %1$scontact our support team%2$s.', 'gravityforms' ), '<a target="_blank" href="' . esc_attr( GFCommon::get_support_url() ) . '">', '</a>' );
		}

		return $this->wrap_json_response( $result );

	}

	/**
	 * Wrap the response inside two known strings, so we can extract the response object in case of content output during to notices for example.
	 *
	 * @since 2.5
	 *
	 * @param array $response The Response array.
	 *
	 * @return array
	 */
	protected function wrap_json_response( $response ) {
		$json_start = array( GF_Admin_Form_Save_Config::JSON_START_STRING => 0 );
		$json_end   = array( GF_Admin_Form_Save_Config::JSON_END_STRING => 1 );

		$response = array_merge( $json_start, $response );
		$response = array_merge( $response, $json_end );

		return $response;
	}

}
