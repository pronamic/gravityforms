<?php
/**
 * Handles AJAX services such as validation and submission.
 *
 * @package Gravity_Forms\Gravity_Forms\Ajax
 */
namespace Gravity_Forms\Gravity_Forms\Ajax;

use GFCommon;

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
	 *
	 * @deprecated 2.9.9 Use GFAPI::validate_form() instead.
	 * @remove-in 3.1.0
	 */
	public function validate_form() {

		_deprecated_function( __METHOD__, '2.9.9', 'GFAPI::validate_form()' );

		// Check nonce.
		$nonce_result = check_ajax_referer( 'gform_ajax_submission', 'gform_ajax_nonce', false );

		if ( ! $nonce_result ) {
			wp_send_json_error( $this->nonce_validation_message() );
		}

		$form_id     = absint( rgpost( 'form_id' ) );
		$target_page = absint( rgpost( 'gform_target_page_number_' . $form_id ) );
		$source_page = absint( rgpost( 'gform_source_page_number_' . $form_id ) );

		$this->hydrate_get_from_current_page_url();

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
			GFCommon::send_json_error( $this->nonce_validation_message() );
		}

		$this->hydrate_get_from_current_page_url();

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

		// Getting posted values.
		$target_page       = absint( rgpost( 'gform_target_page_number_' . $form_id ) );
		$source_page       = absint( rgpost( 'gform_source_page_number_' . $form_id ) );
		$field_values      = rgpost( 'gform_field_values' );
		$theme             = rgpost( 'gform_theme' );
		$style             = rgpost( 'gform_style_settings' );
		$submission_method = rgpost( 'gform_submission_method' );

		require_once \GFCommon::get_base_path() . '/form_display.php';

		$result = \GFAPI::submit_form( $form_id, array(), $field_values, $target_page, $source_page, \GFFormDisplay::SUBMISSION_INITIATED_BY_WEBFORM );

		if ( is_wp_error( $result ) ) {
			GFCommon::send_json_error( $result->get_error_message() );
		}

		$form = $result['form'];

		// Adding confirmation markup if there is a confirmation message to be displayed.
		if ( rgar( $result, 'confirmation_type' ) == 'message' && ! empty( rgar( $result, 'confirmation_message' ) ) ) {
			// Get confirmation markup from get_form(). This is necessary to ensure that confirmation markup is properly formatted.
			$result['confirmation_markup'] = \GFFormDisplay::get_form( $form_id, false, false, false, $field_values, false, 0, $theme, $style );
		} elseif ( ! $result['is_valid'] ) {
			$result = $this->add_validation_summary( $form, $result );

			// Refresh the form markup if single page or multipage forms have validation errors.
			$result['form_markup'] = \GFFormDisplay::get_form( $form_id, (bool) rgpost( 'display_title' ), (bool) rgpost( 'display_description' ), false, $field_values, false, 0, $theme, $style );
		} elseif ( $target_page > 0 ) {
			// Getting the target page number taking page conditional logic into account.
			$page_number = \GFFormDisplay::get_target_page( $form, $source_page, $field_values );

			$result = $this->add_validation_summary( $form, $result );

			// Getting the field markup for the target page if the form is a multipage form.
			$result['page_markup'] = \GFFormDisplay::get_page( $form_id, $page_number, $field_values, $theme, $style, $submission_method );
		}

		$result['submission_type'] = $this->get_submission_type( $target_page, $source_page );

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

		GFCommon::send_json_success( $result );
	}

	/**
	 * Returns the submission type based on the target and source page numbers.
	 *
	 * @since 2.9.7
	 *
	 * @param int $target_page The target page number.
	 * @param int $source_page The source page number.
	 *
	 * @return string The submission type. Possible values are SUBMISSION_TYPE_SUBMIT, SUBMISSION_TYPE_NEXT, SUBMISSION_TYPE_PREVIOUS, and SUBMISSION_TYPE_SAVE_AND_CONTINUE.
	 */
	public function get_submission_type( $target_page, $source_page ) {
		if ( isset( $_POST['gform_send_resume_link'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return \GFFormDisplay::SUBMISSION_TYPE_SEND_LINK;
		} elseif ( rgpost( 'gform_save') ) {
			return \GFFormDisplay::SUBMISSION_TYPE_SAVE_AND_CONTINUE;
		} elseif ( $target_page === 0 ) {
			return \GFFormDisplay::SUBMISSION_TYPE_SUBMIT;
		} elseif ( $target_page > $source_page ) {
			return \GFFormDisplay::SUBMISSION_TYPE_NEXT;
		} else {
			return \GFFormDisplay::SUBMISSION_TYPE_PREVIOUS;
		}
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

		GFCommon::send_json_success(
			array(
				'is_valid'             => true,
				'confirmation_type'    => 'message',
				'confirmation_message' => $confirmation,
				'confirmation_markup'  => $confirmation,
				'submission_type'      => \GFFormDisplay::SUBMISSION_TYPE_SEND_LINK,
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

	/**
	 * Making the current page query string available for use with form filters.
	 *
	 * @since 2.9.5
	 *
	 * @return void
	 */
	private function hydrate_get_from_current_page_url() {
		$url = rgpost( 'current_page_url' );

		if ( empty( $url ) ) {
			return;
		}

		$query_string = parse_url( rawurldecode( $url ), PHP_URL_QUERY );

		if ( empty( $query_string ) || ! is_string( $query_string ) ) {
			return;
		}

		parse_str( $query_string, $query );
		unset( $query['gf_page'] ); // Removing so it doesn't conflict with gf_ajax_page=preview.
		$_GET = array_merge( $_GET, $query ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

}
