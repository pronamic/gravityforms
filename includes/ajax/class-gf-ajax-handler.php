<?php
/**
 * Handles AJAX services such as validation and submission.
 *
 * @package Gravity_Forms\Gravity_Forms\Ajax
 */
namespace Gravity_Forms\Gravity_Forms\Ajax;

/**
 * Class GF_Ajax_Handler
 *
 * @since 2.9.0
 *
 * Provides functionality for handling AJAX validation and submission.
 */
class GF_Ajax_Handler {

	/**
	 * Handles the form validation AJAX requests. Uses the global $_POST array and sends the form validation result as a JSON response.
	 *
	 * @since 2.9.0
	 */
	public function validate_form() {

		// Check nonce.
		$nonce_result = check_ajax_referer( 'gform_ajax_submission', 'gform_ajax_nonce', false );

		if ( ! $nonce_result ) {
			wp_send_json_error( $this->nonce_validation_message() );
		}

		$form_id     = absint( rgpost( 'form_id' ) );
		$target_page = absint( rgpost( 'gform_target_page_number_' . $form_id ) );
		$source_page = absint( rgpost( 'gform_source_page_number_' . $form_id ) );

		$result = \GFAPI::validate_form( $form_id, array(), rgpost( 'gform_field_values' ), $target_page, $source_page );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$form = $result['form'];
		if ( ! $result['is_valid'] ) {
			$result = $this->add_validation_summary( $form, $result );
		}

		/**
		 * Filters the form validation result.
		 *
		 * @since 2.9.0
		 *
		 * @param array $result The form validation result to be filtered.
		 *
		 * @return array The filtered form validation result.
		 */
		$result = gf_apply_filters( array( 'gform_ajax_validation_result', $form['id'] ), $result );

		// Remove form from result.
		unset( $result['form'] );

		wp_send_json_success( $result );
	}

	/**
	 * Handles the form submission AJAX requests. Uses the global $_POST array and sends the form submission result as a JSON response.
	 *
	 * @since 2.9.0
	 */
	public function submit_form() {

		// Check nonce.
		$nonce_result = check_ajax_referer( 'gform_ajax_submission', 'gform_ajax_nonce', false );

		if ( ! $nonce_result ) {
			wp_send_json_error( $this->nonce_validation_message() );
		}

		$form_id = absint( rgpost( 'form_id' ) );

		/**
		 * Allows actions to be performed right before an AJAX form submission.
		 *
		 * @since 2.9.0
		 *
		 * @param int $form_id The form ID.
		 */
		gf_do_action( array( 'gform_ajax_pre_submit_form', $form_id ), $form_id );

		// Handling the save link submission.
		if ( isset( $_POST['gform_send_resume_link'] ) ) {
			$this->submit_save_link();
			return;
		}

		$target_page = absint( rgpost( 'gform_target_page_number_' . $form_id ) );
		$source_page = absint( rgpost( 'gform_source_page_number_' . $form_id ) );

		$result = \GFAPI::submit_form( $form_id, array(), rgpost( 'gform_field_values' ), $target_page, $source_page );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$form = $result['form'];

		// Adding validation markup if form failed validation
		if ( ! $result['is_valid'] ) {
			$result = $this->add_validation_summary( $form, $result );
		}

		// Adding confirmation markup if there is a confirmation message to be displayed.
		if ( rgar( $result, 'confirmation_type' ) == 'message' && ! empty( rgar( $result, 'confirmation_message' ) ) ) {

			// Get confirmation markup from get_form(). This is necessary to ensure that confirmation markup is properly formatted.
			$result['confirmation_markup'] = \GFFormDisplay::get_form( $form_id, false, false, false, rgpost( 'gform_field_values' ), false, 0, rgpost( 'gform_theme' ), rgpost( 'gform_style_settings') );
		}

		/**
		 * Filters the ajax form submission result.
		 *
		 * @since 2.9.0
		 *
		 * @param array $result The form submission result to be filtered.
		 *
		 * @return array The filtered form submission result.
		 */
		$result = gf_apply_filters( array( 'gform_ajax_submission_result', $form_id ), $result );

		// Remove form from result.
		unset( $result['form'] );

		wp_send_json_success( $result );
	}


	/**
	 * Handles the save link submission. Uses the $_POST array and sends the save link result as a JSON response.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public function submit_save_link() {
		$form_id = absint( rgpost( 'form_id' ) );

		\GFFormDisplay::process_send_resume_link();

		$confirmation = \GFFormDisplay::get_form( $form_id, false, false, false, rgpost( 'gform_field_values' ) );

		wp_send_json_success(
			array(
				'is_valid'             => true,
				'confirmation_type'    => 'message',
				'confirmation_message' => $confirmation,
				'confirmation_markup'  => $confirmation,
			)
		);
	}

	/**
	 * Filters the lifespan of the nonce used for AJAX submissions and validation.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $lifespan_in_seconds The lifespan of the nonce in seconds. Defaults to 3 days
	 * @param string $action              The nonce action (gform_ajax_submission or gform_ajax_validation).
	 *
	 * @return int The filtered lifespan of the nonce in seconds.
	 */
	public function nonce_life( $lifespan_in_seconds, $action = '' ) {
		if ( in_array( $action, array( 'gform_ajax_submission', 'gform_ajax_validation' ) ) ) {

			/**
			 * Filters the lifespan of the nonce used for AJAX submissions and validation.
			 *
			 * @since 2.9.0
			 *
			 * @param int    $lifespan_in_seconds The lifespan of the nonce in seconds (defaults to 3 days).
			 * @param string $action              The nonce action (gform_ajax_submission or gform_ajax_validation).
			 *
			 * @return int The lifespan of the nonce in seconds.
			 */
			$lifespan_in_seconds = apply_filters( 'gform_nonce_life', 3 * DAY_IN_SECONDS, $action );
		}

		return $lifespan_in_seconds;
	}

	/**
	 * Returns the nonce validation message.
	 *
	 * @since 2.9.0
	 *
	 * @return string The nonce validation message.
	 */
	private function nonce_validation_message() {
		return esc_html__( 'Your session has expired. Please refresh the page and try again.', 'gravityforms' );
	}

	/**
	 * Adds the validation summary properties to the form validation result.
	 *
	 * @since 2.9.0
	 *
	 * @param array $form   The form being validated.
	 * @param array $result The form validation result.
	 *
	 * @return mixed Returns the form validation result with the validation summary properties added.
	 */
	private function add_validation_summary( $form, $result ) {
		$summary                      = \GFFormDisplay::get_validation_errors_markup( $form, array(), rgar( $form, 'validationSummary' ) );
		$result['validation_summary'] = wp_kses( $summary, wp_kses_allowed_html( 'post' ) );
		return $result;
	}
}
