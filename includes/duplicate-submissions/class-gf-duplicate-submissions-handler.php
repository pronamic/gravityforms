<?php
/**
 * Handles logic for duplicate submission prevention services.
 *
 * @package Gravity_Forms\Gravity_Forms\Duplicate_Submissions
 */

namespace Gravity_Forms\Gravity_Forms\Duplicate_Submissions;

/**
 * Class GF_Duplicate_Submissions_Handler
 *
 * @since 2.9.1
 *
 * Provides functionality for handling duplicate submissions while avoiding multiple
 * entries being submitted.
 */
class GF_Duplicate_Submissions_Handler {

	/**
	 * The URL parameter used for redirect protection in Safari.
	 */
	const SAFARI_REDIRECT_PARAM = 'gf_protect_submission';

	/**
	 * The base URL for this plugin
	 *
	 * @var string $base_url.
	 */
	private $base_url;

	/**
	 * GF_Duplicate_Submissions_Handler constructor.
	 *
	 * @param string $base_url The Base URL for this Plugin.
	 */
	public function __construct( $base_url ) {
		$this->base_url = $base_url;
	}

	/**
	 * Returns true if duplicate submission protection is enabled. false otherwise.
	 *
	 * @returns bool $is_enabled true if duplicate protection is active/enabled. false otherwise
	 */
	public function is_enabled() {

		$form_id = filter_input( INPUT_POST, 'gform_submit', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $form_id ) ) {
			return false;
		}

		/**
		 * Allows users to disable duplicate submissions protection, either globally
		 * or on a form-by-form basis.
		 *
		 * @since 2.5.15
		 *
		 * @param bool       Passes a false value by default.
		 * @param int|string Passes the current form ID.
		 */
		$is_disabled = gf_apply_filters( array( 'gform_is_disabled_duplicate_submissions_protection', $form_id ), false, $form_id );

		return ! $is_disabled;

	}

	/**
	 * Enqueue the JS file if this is a form submission configured for duplicate protection.
	 */
	public function maybe_enqueue_scripts() {

		if ( $this->is_enabled() ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
			wp_enqueue_script( 'gform_duplicate_submissions', $this->base_url . "/js/duplicate-submissions{$min}.js", array(), true );
			wp_localize_script( 'gform_duplicate_submissions', 'gf_duplicate_submissions', $this->get_localized_script_data() );
		}
	}

	/**
	 * Get the correct data to localize to the JS file.
	 *
	 * @return array
	 */
	private function get_localized_script_data() {
		return array(
			'is_gf_submission'      => (int) $this->is_valid_submission(),
			'safari_redirect_param' => self::SAFARI_REDIRECT_PARAM,
		);
	}

	/**
	 * Check if the current submission exists, and is valid.
	 *
	 * @return bool
	 */
	private function is_valid_submission() {
		$form_id = filter_input( INPUT_POST, 'gform_submit', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $form_id ) || ! class_exists( '\GFFormDisplay' ) ) {
			return false;
		}

		$entry_id = rgars( \GFFormDisplay::$submission, $form_id . '/lead/id' );

		if ( empty( $entry_id ) ) {
			return false;
		}

		\GFCommon::log_debug( __METHOD__ . sprintf( '(): form #%d. entry #%d.', $form_id, $entry_id ) );

		return true;
	}

	/**
	 * Redirect to a $_GET request if we detect a dupe submission from Safari.
	 */
	public function maybe_handle_safari_redirect() {
		if ( rgget( self::SAFARI_REDIRECT_PARAM ) != '1' || ! $this->is_enabled() ) {
			return;
		}

		// Get the submission URL from the $_SERVER, and strip out our redirect param.
		$submission_url = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL );
		$base_url       = remove_query_arg( self::SAFARI_REDIRECT_PARAM, $submission_url );

		// Redirect to the form's page URL as a GET request.
		wp_safe_redirect( $base_url, 303 );
		exit;
	}
}
