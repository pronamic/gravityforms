<?php
/**
 * Handles logic for honeypot service.
 *
 * @package Gravity_Forms\Gravity_Forms\Honeypot
 */

namespace Gravity_Forms\Gravity_Forms\Honeypot;

/**
 * Class GF_Honeypot_Handler
 *
 * @since 2.7
 *
 * Provides functionality for handling honeypot spam prevention services.
 */
class GF_Honeypot_Handler {

	/**
	 * Target of the gform_entry_is_spam filter. Checks entry for honeypot validation and returns true or false depending on the result.
	 *
	 * @since 2.7
	 *
	 * @param bool  $is_spam Variable being filtered. True for spam, false for non-spam.
	 * @param array $form Current form object.
	 *
	 * @return bool Returns true if honeypot validation fails. False otherwise.
	 */
	public function handle_entry_is_spam( $is_spam, $form ) {

		// If already marked as spam, don't change it.
		if ( $is_spam ) {
			return true;
		}

		// Bypass honeypot validation if disabled.
		if ( ! $this->is_honeypot_enabled( $form ) ) {
			return false;
		}

		$is_spam = ! $this->validate_honeypot( $form );

		// Setting filter that flagged entry as spam so that an entry note is created.
		if ( $is_spam ) {
			\GFCommon::set_spam_filter( $form['id'], __( 'Honeypot Spam Filter', 'gravityforms' ), __( 'Failed Honeypot Validation.', 'gravityforms' ) );
		}

		return $is_spam;
	}

	/**
	 * Target of the gform_abort_submission_with_confirmation filter. Aborts form submission early with a confirmation when honeypot fails and it is configured not to create an entry.
	 *
	 * @since 2.7
	 *
	 * @param bool  $do_abort Variable being filtered. True to abort submission, false to continue.
	 * @param array $form Current form object.
	 *
	 * @return bool Returns true to abort form submission early and display confirmation. Returns false to let submission continue.
	 */
	public function handle_abort_submission( $do_abort, $form ) {

		// If already marked to abort early, let it abort early.
		if ( $do_abort ) {
			return true;
		}

		// Do not abort submission if Honeypot should be disabled or if honeypot action is set to create an entry.
		if ( ! $this->is_honeypot_enabled( $form ) || rgar( $form, 'honeypotAction' ) == 'spam' ) {
			return false;
		}

		$do_abort = ! $this->validate_honeypot( $form );
		\GFCommon::log_debug( __METHOD__ . '(): Result from Honeypot: ' . json_encode( $do_abort ) );

		return $do_abort;
	}

	/**
	 * Target of the gform_after_submission. Clears the cached results.
	 *
	 * @since 2.7
	 *
	 * @param array $entry Current entry object.
	 * @param array $form Current form object.
	 */
	public function handle_after_submission( $entry, $form ) {
		\GFCache::delete( "honeypot_{$form['id']}" );
	}

	/**
	 * Adds the honeypot field to the form if honeypot is enabled.
	 *
	 * @since 2.7
	 *
	 * @param array $form Current form object.
	 *
	 * @return array Returns a form object with the new honeypot field appended to the fields array.
	 */
	public function maybe_add_honeypot_field( $form ) {

		if ( rgar( $form, 'enableHoneypot' ) ) {
			$form['fields'][] = $this->get_honeypot_field( $form );
		}

		return $form;
	}

	/**
	 * Validates the submission against the honeypot field.
	 *
	 * @since 2.7
	 *
	 * @param array $form The current form object.
	 *
	 * @return bool True if form passes the honeypot validation (i.e. Not spam). False if honeypot validation fails (i.e. spam)
	 */
	public function validate_honeypot( $form ) {

		// If validation has already been computed for this form, no need to validate it again.
		$cache_key = "honeypot_{$form['id']}";
		if ( \GFCache::get( $cache_key ) !== false ) {
			return (bool) \GFCache::get( $cache_key );
		}

		$honeypot_id               = $this->get_honeypot_field_id( $form );
		$pass_server_side_honeypot = rgempty( "input_{$honeypot_id}" );
		\GFCommon::log_debug( __METHOD__ . '(): Is honeypot input empty? ' . json_encode( $pass_server_side_honeypot ) );

		// Bypass JS field hash validation on GFAPI submissions.
		if ( $this->is_api_submission() ) {
			$pass_js_honeypot = true;
			\GFCommon::log_debug( __METHOD__ . '(): Submission initiated by GFAPI. Honeypot JS field hash validation bypassed.' );
		} else {
			$pass_js_honeypot = $this->is_valid_version_hash( rgpost( 'version_hash' ) );
			\GFCommon::log_debug( __METHOD__ . '(): Is version_hash input valid? ' . json_encode( $pass_js_honeypot ) );
		}

		$is_success = $pass_server_side_honeypot && $pass_js_honeypot;
		\GFCommon::log_debug( __METHOD__ . '(): Are both inputs valid? ' . json_encode( $is_success ) );

		\GFCache::set( $cache_key, (int) $is_success );

		return $is_success;
	}

