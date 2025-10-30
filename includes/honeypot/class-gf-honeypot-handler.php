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
	 * @since 2.9.21 Updated to get the entry note message from `get_cached_result()`.
	 *
	 * @param bool  $is_spam Variable being filtered. True for spam, false for non-spam.
	 * @param array $form    Current form object.
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
			$form_id = absint( rgar( $form, 'id' ) );
			$result  = $this->get_cached_result( $form_id );
			$message = rgar( $result, 'message' ) ?: __( 'Failed Honeypot Validation.', 'gravityforms' );
			\GFCommon::set_spam_filter( $form_id, __( 'Honeypot Spam Filter', 'gravityforms' ), $message );
		}

		return $is_spam;
	}

	/**
	 * Target of the gform_abort_submission_with_confirmation filter. Aborts form submission early with a confirmation when honeypot fails and it is configured not to create an entry.
	 *
	 * @since 2.7
	 * @since 2.9.8 Updated honeypotAction default to spam.
	 *
	 * @param bool  $do_abort Variable being filtered. True to abort submission, false to continue.
	 * @param array $form     Current form object.
	 *
	 * @return bool Returns true to abort form submission early and display confirmation. Returns false to let submission continue.
	 */
	public function handle_abort_submission( $do_abort, $form ) {

		// If already marked to abort early, let it abort early.
		if ( $do_abort ) {
			return true;
		}

		// Do not abort submission if Honeypot should be disabled or if honeypot action is set to create an entry.
		if ( ! $this->is_honeypot_enabled( $form ) || rgar( $form, 'honeypotAction', 'spam' ) === 'spam' ) {
			return false;
		}

		$do_abort = ! $this->validate_honeypot( $form );
		\GFCommon::log_debug( __METHOD__ . '(): Result from Honeypot: ' . json_encode( $do_abort ) );

		if ( $do_abort ) {
			\GFFormDisplay::$submission[ (int) rgar( $form, 'id' ) ]['is_spam'] = true;
		}

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
		if ( ! is_array( $form['fields'] ) ) {
			return $form;
		}

		if ( rgar( $form, 'enableHoneypot' ) ) {
			$form['fields'][] = $this->get_honeypot_field( $form );
		}

		return $form;
	}

	/**
	 * Returns the cached result for the given form ID.
	 *
	 * @since 2.9.21
	 *
	 * @param int $form_id The current form ID.
	 *
	 * @return false|array
	 */
	public function get_cached_result( $form_id ) {
		return \GFCache::get( "honeypot_{$form_id}", $found, false );
	}

	/**
	 * Caches the result for the given form ID.
	 *
	 * @since 2.9.21
	 *
	 * @param int   $form_id The current form ID.
	 * @param array $result  The result to be cached.
	 *
	 * @return void
	 */
	public function cache_result( $form_id, $result ) {
		\GFCache::set( "honeypot_{$form_id}", $result );
	}

	/**
	 * Validates the submission against the honeypot field.
	 *
	 * @since 2.7
	 * @since 2.9.16 Updated to use `get_input_name()`.
	 * @since 2.9.21 Updated to perform a submission speed check, return early on invalid checks, and to use `get_cached_result()` and `cache_result()`.
	 *
	 * @param array $form The current form object.
	 *
	 * @return bool True if form passes the honeypot validation (i.e. Not spam). False if honeypot validation fails (i.e. spam)
	 */
	public function validate_honeypot( $form ) {
		$form_id       = absint( rgar( $form, 'id' ) );
		$cached_result = $this->get_cached_result( $form_id );
		if ( is_array( $cached_result ) ) {
			return (bool) rgar( $cached_result, 'is_valid' );
		}

		$result = array(
			'is_valid' => false,
			'message'  => '',
		);

		$input_name           = $this->get_input_name( $form );
		$is_field_input_empty = rgempty( $input_name );
		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Is honeypot input (name: %s) empty? %s', $input_name, ( $is_field_input_empty ? 'Yes.' : 'No.' ) ) );

		if ( ! $is_field_input_empty ) {
			\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? No.' );
			$result['message'] = __( 'The honeypot input is not empty.', 'gravityforms' );
			$this->cache_result( $form_id, $result );

			return false;
		}

		if ( $this->is_api_submission() ) {
			\GFCommon::log_debug( __METHOD__ . '(): Submission initiated by GFAPI. version_hash validation and speed check bypassed.' );
			\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? Yes.' );
			$result['is_valid'] = true;
			$this->cache_result( $form_id, $result );

			return true;
		}

		$version_hash = rgpost( 'version_hash' );
		if ( empty( $version_hash ) ) {
			\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? No; version_hash input is empty.' );
			$result['message'] = __( 'The version_hash was not included in the submission.', 'gravityforms' );
			$this->cache_result( $form_id, $result );

			return false;
		}

		$is_version_hash_valid = $this->is_valid_version_hash( $version_hash );
		\GFCommon::log_debug( __METHOD__ . '(): Is version_hash input valid? ' . ( $is_version_hash_valid ? 'Yes.' : 'No.' ) );

		if ( ! $is_version_hash_valid ) {
			\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? No.' );
			$result['message'] = __( 'The wrong value was submitted for the version_hash.', 'gravityforms' );
			$this->cache_result( $form_id, $result );

			return false;
		}

		$speed_check_result = $this->is_valid_submission_speed( $form );
		if ( ! rgar( $speed_check_result, 'is_valid' ) ) {
			\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? No.' );
			$result['message'] = rgar( $speed_check_result, 'message' );
			$this->cache_result( $form_id, $result );

			return false;
		}

		\GFCommon::log_debug( __METHOD__ . '(): Is submission valid? Yes.' );
		$result['is_valid'] = true;
		$this->cache_result( $form_id, $result );

		return true;
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
	 * Returns the value used in the name attribute of the honeypot input.
	 *
	 * @since 2.9.16
	 *
	 * @param array    $form The form being rendered or validated.
	 * @param null|int $id   The ID of the honeypot field, or null to use GF_Honeypot_Handler::get_honeypot_field_id().
	 *
	 * @return string
	 */
	public function get_input_name( $form, $id = null ) {
		if ( is_null( $id ) ) {
			$id = $this->get_honeypot_field_id( $form );
		}

		$name = sprintf( 'input_%d', $id );

		/**
		 * Allow the honeypot input name to be overridden.
		 *
		 * @since 2.9.16
		 *
		 * @param string $name The honeypot input name.
		 * @param array  $form The form being rendered or validated.
		 */
		return apply_filters( 'gform_honeypot_input_name', $name, $form );
	}

	/**
	 * Creates the honeypot field object for the given form.
	 *
	 * @since 2.7
	 *
	 * @param array $form The form the honeypot field is to be created for.
	 *
	 * @return bool|\GF_Field Returns the honeypot field.
	 */
	public function get_honeypot_field( $form ) {
		$labels = $this->get_honeypot_labels();
		shuffle( $labels );

		$field_data = array(
			'type'        => 'honeypot',
			'label'       => $labels[ rand( 0, count( $labels ) - 1 ) ],
			'id'          => \GFFormsModel::get_next_field_id( $form['fields'] ),
			'cssClass'    => 'gform_validation_container',
			'description' => __( 'This field is for validation purposes and should be left unchanged.', 'gravityforms' ),
			'formId'      => absint( rgar( $form, 'id' ) ),
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
		$honeypot_labels = array(
			'Name',
			'Email',
			'Phone',
			'Comments',
			'URL',
			'Company',
			'X/Twitter',
			'Instagram',
			'Facebook',
			'LinkedIn',
		);

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
		if ( empty( $hash ) ) {
			return false;
		}

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
		$is_wp_dashboard = is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		$is_disabled     = ! rgar( $form, 'enableHoneypot' ) || \GFCommon::is_preview() || $is_wp_dashboard;

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

	/**
	 * Determines if the submission speed check is enabled.
	 *
	 * @since 2.9.21
	 *
	 * @param array $form The form being processed.
	 *
	 * @return bool
	 */
	public function is_speed_check_enabled( $form ) {
		return (bool) rgar( $form, 'enableSubmitSpeedCheck' );
	}

	/**
	 * Returns the submission speed threshold.
	 *
	 * @since 2.9.21
	 *
	 * @param array $form The form being processed.
	 *
	 * @return int
	 */
	public function get_submission_speed_threshold( $form ) {
		return absint( rgar( $form, 'submitSpeedCheckThreshold', 2000 ) );
	}

	/**
	 * Determines if strict mode is enabled for the submission speed check.
	 *
	 * @since 2.9.21
	 *
	 * @param array $form The form being processed.
	 *
	 * @return bool
	 */
	public function is_strict_submission_speed_mode_enabled( $form ) {
		return rgar( $form, 'submitSpeedCheckMode', 'normal' ) === 'strict';
	}

	/**
	 * Determines if the submission speed check is valid.
	 *
	 * @since 2.9.21
	 *
	 * @param array $form The current form object.
	 *
	 * @return array
	 */
	public function is_valid_submission_speed( $form ) {
		$result = array(
			'is_valid' => true,
			'message'  => '',
		);

		if ( ! $this->is_speed_check_enabled( $form ) ) {
			\GFCommon::log_debug( __METHOD__ . '(): Submission speed check is disabled.' );

			return $result;
		}

		$speeds = $this->get_submission_speeds_array( absint( rgar( $form, 'id' ) ) );
		if ( empty( $speeds ) ) {
			\GFCommon::log_debug( __METHOD__ . '(): Is speed check valid? No; gform_submission_speeds input is empty or invalid.' );
			$result['is_valid'] = false;
			$result['message']  = __( 'The gform_submission_speeds input is empty or invalid.', 'gravityforms' );

			return $result;
		}

		$threshold = $this->get_submission_speed_threshold( $form );
		$counts    = $this->check_submission_speeds( $speeds, $threshold );
		$min_count = $this->is_strict_submission_speed_mode_enabled( $form ) ? $counts['total'] : 1;
		$is_valid  = $counts['valid_count'] >= $min_count;

		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Is speed check valid? %s; %d of %d submissions met the threshold (%d ms). Min required: %d. All speeds: %s', ( $is_valid ? 'Yes' : 'No' ), $counts['valid_count'], $counts['total'], $threshold, $min_count, json_encode( $speeds ) ) );

		if ( ! $is_valid ) {
			$result['is_valid'] = false;
			// translators: %1$d: the number of submissions that met the speed check threshold. %2$d: the total number of submissions. %3$s: the threshold. %4$s: the minimum number of matches required. %5$s: all the recorded speeds.
			$result['message'] = sprintf( __( '%1$d of %2$d submissions met the speed check threshold (%3$d ms). Min required: %4$d. All speeds (ms): %5$s.', 'gravityforms' ), $counts['valid_count'], $counts['total'], $threshold, $min_count, $this->format_submission_speeds_for_note( $speeds ) );

			return $result;
		}

		return $result;
	}

	/**
	 * Formats the submission speeds for the entry note.
	 *
	 * @since 2.9.21
	 *
	 * @param array $speeds The recorded speeds.
	 *
	 * @return string
	 */
	public function format_submission_speeds_for_note( $speeds ) {
		if ( count( $speeds ) === 1 ) {
			return implode( ', ', rgar( $speeds, 1 ) );
		}

		$pages = array();

		foreach ( $speeds as $page_number => $page_speeds ) {
			// translators: %d: the page number. %s: the list of submission speeds for the page.
			$pages[] = sprintf( 'Page %d: %s', $page_number, implode( ', ', $page_speeds ) );
		}

		return implode( '; ', $pages );
	}

	/**
	 * Checks the submission speeds against the threshold.
	 *
	 * @since 2.9.21
	 *
	 * @param array $speeds    The recorded speeds.
	 * @param int   $threshold The submission speed threshold.
	 *
	 * @return array
	 */
	public function check_submission_speeds( $speeds, $threshold ) {
		$valid_count = 0;
		$total       = 0;

		foreach ( $speeds as $page_speeds ) {
			$total += count( $page_speeds );
			foreach ( $page_speeds as $speed ) {
				if ( $speed >= $threshold ) {
					++$valid_count;
				}
			}
		}

		return array(
			'total'       => $total,
			'valid_count' => $valid_count,
		);
	}

	/**
	 * Parses and sanitizes the submission speeds from the gform_submit_speeds input for the current form.
	 *
	 * @since 2.9.21
	 *
	 * @param int $form_id The current form ID.
	 *
	 * @return array
	 */
	public function get_submission_speeds_array( $form_id ) {
		if ( rgpost( 'is_submit_' . $form_id ) !== '1' ) {
			return array();
		}

		$json = rgpost( 'gform_submission_speeds' );
		if ( empty( $json ) ) {
			return array();
		}

		$array = json_decode( $json, true );
		if ( empty( $array['pages'] ) ) {
			return array();
		}

		$clean_array = array();

		foreach ( $array['pages'] as $page_number => $speeds ) {
			$page_number = absint( $page_number );
			if ( empty( $page_number ) || ! is_array( $speeds ) ) {
				continue;
			}
			$clean_array[ $page_number ] = array_map( 'absint', array_values( $speeds ) );
		}

		return $clean_array;
	}

	/**
	 * JSON encodes the submission speeds for the current form markup.
	 *
	 * @since 2.9.21
	 *
	 * @param int $form_id The current form ID.
	 *
	 * @return false|string
	 */
	public function get_submission_speeds_json( $form_id ) {
		return json_encode( array( 'pages' => $this->get_submission_speeds_array( $form_id ) ) );
	}

	/**
	 * Registers and saves the submission speeds to the entry meta, so the user can display it as an entries list column.
	 *
	 * Callback for the gform_entry_meta filter added via GF_Honeypot_Service_Provider::init().
	 *
	 * @since 2.9.21
	 *
	 * @param array $entry_meta The registered entry meta.
	 *
	 * @return array
	 */
	public function submission_speeds_entry_meta( $entry_meta ) {
		$entry_meta['submission_speeds'] = array(
			'label'                      => esc_html__( 'Submission Speed (ms)', 'gravityforms' ),
			'is_numeric'                 => false,
			'update_entry_meta_callback' => function ( $key, $entry, $form ) {
				$existing = rgar( $entry, 'submission_speeds' );
				if ( \GFCommon::is_entry_detail_edit() || ! rgblank( $existing ) ) {
					return $existing;
				}

				$speeds = $this->get_submission_speeds_array( absint( rgar( $form, 'id' ) ) );
				if ( empty( $speeds ) ) {
					return '';
				}

				return json_encode( $speeds );
			},
			'is_default_column'          => false,
		);

		return $entry_meta;
	}

	/**
	 * Formats the value for display in an entries list column.
	 *
	 * Callback for the gform_entries_field_value filter added via GF_Honeypot_Service_Provider::init().
	 *
	 * @since 2.9.21
	 *
	 * @param mixed|string $value    The escaped value of the field, entry property, or meta-key.
	 * @param int          $form_id  The ID of the current form.
	 * @param int|string   $field_id The field ID entry property, or meta-key.
	 * @param array        $entry    The current entry.
	 *
	 * @return mixed|string
	 */
	public function submission_speeds_entries_field_value( $value, $form_id, $field_id, $entry ) {
		if ( $field_id !== 'submission_speeds' || rgblank( $value ) ) {
			return $value;
		}

		$value = $this->get_submission_speeds_range( $entry );

		return $value ? esc_html( $value ) : '';
	}

	/**
	 * Returns the submission speeds range for the given entry.
	 *
	 * @since 2.9.21
	 *
	 * @param array $entry The current entry.
	 *
	 * @return string
	 */
	private function get_submission_speeds_range( $entry ) {
		$entry_value = rgar( $entry, 'submission_speeds' );
		if ( empty( $entry_value ) ) {
			return '';
		}

		$speeds = json_decode( $entry_value, true );
		if ( empty( $speeds ) ) {
			return '';
		}

		$all = array();
		foreach ( $speeds as $page_speeds ) {
			$all = array_merge( $all, $page_speeds );
		}

		if ( count( $all ) === 1 ) {
			return $all[0];
		}

		return min( $all ) . ' - ' . max( $all );
	}

	/**
	 * Displays the submission speeds on the entry detail page.
	 *
	 * Callback for the gform_entry_detail_meta_boxes filter added via GF_Honeypot_Service_Provider::init().
	 *
	 * @since 2.9.21
	 *
	 * @param array $meta_boxes The registered meta boxes.
	 * @param array $entry      The current entry.
	 *
	 * @return array
	 */
	public function submission_speeds_entry_detail_meta_box( $meta_boxes, $entry ) {
		$value = $this->get_submission_speeds_range( $entry );
		if ( empty( $value ) ) {
			return $meta_boxes;
		}

		$meta_boxes['submission_speeds'] = array(
			'title'    => esc_html__( 'Submission Speed (ms)', 'gravityforms' ),
			'callback' => function ( $args ) use ( $value ) {
				echo esc_html( $value );
			},
			'context'  => 'side',
		);

		return $meta_boxes;
	}

}
