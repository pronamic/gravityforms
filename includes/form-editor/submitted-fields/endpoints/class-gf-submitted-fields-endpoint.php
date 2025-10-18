<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Submitted_Fields\Endpoints;

defined( 'ABSPATH' ) || die();

use GFCommon;

/**
 * Class GF_Submitted_Fields_Endpoint
 *
 * Endpoint for retrieving submitted fields data for the form editor.
 *
 * @since 2.9.20
 *
 * @package Gravity_Forms\Gravity_Forms\Form_Editor\Submitted_Fields\Endpoints
 */
class GF_Submitted_Fields_Endpoint {

	/**
	 * The action name for this endpoint.
	 *
	 * @since 2.9.20
	 *
	 * @var string
	 */
	const ACTION_NAME = 'gf_get_submitted_fields';

	/**
	 * The nonce action for this endpoint.
	 *
	 * @since 2.9.20
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'gf_get_submitted_fields';

	/**
	 * @var array
	 */
	private $dependencies;

	/**
	 * @since 2.9.20
	 *
	 * @param array $dependencies
	 */
	public function __construct( $dependencies ) {
		$this->dependencies = $dependencies;
	}

	/**
	 * Handle the endpoint request.
	 *
	 * @since 2.9.20
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'gravityforms' ) ), 403 );
		}

		if ( ! wp_verify_nonce( rgpost( 'nonce' ), self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'gravityforms' ) ), 403 );
		}

		$form_id = absint( rgpost( 'form_id' ) );
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'gravityforms' ) ), 400 );
		}

		$gf_forms_model = $this->dependencies['gf_forms_model'];
		$fields_string  = $gf_forms_model::get_submitted_fields( $form_id );
		$fields         = empty( $fields_string ) ? array() : array_map( 'intval', explode( ',', $fields_string ) );

		wp_send_json_success( array(
			'fields'  => $fields,
			'form_id' => $form_id,
		) );
	}
}