	/**
	 * Returns the ID of the honeypot field.
	 *
	 * @since 2.7
	 *
	 * @param array $form Current form object.
	 *
	 * @return int Returns the id of the honeypot field.
	 */
	public function get_honeypot_field_id( $form ) {

		if ( empty( $form['fields'] ) ) {
			return 0;
		}

		// Look for honeypot field in the form.
		$honeypot_field = \GFFormsModel::get_fields_by_type( $form, array( 'honeypot' ) );
		if ( count( $honeypot_field ) > 0 ) {
			return $honeypot_field[0]->id;
		}

		// If no honeypot field in the form, return the largest field ID + 1.
		return \GFFormsModel::get_next_field_id( $form['fields'] );

	}

	/**
	 * Creates the honeypot field object for the given form.
	 *
	 * @since 2.7
	 *
	 * @param array $form The form the honeypot field is to be created for.
	 *
	 * @return GF_Field Returns the honeypot field.
	 */
	private function get_honeypot_field( $form ) {

		$labels     = $this->get_honeypot_labels();
		$field_data = array(
			'type'        => 'honeypot',
			'label'       => $labels[ rand( 0, count( $labels ) - 1 ) ],
			'id'          => \GFFormsModel::get_next_field_id( $form['fields'] ),
			'cssClass'    => 'gform_validation_container',
			'description' => __( 'This field is for validation purposes and should be left unchanged.', 'gravityforms' ),
			'formId'      => absint( $form['id'] ),
		);

		return \GF_Fields::create( $field_data );
	}

	/**
	 * Returns an array of possible labels to be used for the Honeypot field.
	 *
	 * @since 2.7
	 *
	 * @return array Returns an array of possible labels
	 */
	private function get_honeypot_labels() {
		$honeypot_labels = array( 'Name', 'Email', 'Phone', 'Comments' );

		/**
		 * Allow the honeypot field labels to be overridden.
		 *
		 * @since 2.0.7.16
		 *
		 * @param array $honeypot_labels The honeypot field labels.
		 */
		return apply_filters( 'gform_honeypot_labels_pre_render', $honeypot_labels );
	}

	/**
	 * Validates a version hash.
	 *
	 * @since 2.7
	 *
	 * @param string $hash The version hash to be validated.
	 *
	 * @return bool Returns true if the hash is validated against the current and previous version of Gravity Forms
	 */
	public function is_valid_version_hash( $hash ) {

		// Allow password to validate on current version and previous version.
		$allowed_hashes = array( wp_hash( \GFForms::$version ) );

		$previous_version = get_option( 'gf_previous_db_version' );
		if ( ! empty( $previous_version ) ) {
			$allowed_hashes[] = wp_hash( $previous_version );
		}

		return in_array( $hash, $allowed_hashes );
	}

	/**
	 * Determines if Honeypot should be enabled for this form submission.
	 *
	 * @since 2.7
	 *
	 * @param array $form The current form object.
	 *
	 * @return bool True if honeypot should be enabled. False otherwise.
	 */
	public function is_honeypot_enabled( $form ) {

		// Honeypot should be disabled if ANY of the following is true:
		// 1- honeypot is not enabled by this form in form settings.
		// 2- the form is submitted from preview.
		// 3- the form is submitted from the WP dashboard.
		$is_disabled = ! rgar( $form, 'enableHoneypot' ) || \GFCommon::is_preview() || is_admin();

		return ! $is_disabled;
	}

	/**
	 * Determines if the current form submission was initiated via GFAPI.
	 *
	 * @since 2.7
	 *
	 * @return bool True if the current form submission was initiated via GFAPI. False otherwise.
	 */
	public function is_api_submission() {
		return \GFFormDisplay::$submission_initiated_by == \GFFormDisplay::SUBMISSION_INITIATED_BY_API;
	}
}
