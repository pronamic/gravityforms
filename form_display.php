<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GFFormDisplay {

	public static $submission              = array();
	public static $init_scripts            = array();
	public static $hooks_js_printed        = false;
	public static $sidebar_has_widget      = false;
	public static $submission_initiated_by = '';
	public static $processed               = array();

	const ON_PAGE_RENDER       = 1;
	const ON_CONDITIONAL_LOGIC = 2;

	const SUBMISSION_INITIATED_BY_WEBFORM        = 1;
	const SUBMISSION_INITIATED_BY_API            = 2;
	const SUBMISSION_INITIATED_BY_API_VALIDATION = 3;

	const SUBMISSION_METHOD_POSTBACK = 'postback';
	const SUBMISSION_METHOD_AJAX     = 'ajax';
	const SUBMISSION_METHOD_IFRAME   = 'iframe';
	const SUBMISSION_METHOD_CUSTOM   = 'custom';

	/**
	 * Starting point for the form submission process. Handles the following tasks: Form validation, save for later logic, entry creation, notification and confirmation.
	 *
	 * @since unknown
	 * @since 2.6.4 Added the $initiated_by param.
	 *
	 * @param int $form_id      The form ID being submitted.
	 * @param int $initiated_by What process initiated the form submission. Possible options are self::SUBMISSION_INITIATED_BY_WEBFORM = 1 or self::SUBMISSION_INITIATED_BY_API = 2.
	 */
	public static function process_form( $form_id, $initiated_by = self::SUBMISSION_INITIATED_BY_API ) {
		GFCommon::timer_start( __METHOD__ );
		GFCommon::log_debug( "GFFormDisplay::process_form(): Starting to process form (#{$form_id}) submission." );

		self::$submission_initiated_by = $initiated_by;

		$form = GFAPI::get_form( $form_id );

		$gform_pre_process_args = array( 'gform_pre_process', $form_id );
		if ( gf_has_filter( $gform_pre_process_args ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_process.' );
			/**
			 * Filter the form before GF begins to process the submission.
			 *
			 * @param array $form The Form Object
			 */
			$filtered_form = gf_apply_filters( array( 'gform_pre_process', $form['id'] ), $form );
			if ( $filtered_form !== null ) {
				$form = $filtered_form;
			}
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_process.' );
		}

		// Set files that have been uploaded to temp folder
		$files = GFFormsModel::set_uploaded_files( $form_id );

		//reading form metadata
		$form = self::maybe_add_review_page( $form );

		if ( ! rgar( $form, 'is_active' ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting. Form is inactive.' );

			return;
		}

		if ( rgar( $form, 'is_trash' ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting. Form has been trashed.' );

			return;
		}

		if ( GFCommon::form_requires_login( $form ) ) {
			if ( ! is_user_logged_in() ) {
				GFCommon::log_debug( __METHOD__ . '(): Aborting. User is not logged in.' );

				return;
			}

			// Bypass nonce check for requests coming from the REST API
			$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;
			if ( ! $is_rest_request ) {
				check_admin_referer( 'gform_submit_' . $form_id, '_gform_submit_nonce_' . $form_id );
			}
		}

		$lead = array();

		$field_values = RGForms::post( 'gform_field_values' );

		$confirmation_message = '';

		$source_page_number = self::get_source_page( $form_id );
		$page_number        = $source_page_number;
		$target_page        = self::get_target_page( $form, $page_number, $field_values );

		GFCommon::log_debug( "GFFormDisplay::process_form(): Source page number: {$source_page_number}. Target page number: {$target_page}." );

		$saving_for_later = rgpost( 'gform_save' ) ? true : false;

		$is_valid = true;

		$failed_validation_page = $page_number;

		//don't validate when going to previous page or saving for later
		if ( ! $saving_for_later && ( empty( $target_page ) || $target_page >= $page_number ) ) {
			$is_valid = self::validate( $form, $field_values, $page_number, $failed_validation_page );
		}

		$log_is_valid = $is_valid ? 'Yes' : 'No';
		GFCommon::log_debug( __METHOD__ . "(): After validation. Is submission valid? {$log_is_valid}." );

		// Upload files to temp folder when going to the next page or when submitting the form and it failed validation
		if ( $target_page > $page_number || $target_page == 0 ) {
			if ( ! empty( $_FILES ) && ! $saving_for_later ) {
				// When saving, ignore files with single file upload fields as they have not been validated.
				GFCommon::log_debug( 'GFFormDisplay::process_form(): Uploading files...' );
				// Uploading files to temporary folder.
				$files = self::upload_files( $form, $files );

				RGFormsModel::$uploaded_files[ $form_id ] = $files;
			}
		}

		// Load target page if it did not fail validation or if going to the previous page
		if ( ! $saving_for_later && $is_valid ) {
			$page_number = $target_page;
		} else {
			$page_number = $failed_validation_page;
		}

		$confirmation = '';
		if ( ( $is_valid && $page_number == 0 ) || $saving_for_later ) {

			// Make sure submit button isn't hidden by conditional logic.
			if ( GFFormsModel::is_submit_button_hidden( $form ) && ! $saving_for_later ) {
				// Ignore submission.
				return;
			}

			$ajax = isset( $_POST['gform_ajax'] );

			/**
			 * Adds support for aborting submission, displaying the confirmation page/text to the user. This filter is useful for Spam Filters that want to abort submissions that flagged as spam.
			 *
			 * @since 2.7
			 *
			 * @see https://docs.gravityforms.com/gform_abort_submission_with_confirmation/
			 *
			 * @param bool   $do_abort  The value being filtered. True to abort submission and display the confirmation. False to continue with submission. Defaults to false.
			 * @param array  $form         The current form object.
			 */
			$abort_with_confirmation = gf_apply_filters( array( 'gform_abort_submission_with_confirmation', $form['id'] ), false, $form );

			if ( $abort_with_confirmation ) {

				GFCommon::log_debug( 'GFFormDisplay::process_form(): Aborting early via gform_abort_submission_with_confirmation filter.' );

				// Display confirmation but doesn't process the form. Useful for spam filters.
				$confirmation = self::handle_confirmation( $form, $lead, $ajax );
				$is_valid     = false;
			} elseif ( ! $saving_for_later ) {

				GFCommon::log_debug( 'GFFormDisplay::process_form(): Submission is valid. Moving forward.' );

				$gform_pre_submission_args = array( 'gform_pre_submission', $form_id );
				if ( gf_has_action( $gform_pre_submission_args ) ) {
					GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_submission.' );
					/**
					 * Fires before form submission is handled
					 *
					 * Typically used to modify values before the submission is processed.
					 *
					 * @since 1.0
					 *
					 * @param array $form The Form object
					 */
					gf_do_action( $gform_pre_submission_args, $form );
					GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_submission.' );
				}

				$gform_pre_submission_filter_args = array( 'gform_pre_submission_filter', $form_id );
				if ( gf_has_filter( $gform_pre_submission_filter_args ) ) {
					GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_submission_filter.' );
					/**
					 * Allows the form object to be modified before the entry is saved.
					 *
					 * @since Unknown.
					 *
					 * @param array $form The form currently being processed.
					 */
					$form = gf_apply_filters( $gform_pre_submission_filter_args, $form );
					GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_submission_filter.' );
				}

				$confirmation = self::handle_submission( $form, $lead, $ajax );

				$gform_after_submission_args = array( 'gform_after_submission', $form_id );
				if ( gf_has_action( $gform_after_submission_args ) ) {
					GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_after_submission.' );
					/**
					 * Allows additional actions to be performed after successful form submission.
					 *
					 * @since 1.6
					 *
					 * @param array $lead The Entry object.
					 * @param array $form The Form object.
					 */
					gf_do_action( $gform_after_submission_args, $lead, $form );
					GFCommon::log_debug( __METHOD__ . '(): Completed gform_after_submission.' );
				}

			} elseif ( $saving_for_later ) {
				GFCommon::log_debug( 'GFFormDisplay::process_form(): Saving for later.' );
				$lead = GFFormsModel::get_current_lead();
				$form = self::update_confirmation( $form, $lead, 'form_saved' );

				$confirmation = rgar( $form['confirmation'], 'message' );
				$nl2br        = rgar( $form['confirmation'], 'disableAutoformat' ) ? false : true;
				$confirmation = GFCommon::replace_variables( $confirmation, $form, $lead, false, true, $nl2br );

				$form_unique_id = GFFormsModel::get_form_unique_id( $form_id );
				$ip             = rgars( $form, 'personalData/preventIP' ) ? '' : GFFormsModel::get_ip();
				$source_url     = GFFormsModel::get_current_page_url();
				$source_url     = esc_url_raw( $source_url );
				$resume_token   = rgpost( 'gform_resume_token' );
				$resume_token   = sanitize_key( $resume_token );
				$resume_token   = GFFormsModel::save_draft_submission( $form, $lead, $field_values, $page_number, $files, $form_unique_id, $ip, $source_url, $resume_token );

				$notifications_to_send = GFCommon::get_notifications_to_send( 'form_saved', $form, $lead );

				$log_notification_event = empty( $notifications_to_send ) ? 'No notifications to process' : 'Processing notifications';
				GFCommon::log_debug( "GFFormDisplay::process_form(): {$log_notification_event} for form_saved event." );

				foreach ( $notifications_to_send as $notification ) {
					if ( isset( $notification['isActive'] ) && ! $notification['isActive'] ) {
						GFCommon::log_debug( "GFFormDisplay::process_form(): Notification is inactive, not processing notification (#{$notification['id']} - {$notification['name']})." );
						continue;
					}
					$notification['message'] = self::replace_save_variables( $notification['message'], $form, $resume_token );
					GFCommon::send_notification( $notification, $form, $lead );
				}
				self::set_submission_if_null( $form_id, 'saved_for_later', true );
				self::set_submission_if_null( $form_id, 'resume_token', $resume_token );
				GFCommon::log_debug( 'GFFormDisplay::process_form(): Saved incomplete submission.' );

			}

			/**
			 * Allows the confirmation redirect header to be suppressed. Required by GFAPI::submit_form().
			 *
			 * @since 2.3
			 *
			 * @param bool $suppress_redirect
			 */
			$suppress_redirect = apply_filters( 'gform_suppress_confirmation_redirect', false );

			if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) && ! $suppress_redirect ) {
				header( "Location: {$confirmation["redirect"]}" );

				$gform_post_submission_args = array( 'gform_post_submission', $form_id );
				if ( gf_has_action( $gform_post_submission_args ) ) {
					GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_post_submission.' );
					/**
					 * Allows additional actions to be performed after form submission when the confirmation is a redirect.
					 *
					 * @param array $lead The Entry object.
					 * @param array $form The Form object.
					 */
					gf_do_action( $gform_post_submission_args, $lead, $form );
					GFCommon::log_debug( __METHOD__ . '(): Completed gform_post_submission.' );
				}
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing completed in %F seconds.', GFCommon::timer_end( __METHOD__ ) ) );
				exit;
			}
		}



		if ( ! isset( self::$submission[ $form_id ] ) ) {
			self::$submission[ $form_id ] = array();
		}

		self::set_submission_if_null( $form_id, 'is_valid', $is_valid );
		self::set_submission_if_null( $form_id, 'form', $form );
		self::set_submission_if_null( $form_id, 'lead', $lead );
		self::set_submission_if_null( $form_id, 'confirmation_message', $confirmation );
		self::set_submission_if_null( $form_id, 'page_number', $page_number );
		self::set_submission_if_null( $form_id, 'source_page_number', $source_page_number );

		$gform_post_process_args = array( 'gform_post_process', $form_id );
		if ( gf_has_action( $gform_post_process_args ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_post_process.' );
			/**
			 * Fires after the form processing is completed. Form processing happens when submitting a page on a multi-page form (i.e. going to the "Next" or "Previous" page), or
			 * when submitting a single page form.
			 *
			 * @param array $form               The Form Object
			 * @param int   $page_number        In a multi-page form, this variable contains the current page number.
			 * @param int   $source_page_number In a multi-page form, this parameters contains the number of the page that the submission came from.
			 *                                  For example, when clicking "Next" on page 1, this parameter will be set to 1. When clicking "Previous" on page 2, this parameter will be set to 2.
			 */
			gf_do_action( $gform_post_process_args, $form, $page_number, $source_page_number );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_post_process.' );
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Processing completed in %F seconds.', GFCommon::timer_end( __METHOD__ ) ) );
	}

	/**
	 * Get form object and insert review page, if necessary.
	 *
	 * @since 2.1.1.25 Added $partial_entry parameter.
	 * @since 1.9.15
	 *
	 * @param array $form          The current Form object.
	 * @param array $partial_entry The partial entry from the resumed incomplete submission. Defaults to an empty array.
	 *
	 * @return array The form object.
	 */
	public static function maybe_add_review_page( $form, $partial_entry = array() ) {

		/* Setup default review page parameters. */
		$review_page = array(
			'content'        => '',
			'cssClass'       => '',
			'is_enabled'     => false,
			'nextButton'     => array(
				'type'     => 'text',
				'text'     => __( 'Review Form', 'gravityforms' ),
				'imageUrl' => '',
				'imageAlt' => '',
			),
			'previousButton' => array(
				'type'     => 'text',
				'text'     => __( 'Previous', 'gravityforms' ),
				'imageUrl' => '',
				'imageAlt' => '',
			),
		);

		$gform_review_page_args = array( 'gform_review_page', rgar( $form, 'id' ) );
		if ( gf_has_filter( $gform_review_page_args ) ) {

			if ( empty( $partial_entry ) ) {
				// Prepare partial entry for review page.
				$partial_entry = GFFormsModel::get_current_lead();
			}

			/**
			 * GFFormsModel::create_lead() caches the field value and conditional logic visibility which can create
			 * issues when 3rd parties use hooks later in the process to modify the form. Let's flush the cache avoid
			 * any weirdness.
			 */
			GFCache::flush();

			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_review_page.' );
			/**
			 * A filter for setting up the review page.
			 *
			 * @since 2.4.5
			 *
			 * @param array       $review_page   The review page parameters
			 * @param array       $form          The current form object
			 * @param array|false $partial_entry The partial entry for the form or false on initial form display.
			 */
			$review_page = gf_apply_filters( $gform_review_page_args, $review_page, $form, $partial_entry );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_review_page.' );

			if ( ! rgempty( 'button_text', $review_page ) ) {
				$review_page['nextButton']['text'] = $review_page['button_text'];
			}

		}

		if ( rgar( $review_page, 'is_enabled' ) ) {
			$form = self::insert_review_page( $form, $review_page );
		}

		return $form;
	}

	private static function set_submission_if_null( $form_id, $key, $val ) {
		if ( ! isset( self::$submission[ $form_id ][ $key ] ) ) {
			self::$submission[ $form_id ][ $key ] = $val;
		}
	}

	private static function upload_files( $form, $files ) {

		$form_upload_path = GFFormsModel::get_upload_path( $form['id'] );
		GFCommon::log_debug( "GFFormDisplay::upload_files(): Upload path {$form_upload_path}" );

		//Creating temp folder if it does not exist
		$tmp_location  = GFFormsModel::get_tmp_upload_location( $form['id'] );
		$target_path   = $tmp_location['path'];
		if ( ! is_dir( $target_path ) && wp_mkdir_p( $target_path ) ) {
			GFCommon::recursive_add_index_file( $target_path );
		}

		foreach ( $form['fields'] as $field ) {
			$input_name = "input_{$field->id}";

			//skip fields that are not file upload fields or that don't have a file to be uploaded or that have failed validation
			$input_type = RGFormsModel::get_input_type( $field );
			if ( ! in_array( $input_type, array( 'fileupload', 'post_image' ) ) || $field->multipleFiles ) {
				continue;
			}

			/*if ( $field->failed_validation || empty( $_FILES[ $input_name ]['name'] ) ) {
				GFCommon::log_debug( "GFFormDisplay::upload_files(): Skipping field: {$field->label}({$field->id} - {$field->type})." );
				continue;
			}*/

			if ( $field->failed_validation ) {
				GFCommon::log_debug( "GFFormDisplay::upload_files(): Skipping field because it failed validation: {$field->label}({$field->id} - {$field->type})." );
				continue;
			}

			if ( empty( $_FILES[ $input_name ]['name'] ) ) {
				GFCommon::log_debug( "GFFormDisplay::upload_files(): Skipping field because a file could not be found: {$field->label}({$field->id} - {$field->type})." );
				continue;
			}

			$file_name = $_FILES[ $input_name ]['name'];
			if ( GFCommon::file_name_has_disallowed_extension( $file_name ) ) {
				GFCommon::log_debug( __METHOD__ . "(): Illegal file extension: {$file_name}" );
				continue;
			}

			$allowed_extensions = ! empty( $field->allowedExtensions ) ? GFCommon::clean_extensions( explode( ',', strtolower( $field->allowedExtensions ) ) ) : array();

			if ( ! empty( $allowed_extensions ) ) {
				if ( ! GFCommon::match_file_extension( $file_name, $allowed_extensions ) ) {
					GFCommon::log_debug( __METHOD__ . "(): The uploaded file type is not allowed: {$file_name}" );
					continue;
				}
			}

			/**
			 * Allows the disabling of file upload whitelisting
			 *
			 * @param bool false Set to 'true' to disable whitelisting.  Defaults to 'false'.
			 */
			$whitelisting_disabled = apply_filters( 'gform_file_upload_whitelisting_disabled', false );

			if ( empty( $allowed_extensions ) && ! $whitelisting_disabled ) {
				// Whitelist the file type

				$valid_file_name = GFCommon::check_type_and_ext( $_FILES[ $input_name ], $file_name );

				if ( is_wp_error( $valid_file_name ) ) {
					GFCommon::log_debug( __METHOD__ . "(): The uploaded file type is not allowed: {$file_name}" );
					continue;
				}
			}

			$file_info = RGFormsModel::get_temp_filename( $form['id'], $input_name );
			GFCommon::log_debug( 'GFFormDisplay::upload_files(): Temp file info: ' . print_r( $file_info, true ) );

			if ( $file_info && move_uploaded_file( $_FILES[ $input_name ]['tmp_name'], $target_path . $file_info['temp_filename'] ) ) {
				GFFormsModel::set_permissions( $target_path . $file_info['temp_filename'] );
				$files[ $input_name ] = $file_info['uploaded_filename'];
				GFCommon::log_debug( "GFFormDisplay::upload_files(): File uploaded successfully: {$file_info['uploaded_filename']}" );
			} else {
				GFCommon::log_error( "GFFormDisplay::upload_files(): File could not be uploaded: tmp_name: {$_FILES[ $input_name ]['tmp_name']} - target location: " . $target_path . $file_info['temp_filename'] );
			}
		}
		return $files;
	}

	public static function get_state( $form, $field_values ) {
		$fields = array();
		foreach ( $form['fields'] as $field ) {
			/* @var GF_Field $field */
			if ( $field->is_state_validation_supported() ) {
				$value = RGFormsModel::get_field_value( $field, $field_values, false );
				$value = $field->get_value_default_if_empty( $value );

				switch ( $field->get_input_type() ) {
					case 'calculation' :
					case 'singleproduct' :
					case 'hiddenproduct' :
						$price = ! is_array( $value ) || empty( $value[ $field->id . '.2' ] ) ? $field->basePrice : $value[ $field->id . '.2' ];
						if ( empty( $price ) ) {
							$price = 0;
						}

						$price = GFCommon::to_number( $price );

						$product_name = ! is_array( $value ) || empty( $value[ $field->id . '.1' ] ) ? $field->label : $value[ $field->id . '.1' ];

						$fields[ $field->id . '.1' ] = wp_hash( GFFormsModel::maybe_trim_input( $product_name, $form['id'], $field ) );
						$fields[ $field->id . '.2' ] = wp_hash( GFFormsModel::maybe_trim_input( $price, $form['id'], $field ) );
						break;

					case 'singleshipping' :
						$price = ! empty( $value ) ? $value : $field->basePrice;
						$price = ! empty( $price ) ? GFCommon::to_number( $price ) : 0;

						$fields[ $field->id ] = wp_hash( GFFormsModel::maybe_trim_input( $price, $form['id'], $field ) );
						break;
					case 'radio' :
					case 'select' :
						$fields[ $field->id ] = array();
						foreach ( $field->choices as $choice ) {
							$field_value = ! empty( $choice['value'] ) || $field->enableChoiceValue ? $choice['value'] : $choice['text'];
							if ( $field->enablePrice ) {
								$price = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
								$field_value .= '|' . $price;
							}

							$fields[ $field->id ][] = wp_hash( GFFormsModel::maybe_trim_input( $field_value, $form['id'], $field ) );
						}
						break;
					case 'checkbox' :
						$index = 1;
						foreach ( $field->choices as $choice ) {
							$field_value = ! empty( $choice['value'] ) || $field->enableChoiceValue ? $choice['value'] : $choice['text'];
							if ( $field->enablePrice ) {
								$price = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
								$field_value .= '|' . $price;
							}

							// Checkboxes for Choice fields already have a unique ID.
							if ( $field->has_persistent_choices() ) {
								$input_id = $field->get_input_id_from_choice_key( $choice['key'] );
								$fields[ $input_id ] = wp_hash( GFFormsModel::maybe_trim_input( $field_value, $form['id'], $field ) );
							} else {
								if ( $index % 10 == 0 ) { //hack to skip numbers ending in 0. so that 5.1 doesn't conflict with 5.10
									$index ++;
								}
								$fields[ $field->id . '.' . $index ++ ] = wp_hash( GFFormsModel::maybe_trim_input( $field_value, $form['id'], $field ) );
							}
						}
						break;
					case 'consent':
						$text        = $field->checkboxLabel;
						$description = GFFormsModel::get_latest_form_revisions_id( $form['id'] );

						$fields[ $field->id . '.1' ] = wp_hash( 1 );
						$fields[ $field->id . '.2' ] = wp_hash( GFFormsModel::maybe_trim_input( $text, $form['id'], $field ) );
						$fields[ $field->id . '.3' ] = wp_hash( GFFormsModel::maybe_trim_input( $description, $form['id'], $field ) );
						break;
				}
			}
		}

		$hash     = json_encode( $fields );
		$checksum = wp_hash( crc32( $hash ) );

		return base64_encode( json_encode( array( $hash, $checksum ) ) );

	}

	/**
	 * Determine if form has any pages.
	 *
	 * @access private
	 *
	 * @param array $form The form object
	 *
	 * @return bool If form object has any pages
	 */
	private static function has_pages( $form ) {
		return GFCommon::has_pages( $form );
	}

	private static function has_character_counter( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->maxLength && ! $field->inputMask ) {
				return true;
			}
		}

		return false;
	}

	private static function has_placeholder( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->placeholder != '' ) {
				return true;
			}
			if ( is_array( $field->inputs ) ) {
				foreach ( $field->inputs as $input ) {
					if ( rgar( $input, 'placeholder' ) != '' ) {
						return true;
					}
				}
			}
		}

		return false;
	}


	private static function has_enhanced_dropdown( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( in_array( RGFormsModel::get_input_type( $field ), array( 'select', 'multiselect' ) ) && $field->enableEnhancedUI ) {
				return true;
			}
		}

		return false;
	}

	private static function has_password_strength( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'password' && $field->passwordStrengthEnabled ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if form has a Password field with the Password Visibility Toggle enabled.
	 *
	 * @since 2.4.15
	 *
	 * @param array $form Form object.
	 *
	 * @return bool
	 */
	private static function has_password_visibility( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'password' && $field->passwordVisibilityEnabled ) {
				return true;
			}
		}

		return false;

	}

	private static function has_other_choice( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'radio' && $field->enableOtherChoice ) {
				return true;
			}
		}

		return false;
	}


	public static function get_target_page( $form, $current_page, $field_values ) {
		$page_number = RGForms::post( "gform_target_page_number_{$form['id']}" );
		$page_number = ! is_numeric( $page_number ) ? 1 : $page_number;

		// cast to an integer since page numbers can only be whole numbers
		$page_number = absint( $page_number );

		$direction = $page_number >= $current_page ? 1 : - 1;

		//Finding next page that is not hidden by conditional logic
		while ( RGFormsModel::is_page_hidden( $form, $page_number, $field_values ) ) {
			$page_number += $direction;
		}

		//If all following pages are hidden, submit the form
		if ( $page_number > self::get_max_page_number( $form ) ) {
			$page_number = 0;
		}

		/**
		 * Modify the target page.
		 *
		 * @since 2.1.2.13
		 *
		 * @see https://docs.gravityforms.com/gform_target_page/
		 *
		 * @param int   $page_number  The target page number.
		 * @param array $form         The current form object.
		 * @param int   $current_page The page that was submitted.
		 * @param array $field_values Dynamic population values that were provided when loading the form.
		 */
		return gf_apply_filters( array( 'gform_target_page', $form['id'] ), $page_number, $form, $current_page, $field_values );
	}

	public static function get_source_page( $form_id ) {
		$page_number = RGForms::post( "gform_source_page_number_{$form_id}" );

		return ! is_numeric( $page_number ) ? 1 : $page_number;
	}

	public static function set_current_page( $form_id, $page_number ) {
		self::$submission[ $form_id ]['page_number'] = $page_number;
	}

	public static function get_current_page( $form_id ) {
		$page_number = isset( self::$submission[ $form_id ] ) ? intval( self::$submission[ $form_id ]['page_number'] ) : 1;

		return $page_number;
	}

	private static function is_page_active( $form_id, $page_number ) {
		return intval( self::get_current_page( $form_id ) ) == intval( $page_number );
	}

	/**
	 * Determine if the last page for the current form object is being submitted or rendered (depending on the provided $mode).
	 *
	 * @param  array  $form A Gravity Forms form object.
	 * @param  string $mode Mode to check for: 'submit' or 'render'
	 *
	 * @return boolean
	 */
	public static function is_last_page( $form, $mode = 'submit' ) {

		$page_number  = self::get_source_page( $form['id'] );
		$field_values = GFForms::post( 'gform_field_values' );
		$target_page  = self::get_target_page( $form, $page_number, $field_values );

		if ( $mode == 'render' ) {
			$is_valid     = rgars( self::$submission, "{$form['id']}/is_valid" );
			$is_last_page = ( $is_valid && $target_page == self::get_max_page_number( $form ) ) || (string) $target_page === '0';
		} else {
			$is_last_page = (string) $target_page === '0';
		}

		return $is_last_page;
	}

	/**
	 * Returns the entry limit date range for the given period.
	 *
	 * @since unknown
	 * @since 2.4.15 Updated the day period to use the local time.
	 *
	 * @param string $period The eriod for the entry limit.
	 *
	 * @return array
	 */
	private static function get_limit_period_dates( $period ) {
		if ( empty( $period ) ) {
			return array( 'start_date' => null, 'end_date' => null );
		}

		switch ( $period ) {
			case 'day':
				return array(
					'start_date' => current_time( 'Y-m-d' ),
					'end_date'   => current_time( 'Y-m-d 23:59:59' ),
				);
				break;

			case 'week':
				return array(
					'start_date' => gmdate( 'Y-m-d', strtotime( 'Monday this week' ) ),
					'end_date'   => gmdate( 'Y-m-d 23:59:59', strtotime( 'Sunday this week' ) ),
				);
				break;

			case 'month':
				$month_start = gmdate( 'Y-m-1');
				return array(
					'start_date' => $month_start,
					'end_date'   => gmdate( 'Y-m-d H:i:s', strtotime( "{$month_start} +1 month -1 second" ) ),
				);
				break;

			case 'year':
				return array(
					'start_date' => gmdate( 'Y-1-1' ),
					'end_date'   => gmdate( 'Y-12-31 23:59:59' ),
				);
				break;
		}
	}

	/**
	 * Get the slug for the form's theme
	 *
	 * @since 2.7
	 *
	 * @param $form
	 *
	 * @return string The theme slug
	 */
	public static function get_form_theme_slug( $form ) {

		$form            = (array) $form;
		$slug            = '';
		$is_wp_dashboard = is_admin() && ! defined( 'DOING_AJAX' );

		// If form is legacy, return that early to avoid calculating orbital styles.
		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			$slug = 'legacy';
		} elseif ( $is_wp_dashboard || GFCommon::is_preview() || GFCommon::is_form_editor() ) {
			$slug = 'orbital';
		} elseif ( isset( $form['theme'] ) ) {
			$slug = $form['theme'];
		} else {
			$instance       = rgar( $form, 'page_instance', 0 );
			$all_blocks     = apply_filters( 'gform_form_block_attribute_values', array() );
			$block_settings = rgars( $all_blocks, rgar( $form, 'id', 0 ) . '/' . $instance, array() );

			// If a theme is selected for this block or shortcode, return that.
			if ( isset( $block_settings['theme'] ) ) {
				$slug = $block_settings['theme'];
			}
		}

		// allow using the short version in shortcodes.
		if ( $slug == 'gravity' ) {
			$slug = 'gravity-theme';
		}

		if ( empty( $slug ) || ! in_array( $slug, array( 'legacy', 'gravity-theme', 'orbital' ) ) ) {
			$slug = GFForms::get_default_theme();
		}

		/**
		 * Allow users to filter the theme slug returned for a given form.
		 *
		 * @since 2.7
		 *
		 * @param string $slug The current theme slug for the form.
		 * @param array  $form The form object being processed.
		 *
		 * @return string
		 */
		return apply_filters( 'gform_form_theme_slug', $slug, $form );
	}

	/**
	 * Determines which themes to enqueue based on the form context and current page.
	 *
	 * @since 2.9
	 *
	 * @param  array        $form       Current form object.
	 * @param  string|array $field_types Optional. If specified, only load themes for forms with fields of these types. Can be a string with a single field type or an array of strings with multiple field types.
	 *
	 * @return array|string[] Returns an array of theme slugs to enqueue
	 */
	public static function get_themes_to_enqueue( $form, $field_types = '' ) {

		// In the form editor, enqueue the theme framework.
		if ( GFCommon::is_form_editor() ) {
			return array( 'orbital' );
		}

		// Enqueues Gravity and Theme Framework themes in the block editor
		if ( GFCommon::is_block_editor_page() ) {
			return array( 'gravity-theme', 'orbital' );
		}

		// Enqueues Gravity theme in the entry detail views
		if ( GFCommon::is_entry_detail() ) {
			return array( 'gravity-theme' );
		}

		// On pages other than the form editor and block editor, if a field type is specified, only enqueue a theme if the form has the specified field type.
		if ( ! empty( $field_types ) && ! GFCommon::get_fields_by_type( $form, $field_types ) ) {
			return array();
		}

		$selected_theme = self::get_form_theme_slug( $form );
		return array( $selected_theme );
	}

	/**
	 * Fire the post render events for a form instance when the form is visible on the page.
	 *
	 * @since 2.8.4
	 *
	 * @param $form_id
	 * @param $current_page
	 *
	 * @return string
	 */
	public static function post_render_script( $form_id, $current_page = 'current_page' ) {
		$post_render_script = '
			jQuery(document).trigger("gform_pre_post_render", [{ formId: "' . $form_id . '", currentPage: "' . $current_page . '", abort: function() { this.preventDefault(); } }]);
	        
	        if (event && event.defaultPrevented) {
            	    return; 
        	}
	        const gformWrapperDiv = document.getElementById( "gform_wrapper_' . $form_id . '" );
	        if ( gformWrapperDiv ) {
	            const visibilitySpan = document.createElement( "span" );
	            visibilitySpan.id = "gform_visibility_test_' . $form_id . '";
	            gformWrapperDiv.insertAdjacentElement( "afterend", visibilitySpan );
	        }
	        const visibilityTestDiv = document.getElementById( "gform_visibility_test_' . $form_id . '" );
	        let postRenderFired = false;
	        
	        function triggerPostRender() {
	            if ( postRenderFired ) {
	                return;
	            }
	            postRenderFired = true;
	            jQuery( document ).trigger( \'gform_post_render\', [' . $form_id . ', ' . $current_page . '] );
	            gform.utils.trigger( { event: \'gform/postRender\', native: false, data: { formId: ' . $form_id . ', currentPage: ' . $current_page . ' } } );
	            gform.utils.trigger( { event: \'gform/post_render\', native: false, data: { formId: ' . $form_id . ', currentPage: ' . $current_page . ' } } );
	            if ( visibilityTestDiv ) {
	                visibilityTestDiv.parentNode.removeChild( visibilityTestDiv );
	            }
	        }
	
	        function debounce( func, wait, immediate ) {
	            var timeout;
	            return function() {
	                var context = this, args = arguments;
	                var later = function() {
	                    timeout = null;
	                    if ( !immediate ) func.apply( context, args );
	                };
	                var callNow = immediate && !timeout;
	                clearTimeout( timeout );
	                timeout = setTimeout( later, wait );
	                if ( callNow ) func.apply( context, args );
	            };
	        }
	
	        const debouncedTriggerPostRender = debounce( function() {
	            triggerPostRender();
	        }, 200 );
	
	        if ( visibilityTestDiv && visibilityTestDiv.offsetParent === null ) {
	            const observer = new MutationObserver( ( mutations ) => {
	                mutations.forEach( ( mutation ) => {
	                    if ( mutation.type === \'attributes\' && visibilityTestDiv.offsetParent !== null ) {
	                        debouncedTriggerPostRender();
	                        observer.disconnect();
	                    }
	                });
	            });
	            observer.observe( document.body, {
	                attributes: true,
	                childList: false,
	                subtree: true,
	                attributeFilter: [ \'style\', \'class\' ],
	            });
	        } else {
	            triggerPostRender();
	        }
	    ';

		$post_render_script = gf_apply_filters( array( 'gform_post_render_script', $form_id ), $post_render_script, $form_id, $current_page );

		return str_replace( [ "\t", "\n", "\r" ], '', $post_render_script );
	}


	/**
	 * Get a form for display.
	 *
	 * @since unknown
	 * @since 2.7.15 Added the $form_theme and $style_settings parameters.
	 *
	 * @param int    $form_id The id of the form.
	 * @param bool   $display_title Whether to display the form title.
	 * @param bool   $display_description Whether to display the form description.
	 * @param bool   $force_display Whether to force the form to display even if it is inactive.
	 * @param array  $field_values Array of field values.
	 * @param bool   $ajax Whether ajax is enabled.
	 * @param int    $tabindex Tabindex for the form.
	 * @param string $form_theme Form theme slug.
	 * @param string $style_settings JSON-encoded style settings. Passing false will bypass the gform_default_styles filter.
	 *
	 * @return mixed|string|WP_Error
	 */
	public static function get_form( $form_id, $display_title = true, $display_description = true, $force_display = false, $field_values = null, $ajax = false, $tabindex = 0, $form_theme = null, $style_settings = null ) {
		GFCommon::timer_start( __METHOD__ );

		/**
		 * Provides the ability to modify the options used to display the form
		 *
		 * @param array An array of Form Arguments when adding it to a page/post (Like the ID, Title, AJAX or not, etc)
		 */
		$form_args = apply_filters( 'gform_form_args', compact( 'form_id', 'display_title', 'display_description', 'force_display', 'field_values', 'ajax', 'tabindex' ) );

		// The submission_method property can be set in the gform_form_args filter to specify how the form should be submitted.
		// Supported values are: self::SUBMISSION_METHOD_AJAX (for true ajax submission), self::SUBMISSION_METHOD_POSTBACK (for standard form submission), self::SUBMISSION_METHOD_IFRAME (for the legacy iframe-based ajax submission ) or self::SUBMISSION_METHOD_CUSTOM (for submissions that will be triggered by third party code. i.e. Stripe Add-On).
		// It is optional, but if set, will override the $ajax property.
		if ( isset( $form_args['submission_method'] ) ) {
			$form_args['ajax'] = $form_args['submission_method'] == self::SUBMISSION_METHOD_IFRAME;
		} elseif ( $form_args ) {
			$form_args['submission_method'] = $form_args['ajax'] ? self::SUBMISSION_METHOD_IFRAME : self::SUBMISSION_METHOD_POSTBACK;
		}

		if ( empty( $form_args['form_id'] ) ) {
			return self::get_form_not_found_html( $form_id, $ajax );
		}

		$form_args['display_title']       = ! isset( $form_args['display_title'] ) || ! empty( $form_args['display_title'] );
		$form_args['display_description'] = ! isset( $form_args['display_description'] ) || ! empty( $form_args['display_description'] );

		// phpcs:ignore
		extract( $form_args );

		//looking up form id by form name
		if ( ! is_numeric( $form_id ) ) {
			$form_title = $form_id;
			$form_id    = GFFormsModel::get_form_id( $form_title );
			if ( $form_id === 0 ) {
				return self::get_form_not_found_html( $form_title, $ajax );
			}
		}

		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return self::get_form_not_found_html( $form_id, $ajax );
		}

		if ( ! isset( self::$processed[ $form_id ] ) ) {
			self::$processed[ $form_id ] = 0;
		}

		$form['page_instance'] = self::$processed[ $form_id ];
		self::$processed[ $form_id ]++;

		// Setting form style and theme
		$form = self::set_form_styles( $form, $style_settings, $form_theme );

		$action = remove_query_arg( 'gf_token' );

		if ( rgpost( 'gform_send_resume_link' ) == $form_id ) {
			$save_email_confirmation = self::handle_save_email_confirmation( $form, $ajax );
			if ( is_wp_error( $save_email_confirmation ) ) { // Failed email validation
				$resume_token               = rgpost( 'gform_resume_token' );
				$resume_token = sanitize_key( $resume_token );
				$incomplete_submission_info = GFFormsModel::get_draft_submission_values( $resume_token );
				if ( $incomplete_submission_info['form_id'] == $form_id ) {
					$submission_details_json = $incomplete_submission_info['submission'];
					$submission_details      = json_decode( $submission_details_json, true );
					$partial_entry           = $submission_details['partial_entry'];
					$form                    = self::update_confirmation( $form, $partial_entry, 'form_saved' );
					$confirmation_message    = rgar( $form['confirmation'], 'message' );
					$nl2br                   = rgar( $form['confirmation'], 'disableAutoformat' ) ? false : true;
					$confirmation_message    = GFCommon::replace_variables( $confirmation_message, $form, $partial_entry, false, true, $nl2br );

					return self::handle_save_confirmation( $form, $resume_token, $confirmation_message, $ajax );
				}
			} else {
				return $save_email_confirmation;
			}
		}

		$is_postback          = false;
		$is_valid             = true;
		$confirmation_message = '';

		//If form was submitted, read variables set during form submission procedure
		$submission_info = isset( self::$submission[ $form_id ] ) ? self::$submission[ $form_id ] : false;

		if ( rgar( $submission_info, 'saved_for_later' ) == true ) {
			$resume_token         = $submission_info['resume_token'];
			$confirmation_message = rgar( $submission_info, 'confirmation_message' );

			return self::handle_save_confirmation( $form, $resume_token, $confirmation_message, $ajax );
		}

		$partial_entry = $submitted_values = $review_page_done = false;
		if ( isset( $_GET['gf_token'] ) ) {
			$incomplete_submission_info = GFFormsModel::get_draft_submission_values( $_GET['gf_token'] );
			if ( rgar( $incomplete_submission_info, 'form_id' ) == $form_id ) {
				$submission_details_json                  = $incomplete_submission_info['submission'];
				$submission_details                       = json_decode( $submission_details_json, true );
				$partial_entry                            = $submission_details['partial_entry'];
				$submitted_values                         = $submission_details['submitted_values'];
				$field_values                             = $submission_details['field_values'];
				GFFormsModel::$unique_ids[ $form_id ]     = $submission_details['gform_unique_id'];
				GFFormsModel::$uploaded_files[ $form_id ] = $submission_details['files'];
				self::set_submission_if_null( $form_id, 'resuming_incomplete_submission', true );
				self::set_submission_if_null( $form_id, 'form_id', $form_id );

				$form             = self::maybe_add_review_page( $form, $partial_entry );
				$review_page_done = true;

				$max_page_number = self::get_max_page_number( $form );
				$page_number     = $submission_details['page_number'];
				if ( $page_number > 1 && $max_page_number > 0 && $page_number > $max_page_number ) {
					$page_number = $max_page_number;
				}
				self::set_submission_if_null( $form_id, 'page_number', $page_number );
			}
		}

		if ( ! $review_page_done && $form !== false ) {
			$form = self::maybe_add_review_page( $form );
		}

		if ( ! is_array( $partial_entry ) ) {

			/**
			 * A filter that allows disabling of the form view counter
			 *
			 * @param int $form_id The Form ID to filter when disabling the form view counter
			 * @param bool Default set to false (view counter enabled), can be set to true to disable the counter
			 */
			$view_counter_disabled = gf_apply_filters( array( 'gform_disable_view_counter', $form_id ), false );

			if ( $submission_info ) {
				if ( $submission_info['form'] ) {
					$submission_info['form']['page_instance'] = rgar( $form, 'page_instance', 0 );
				}
				$is_postback          = true;
				$is_valid             = rgar( $submission_info, 'is_valid' ) || rgar( $submission_info, 'is_confirmation' );
				$form                 = self::set_form_styles( $submission_info['form'], $style_settings, $form_theme );
				$lead                 = $submission_info['lead'];
				$confirmation_message = rgget( 'confirmation_message', $submission_info );

				if ( $is_valid && ! rgar( $submission_info, 'is_confirmation' ) ) {

					if ( $submission_info['page_number'] == 0 ) {
                        /**
                         * Fired after form submission
                         *
                         * @param array $lead The Entry object
                         * @param array $form The Form object
                         */
						gf_do_action( array( 'gform_post_submission', $form['id'] ), $lead, $form );
					} else {
                        /**
                         * Fired after the page changes on a multi-page form
                         *
                         * @param array $form                                  The Form object
                         * @param int   $submission_info['source_page_number'] The page that was submitted
                         * @param int   $submission_info['page_number']        The page that the user is being sent to
                         */
						gf_do_action( array( 'gform_post_paging', $form['id'] ), $form, $submission_info['source_page_number'], $submission_info['page_number'] );
					}
				}
			} elseif ( ! current_user_can( 'administrator' ) && ! $view_counter_disabled ) {
				RGFormsModel::insert_form_view( $form_id );
			}
		}

		// Running $form through the gform_pre_render filter.
		$form = self::gform_pre_render( $form, 'form_display', $ajax, $field_values );

		if ( empty( $form ) ) {
			return self::get_form_not_found_html( $form_id, $ajax );
		}

		$has_pages = self::has_pages( $form );

		//calling tab index filter
		GFCommon::$tab_index = gf_apply_filters( array( 'gform_tabindex', $form_id ), $tabindex, $form );

		//Don't display inactive forms
		if ( ! $force_display && ! $is_postback ) {

			$form_info = RGFormsModel::get_form( $form_id );
			if ( empty( $form_info ) || ! $form_info->is_active ) {
				return '';
			}

			// If form requires login, check if user is logged in
			if ( GFCommon::form_requires_login( $form ) ) {
				if ( ! is_user_logged_in() ) {
					return empty( $form['requireLoginMessage'] ) ? '<p>' . esc_html__( 'Sorry. You must be logged in to view this form.', 'gravityforms' ) . '</p>' : '<p>' . GFCommon::gform_do_shortcode( $form['requireLoginMessage'] ) . '</p>';
				}
			}
		}

		// show the form regardless of the following validations when force display is set to true
		if ( ! $force_display || $is_postback ) {

			$form_schedule_validation = self::validate_form_schedule( $form );

			// if form schedule validation fails AND this is not a postback, display the validation error
			// if form schedule validation fails AND this is a postback, make sure is not a valid submission (enables display of confirmation message)
			if ( $form_schedule_validation && ! $is_postback ) {
				return $form_schedule_validation;
			} elseif ( $form_schedule_validation && $is_postback && ! $is_valid ) {
				return self::get_ajax_postback_html( $form_schedule_validation );
			}

			$entry_limit_validation = self::validate_entry_limit( $form );

			// refer to form schedule condition notes above
			if ( $entry_limit_validation && ! $is_postback ) {
				return $entry_limit_validation;
			} elseif ( $entry_limit_validation && $is_postback && ! $is_valid ) {
				return self::get_ajax_postback_html( $entry_limit_validation );
			}

		}

		$form_string = '';

		//When called via a template, this will enqueue the proper scripts
		//When called via a shortcode, this will be ignored (too late to enqueue), but the scripts will be enqueued via the enqueue_scripts event
		self::enqueue_form_scripts( $form, $ajax );

		$is_form_editor       = GFCommon::is_form_editor();
		$is_entry_detail      = GFCommon::is_entry_detail();
		$is_admin             = $is_form_editor || $is_entry_detail;
		$should_render_hidden = self::has_conditional_logic( $form ) && rgar( rgget( 'attributes' ), 'formPreview' ) !== 'true';

		if ( empty( $confirmation_message ) ) {
			$wrapper_css_class = GFCommon::get_browser_class() . ' gform_wrapper';

			if ( ! $is_valid ) {
				$wrapper_css_class .= ' gform_validation_error';
			}

			$form_css_class = esc_attr( rgar( $form, 'cssClass' ) );

			//Hiding entire form if conditional logic is on to prevent 'hidden' fields from blinking. Form will be set to visible in the conditional_logic.php after the rules have been applied.

			$style = $should_render_hidden ? "style='display:none'" : '';

			// Split form CSS class by spaces and apply wrapper to each.
			$custom_wrapper_css_class = '';
			if ( ! empty( $form_css_class ) ) {

				// Separate the CSS classes.
				$form_css_classes = explode( ' ', $form_css_class );
				$form_css_classes = array_filter( $form_css_classes );

				// Append _wrapper to each class.
				foreach ( $form_css_classes as &$wrapper_class ) {
					$wrapper_class .= '_wrapper';
				}

				// Merge back into a string.
				$custom_wrapper_css_class = ' ' . implode( ' ', $form_css_classes );

			}

			$page_instance = isset( $form['page_instance'] ) ? "data-form-index='{$form['page_instance']}'" : null;
			$form_theme    = GFFormDisplay::get_form_theme_slug( $form );

			$form_string .= "
                <div class='{$wrapper_css_class}{$custom_wrapper_css_class}' data-form-theme='{$form_theme}' {$page_instance} id='gform_wrapper_$form_id' " . $style . '>';

			/**
			 * Allows markup to be added directly after the opening form wrapper.
			 *
			 * @since 2.7
			 *
			 * @param string $markup The current string to append.
			 * @param array  $form   The form being displayed.
			 *
			 * @return string
			 */
			$form_string .= gf_apply_filters( array( 'gform_form_after_open', $form_id ), '', $form );

			$anchor      = self::get_anchor( $form, $ajax );
			$form_string .= $anchor['tag'];
			$action      .= $anchor['id'];

			$target = $ajax ? "target='gform_ajax_frame_{$form_id}'" : '';

			$form_css_class = ! empty( $form['cssClass'] ) ? "class='{$form_css_class}'" : '';

			if ( $is_postback && ! $is_valid ) {
				$show_summary = rgar( $form, 'validationSummary', false );
				// Generate validation heading message and errors list markup, append to form string.
				$form_string .= self::get_validation_errors_markup( $form, $submitted_values, $show_summary );
			}

			$required_indicator_type = rgar( $form, 'requiredIndicator', 'text' );
			$display_required_legend = GFCommon::has_required_field( $form ) && ! GFCommon::is_legacy_markup_enabled( $form ) && 'text' !== $required_indicator_type;

			if ( ( $display_title || $display_description ) || $display_required_legend ) {
				$gform_title_open  = GFCommon::is_legacy_markup_enabled( $form ) ? '<h3 class="gform_title">' : '<h2 class="gform_title">';
				$gform_title_close = GFCommon::is_legacy_markup_enabled( $form ) ? '</h3>' : '</h2>';

				$form_string .= "
                        <div class='gform_heading'>";
				if ( $display_title ) {
					$form_string .= "
                            {$gform_title_open}" . rgar( $form, 'title' ) . $gform_title_close;
				}
				if ( $display_description ) {
					$form_string .= "
                            <p class='gform_description'>" . rgar( $form, 'description' ) . '</p>';
				}

				if ( $display_required_legend ) {
					/**
					 * Modify the legend displayed at the bottom of the form header which explains how required fields are indicated.
					 *
					 * @since 2.5
					 *
					 * @param string $message The required indicator legend.
					 * @param array  $form    The current Form.
					 */
					$required_legend = gf_apply_filters(
						array( 'gform_required_legend', $form['id'] ),
						/* Translators: the text or symbol that indicates a field is required */
						sprintf( esc_html__( '"%s" indicates required fields', 'gravityforms' ), GFFormsModel::get_required_indicator( $form_id ) ),
						$form
					);
					$form_string .= "
							<p class='gform_required_legend'>{$required_legend}</p>";
				}
				$form_string .= '
                        </div>';
			}

			$action       = esc_url( $action );
			$form_string .= gf_apply_filters( array( 'gform_form_tag', $form_id ), "<form method='post' enctype='multipart/form-data' {$target} id='gform_{$form_id}' {$form_css_class} action='{$action}' data-formid='{$form_id}' novalidate>", $form );

			// If Save and Continue token was provided but expired/invalid, display error message.
			if ( isset( $_GET['gf_token'] ) && ! is_array( $incomplete_submission_info ) ) {

				/**
				 * Modify the error message displayed when an expired/invalid Save and Continue link is used.
				 *
				 * @since 2.4
				 *
				 * @param string $message Save & Continue expired/invalid link error message.
				 * @param array  $form    The current Form object.
				 */
				$savecontinue_expired_message = gf_apply_filters( array(
					'gform_savecontinue_expired_message',
					$form['id'],
				), esc_html__( 'Save and Continue link used is expired or invalid.', 'gravityforms' ), $form );

				// If message is not empty, add to form string.
				if ( ! empty( $savecontinue_expired_message ) ) {
					$form_string .= sprintf(
						'<div class="validation_error gform_validation_error">%s</div>',
						$savecontinue_expired_message
					);
				}

			}

			/* If the form was submitted, has multiple pages and is invalid, set the current page to the first page with an invalid field. */
			if ( $has_pages && $is_postback && ! $is_valid ) {
				self::set_current_page( $form_id, GFFormDisplay::get_first_page_with_error( $form ) );
			}

			$current_page = self::get_current_page( $form_id );

			if ( $has_pages && ! $is_admin ) {
				$pagination_type = rgars( $form, 'pagination/type' );

				if ( $pagination_type == 'percentage' ) {
					$form_string .= self::get_progress_bar( $form, $current_page, $confirmation_message );
				} else if ( $pagination_type == 'steps' ) {
					$form_string .= self::get_progress_steps( $form, $current_page );
				}
			}


			$form_string .= "
                        <div class='gform-body gform_body'>";

			//add first page if this form has any page fields
			if ( $has_pages ) {
				$style         = self::is_page_active( $form_id, 1 ) ? '' : "style='display:none;'";
				$class         = ' ' . rgar( $form, 'firstPageCssClass', '' );
				$class         = esc_attr( $class );
				$form_string .= "<div id='gform_page_{$form_id}_1' class='gform_page{$class}' data-js='page-field-id-0' {$style}>
                                    <div class='gform_page_fields'>";
			}

			$tag = GFCommon::is_legacy_markup_enabled( $form ) ? 'ul' : 'div';
			$form_string .= "<{$tag} id='gform_fields_{$form_id}' class='" . GFCommon::get_ul_classes( $form ) . "'>";

			if ( is_array( $form['fields'] ) ) {

				// Add honeypot field if Honeypot is enabled.
				$honeypot_handler = GFForms::get_service_container()->get( Gravity_Forms\Gravity_Forms\Honeypot\GF_Honeypot_Service_Provider::GF_HONEYPOT_HANDLER );
				$form             = $honeypot_handler->maybe_add_honeypot_field( $form );

				foreach ( $form['fields'] as $field ) {
					$field->set_context_property( 'rendering_form', true );
					/* @var GF_Field $field */
					$field->conditionalLogicFields = self::get_conditional_logic_fields( $form, $field->id );

					if ( is_array( $submitted_values ) ) {
						$field_value = rgar( $submitted_values, $field->id );

						if ( $field->type === 'consent'
						     && ( $field_value[ $field->id . '.3' ] != GFFormsModel::get_latest_form_revisions_id( $form['id'] )
						          || $field_value[ $field->id . '.2' ] != $field->checkboxLabel ) ) {
							$field_value = GFFormsModel::get_field_value( $field, $field_values );
						}
					} else {
						$field_value = GFFormsModel::get_field_value( $field, $field_values );
					}

					$form_string .= self::get_field( $field, $field_value, false, $form, $field_values );

					$form_string .= self::get_row_spacer( $field, $form );

				}
			}
			$form_string .= "</{$tag}>";

			$label_placement = rgar( $form, 'labelPlacement', 'top_label' );

			if ( $has_pages ) {
				$last_page_button = rgar( $form, 'lastPageButton', array() );
				$previous_button_alt = rgar( $last_page_button, 'imageAlt', __( 'Previous Page', 'gravityforms' ) );
				$previous_button = self::get_form_button( $form['id'], "gform_previous_button_{$form['id']}", $last_page_button, __( 'Previous', 'gravityforms' ), 'gform_previous_button gform-theme-button gform-theme-button--secondary', $previous_button_alt, self::get_current_page( $form_id ) - 1 );

				/**
				 * Filter through the form previous button when paged
				 *
				 * @param int $form_id The Form ID to filter through
				 * @param string $previous_button The HTML rendered button (rendered with the form ID and the function get_form_button)
				 * @param array $form The Form object to filter through
				 */
				$previous_button = gf_apply_filters( array( 'gform_previous_button', $form_id ), $previous_button, $form );
				$form_string .= '</div>' . self::gform_footer( $form, 'gform-page-footer gform_page_footer ' . $label_placement, $ajax, $field_values, $previous_button, $display_title, $display_description, $tabindex, $form_theme, $style_settings, $submission_method ) . '
                        </div>'; //closes gform_page
			}

			$form_string .= '</div>'; //closes gform_body

			//suppress form footer for multi-page forms (footer will be included on the last page
			if ( ! $has_pages ) {
				$form_string .= self::gform_footer( $form, 'gform-footer gform_footer ' . $label_placement, $ajax, $field_values, '', $display_title, $display_description, $tabindex, $form_theme, $style_settings, $submission_method );
			}

			$form_string .= '
                        </form>
                        </div>';

			if ( $ajax && $is_postback ) {
				global $wp_scripts;

				$form_string = self::get_ajax_postback_html( $form_string );

			}

			/**
			 * Allows users to disable the spinner on non-ajax forms.
			 *
			 * @since 2.7
			 *
			 * @param bool $show Whether to show the spinner on non-ajax-forms.
			 *
			 * @return bool
			 */
			$always_show_spinner = gf_apply_filters( array( 'gform_always_show_spinner', $form_id ), true );

			$should_show_spinner = $ajax || $always_show_spinner;

			if ( $should_show_spinner ) {
				$default_spinner = GFCommon::get_base_url() . '/images/spinner.svg';
				$spinner_url     = gf_apply_filters( array( 'gform_ajax_spinner_url', $form_id ), $default_spinner, $form );
				$theme_slug      = self::get_form_theme_slug( $form );
				$is_legacy       = $default_spinner !== $spinner_url || in_array( $theme_slug, array( 'gravity-theme', 'legacy' ) );

				$scroll_position = array( 'default' => '', 'confirmation' => '' );

				if ( $anchor['scroll'] !== false ) {
					$scroll_position['default']      = is_numeric( $anchor['scroll'] ) ? 'jQuery(document).scrollTop(' . intval( $anchor['scroll'] ) . ');' : "jQuery(document).scrollTop(jQuery('#gform_wrapper_{$form_id}').offset().top - mt);";
					$scroll_position['confirmation'] = is_numeric( $anchor['scroll'] ) ? 'jQuery(document).scrollTop(' . intval( $anchor['scroll'] ) . ');' : "jQuery(document).scrollTop(jQuery('{$anchor['id']}').offset().top - mt);";
				}

				// Accessibility enhancements to properly handle the iframe title and content.
				$iframe_content = esc_html__( 'This iframe contains the logic required to handle Ajax powered Gravity Forms.', 'gravityforms' );
				$iframe_title   = " title='{$iframe_content}'";
				if ( defined( 'GF_DEBUG' ) && GF_DEBUG ) {
					// In debug mode, display the iframe with the text content.
					$iframe_style = 'display:block;width:600px;height:300px;border:1px solid #eee;';
				} else {
					// Hide the iframe and the content is not needed when not in debug mode.
					$iframe_style   = 'display:none;width:0px;height:0px;';
					$iframe_content = '';
				}

				if ( ! $ajax || ! $is_postback ) {
					$form_scripts_body =
						'gform.initializeOnLoaded( function() {' .
						"gformInitSpinner( {$form_id}, '{$spinner_url}', " . ( $is_legacy ? 'true' : 'false' ) . " );" .
						"jQuery('#gform_ajax_frame_{$form_id}').on('load',function(){" .
						"var contents = jQuery(this).contents().find('*').html();" .
						"var is_postback = contents.indexOf('GF_AJAX_POSTBACK') >= 0;" .
						'if(!is_postback){return;}' .
						"var form_content = jQuery(this).contents().find('#gform_wrapper_{$form_id}');" .
						"var is_confirmation = jQuery(this).contents().find('#gform_confirmation_wrapper_{$form_id}').length > 0;" .
						"var is_redirect = contents.indexOf('gformRedirect(){') >= 0;" .
						'var is_form = form_content.length > 0 && ! is_redirect && ! is_confirmation;' .
						"var mt = parseInt(jQuery('html').css('margin-top'), 10) + parseInt(jQuery('body').css('margin-top'), 10) + 100;" .
						'if(is_form){' .
						( $should_render_hidden ? "form_content.find('form').css('opacity', 0);" : "" ) .
						"jQuery('#gform_wrapper_{$form_id}').html(form_content.html());" .
						"if(form_content.hasClass('gform_validation_error')){jQuery('#gform_wrapper_{$form_id}').addClass('gform_validation_error');} else {jQuery('#gform_wrapper_{$form_id}').removeClass('gform_validation_error');}" .
						"setTimeout( function() { /* delay the scroll by 50 milliseconds to fix a bug in chrome */ {$scroll_position['default']} }, 50 );" .
						"if(window['gformInitDatepicker']) {gformInitDatepicker();}" .
						"if(window['gformInitPriceFields']) {gformInitPriceFields();}" .
						"var current_page = jQuery('#gform_source_page_number_{$form_id}').val();" .
						"gformInitSpinner( {$form_id}, '{$spinner_url}', " . ( $is_legacy ? 'true' : 'false' ) . " );" .
						"jQuery(document).trigger('gform_page_loaded', [{$form_id}, current_page]);" .
						"window['gf_submitting_{$form_id}'] = false;" .
						'}' .
						'else if(!is_redirect){' .
						"var confirmation_content = jQuery(this).contents().find('.GF_AJAX_POSTBACK').html();" .
						'if(!confirmation_content){' .
						'confirmation_content = contents;' .
						'}' .
						"jQuery('#gform_wrapper_{$form_id}').replaceWith(confirmation_content);" .
						"{$scroll_position['confirmation']}" .
						"jQuery(document).trigger('gform_confirmation_loaded', [{$form_id}]);" .
						"window['gf_submitting_{$form_id}'] = false;" .
						"wp.a11y.speak(jQuery('#gform_confirmation_message_{$form_id}').text());" .
						'}' .
						'else{' .
						"jQuery('#gform_{$form_id}').append(contents);" .
						"if(window['gformRedirect']) {gformRedirect();}" .
						'}' .
						self::post_render_script( $form_id ) .
						'} );' .
						'} );';

					$form_scripts = GFCommon::get_inline_script_tag( $form_scripts_body );

					if ( $ajax ) {
						$form_string .= "
		                <iframe style='{$iframe_style}' src='about:blank' name='gform_ajax_frame_{$form_id}' id='gform_ajax_frame_{$form_id}'" . $iframe_title . ">" . $iframe_content . "</iframe>
		                {$form_scripts}";
					} else {
						$form_string .= $form_scripts;
					}

				}


			}

			$is_first_load = ! $is_postback;

			if ( ( ! $ajax || $is_first_load ) ) {

				self::register_form_init_scripts( $form, $field_values, $ajax );

				// We can't init in footer on AJAX calls, as those actions never get called.
				$init_in_footer = ! ( defined('DOING_AJAX') && DOING_AJAX );

				/**
				 * Allows init scripts to be outputted in either the header or footer.
				 *
				 * @since unknown
				 * @since 2.5.3 Defaults to ( ! DOING_AJAX )
				 *
				 * @param bool Whether to output init scripts in the footer. Defaults to ( ! DOING_AJAX ).
				 */
				if ( apply_filters( 'gform_init_scripts_footer', $init_in_footer ) ) {
					$callback = array( new GF_Late_Static_Binding( array( 'form_id' => $form['id'] ) ), 'GFFormDisplay_footer_init_scripts' );
					add_action( 'wp_footer', $callback, 999 );
					add_action( 'admin_print_footer_scripts', $callback, 999 );
					add_action( 'gform_preview_footer', $callback );
				} else {
					$form_string      .= self::get_form_init_scripts( $form );
					$init_script_body = 'gform.initializeOnLoaded( function() {' .
						self::post_render_script( $form_id, $current_page ) .
					'} );';
					$form_string      .= GFCommon::get_inline_script_tag( $init_script_body );
				}
			}

			$form_string = gf_apply_filters( array( 'gform_get_form_filter', $form_id ), $form_string, $form );

			if ( isset( $_GET['gform_debug'] ) || GFCommon::is_preview() ) {
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Preparing form (#%d) markup completed in %F seconds.', $form_id, GFCommon::timer_end( __METHOD__ ) ) );
			}

			return $form_string;

		} else {

			return self::get_confirmation_markup( $form, $confirmation_message, $ajax, $style_settings, $form_theme );
		}
	}

	public static function footer_init_scripts( $form_id ) {
		global $_init_forms;

		$form               = RGFormsModel::get_form_meta( $form_id );
		$form_string        = self::get_form_init_scripts( $form );
		$current_page       = self::get_current_page( $form_id );
		$footer_script_body = 'gform.initializeOnLoaded( function() {' . self::post_render_script( $form_id, $current_page ) . '} );';
		$form_string        .= GFCommon::get_inline_script_tag( $footer_script_body );

		/**
		 * A filter to allow modification of scripts that fire in the footer
		 *
		 * @param int $form_id The Form ID to filter through
		 * @param string $form_string Get the form scripts in a string
		 * @param array $form The Form object to filter through
		 * @param int $current_page The Current form page ID (If paging is enabled)
		 */
		$form_string = gf_apply_filters( array( 'gform_footer_init_scripts_filter', $form_id ), $form_string, $form, $current_page );

		if ( ! isset( $_init_forms[ $form_id ] ) ) {
			echo $form_string;
			if ( ! is_array( $_init_forms ) ) {
				$_init_forms = array();
			}

			$_init_forms[ $form_id ] = true;
		}
	}

	public static function add_init_script( $form_id, $script_name, $location, $script ) {
		$key = $script_name . '_' . $location;

		if ( ! isset( self::$init_scripts[ $form_id ] ) ) {
			self::$init_scripts[ $form_id ] = array();
		}

		//add script if it hasn't been added before
		if ( ! array_key_exists( $key, self::$init_scripts[ $form_id ] ) ) {
			self::$init_scripts[ $form_id ][ $key ] = array( 'location' => $location, 'script' => $script );
		}
	}

	public static function get_form_button( $form_id, $button_input_id, $button, $default_text, $class, $alt, $target_page_number, $onclick = '' ) {

		$is_form_editor = GFCommon::is_form_editor();
		$tabindex       = GFCommon::get_tabindex();
		$input_type     = ( rgar( $button, 'type' ) === 'link' ) ? 'button' : 'submit';
		$input_onclick  = $is_form_editor ? '' : "onclick='gform.submission.handleButtonClick(this);'";

		if ( ! empty( $target_page_number ) ) {
			$input_type  = 'button';
		}

		if ( rgar( $button, 'type' ) == 'text' || rgar( $button, 'type' ) == 'link' || empty( $button['imageUrl'] ) ) {
			$button_text = ! empty( $button['text'] ) ? $button['text'] : $default_text;
			if ( rgar( $button, 'type' ) == 'link' ) {
				if ( GFCommon::is_legacy_markup_enabled( $form_id ) ) {
					$tag    = 'a';
					$target = 'href="javascript:void(0);"';
					$icon   = '';
				} else {
					$tag    = 'button';
					$class .= GFFormDisplay::get_submit_button_class( $button, $form_id );
					$target = '';
					$icon   = '<svg aria-hidden="true" focusable="false" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 8a4 4 0 004 4h3v3a1 1 0 102 0v-3h3a4 4 0 100-8 4 4 0 10-8 0 4 4 0 00-4 4zm9 4H7V7.414L5.707 8.707a1 1 0 01-1.414-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L9 7.414V12z" fill="#6B7280"/></svg>';
				}
				$button_input = "<{$tag} type='{$input_type}' {$target} id='{$button_input_id}_link' {$input_onclick} class='{$class}' {$tabindex} >{$icon} {$button_text}</{$tag}>";
			} else {
				$class .= GFFormDisplay::get_submit_button_class( $button, $form_id );
				$button_input = "<input type='{$input_type}' id='{$button_input_id}' class='{$class}' {$input_onclick} value='" . esc_attr( $button_text ) . "' {$tabindex} />";
			}
		} else {
			$imageUrl = esc_url( $button['imageUrl'] );
			$class .= GFFormDisplay::get_submit_button_class( $button, $form_id );
			$class .= ' gform_image_button';
			$button_input = "<input type='image' src='{$imageUrl}' id='{$button_input_id}' {$input_onclick} class='{$class}' alt='{$alt}' {$tabindex} />";
		}

		return $button_input;
	}

	/**
	 * Get the CSS class for the submit button.
	 *
	 * @since 2.6
	 *
	 * @param array   $button  The button attributes.
	 * @param integer $form_id The ID of the form.
	 *
	 * @return string The CSS class(es) for this button.
	 */
	public static function get_submit_button_class( $button, $form_id ) {
		$class  = ' button';
		$class .= rgar( $button, 'width' ) && 'full' == $button['width'] ? ' gform-button--width-full' : '';

		// if the button is at the bottom, and if it has width, add a width class.
		if ( rgar( $button, 'location' ) && 'bottom' == $button['location'] && rgar( $button, 'layoutGridColumnSpan' ) && 12 !== $button['layoutGridColumnSpan'] ) {
			$form   = GFAPI::get_form( $form_id );
			$submit = new GF_Field_Submit();
			$class .= ' ' . $submit->get_css_grid_class( $form );
		}

		return $class;
	}

	public static function gform_footer( $form, $class, $ajax, $field_values, $previous_button, $display_title, $display_description, $tabindex = 1, $theme = null, $style_settings = null, $submission_method = self::SUBMISSION_METHOD_POSTBACK ) {
		$form_id      = absint( $form['id'] );
		$footer       = "
        <div class='" . esc_attr( $class ) . "'>";
		$button       = rgar( $form, 'button', array( 'type' => 'link' ) );
		if ( rgar( $form['button'], 'location' ) && 'inline' == $form['button']['location'] ) {
			$button_input = '';
		} else {
			$button_input = self::get_form_button( $form['id'], "gform_submit_button_{$form['id']}", $button, __( 'Submit', 'gravityforms' ), 'gform_button', __( 'Submit', 'gravityforms' ), 0 );
			$button_input = gf_apply_filters( array( 'gform_submit_button', $form_id ), $button_input, $form );
		}

		$save_button = rgars( $form, 'save/enabled' ) ? self::get_form_button( $form_id, "gform_save_{$form_id}_footer", $form['save']['button'], rgars( $form, 'save/button/text' ), 'gform_save_link gform-theme-button gform-theme-button--secondary', rgars( $form, 'save/button/text' ), 0, "jQuery(\"#gform_save_{$form_id}\").val(1);" ) : '';

		/**
		 * Filters the save and continue link allowing the tag to be customized
		 *
		 * @since 2.0.7.7
		 *
		 * @param string $save_button The string containing the save and continue link markup.
		 * @param array  $form        The Form object associated with the link.
		 */
		$save_button = apply_filters( 'gform_savecontinue_link', $save_button, $form );
		$save_button = apply_filters( "gform_savecontinue_link_{$form_id}", $save_button, $form );

		$footer .= $previous_button . ' ' . $button_input . ' ' . $save_button;

		$tabindex = intval( $tabindex );

		// Make sure style settings are valid JSON.
		$style_settings = self::validate_form_styles( $style_settings );
		$style_settings = is_array( $style_settings ) ? json_encode( $style_settings ) : null;
		$is_valid_json  = is_string( $style_settings );

		if ( $ajax ) {
			$ajax_value = self::prepare_ajax_input_value( $form_id, $display_title, $display_description, $tabindex, $theme, $is_valid_json ? $style_settings : null );
			$footer     .= "<input type='hidden' name='gform_ajax' value='" . esc_attr( $ajax_value ) . "' />";
		}

		$current_page     = self::get_current_page( $form_id );
		$next_page        = $current_page + 1;
		$next_page        = $next_page > self::get_max_page_number( $form ) ? 0 : $next_page;
		$field_values_str = is_array( $field_values ) ? http_build_query( $field_values ) : $field_values;
		$files_input      = '';
		if ( GFCommon::has_multifile_fileupload_field( $form ) || ! empty( RGFormsModel::$uploaded_files[ $form_id ] ) ) {
			$files       = ! empty( RGFormsModel::$uploaded_files[ $form_id ] ) ? json_encode( RGFormsModel::$uploaded_files[ $form_id ], JSON_UNESCAPED_UNICODE ) : '';
			$files_input = "<input type='hidden' name='gform_uploaded_files' id='gform_uploaded_files_{$form_id}' value='" . str_replace( "'", '&#039;', $files ) . "' />";
		}
		$save_inputs = '';
		if ( rgars( $form, 'save/enabled' ) ) {
			$resume_token = isset( $_POST['gform_resume_token'] ) ? $_POST['gform_resume_token'] : rgget( 'gf_token' );
			$resume_token = sanitize_key( $resume_token );
			$save_inputs  = "<input type='hidden' class='gform_hidden' name='gform_save' id='gform_save_{$form_id}' value='' />
                             <input type='hidden' class='gform_hidden' name='gform_resume_token' id='gform_resume_token_{$form_id}' value='{$resume_token}' />";
		}

		if ( GFCommon::form_requires_login( $form ) ) {
			$footer .= wp_nonce_field( 'gform_submit_' . $form_id, '_gform_submit_nonce_' . $form_id, true, false );
		}

		$unique_id      = isset( self::$submission[ $form_id ] ) && rgar( self::$submission[ $form_id ], 'resuming_incomplete_submission' ) == true ? rgar( GFFormsModel::$unique_ids, $form_id ) : GFFormsModel::get_form_unique_id( $form_id );
		$style_settings = $is_valid_json ? esc_attr( $style_settings ) : '';

		$footer .= "
            <input type='hidden' class='gform_hidden' name='gform_submission_method' data-js='gform_submission_method_{$form_id}' value='" . self::get_submission_method( $submission_method ) . "' />
            <input type='hidden' class='gform_hidden' name='gform_theme' data-js='gform_theme_{$form_id}' id='gform_theme_{$form_id}' value='" . esc_attr( $theme ) . "' />
            <input type='hidden' class='gform_hidden' name='gform_style_settings' data-js='gform_style_settings_{$form_id}' id='gform_style_settings_{$form_id}' value='" . $style_settings . "' />
            <input type='hidden' class='gform_hidden' name='is_submit_{$form_id}' value='1' />
            <input type='hidden' class='gform_hidden' name='gform_submit' value='{$form_id}' />
            {$save_inputs}
            <input type='hidden' class='gform_hidden' name='gform_unique_id' value='" . esc_attr( $unique_id ) . "' />
            <input type='hidden' class='gform_hidden' name='state_{$form_id}' value='" . self::get_state( $form, $field_values ) . "' />
            <input type='hidden' autocomplete='off' class='gform_hidden' name='gform_target_page_number_{$form_id}' id='gform_target_page_number_{$form_id}' value='" . esc_attr( $next_page ) . "' />
            <input type='hidden' autocomplete='off' class='gform_hidden' name='gform_source_page_number_{$form_id}' id='gform_source_page_number_{$form_id}' value='" . esc_attr( $current_page ) . "' />
            <input type='hidden' name='gform_field_values' value='" . esc_attr( $field_values_str ) . "' />
            {$files_input}
        </div>";

		return $footer;
	}

	public static function get_max_page_number( $form ) {
		$page_number = 0;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'page' ) {
				$page_number ++;
			}
		}

		return $page_number == 0 ? 0 : $page_number + 1;
	}

	public static function get_first_page_with_error( $form ) {

		$page = self::get_current_page( $form['id'] );

		foreach ( $form['fields'] as $field ) {
			if ( $field->failed_validation ) {
				$page = $field->pageNumber;
				break;
			}
		}

		return $page;
	}

	/**
	 * Get the maximum field ID for the current form.
	 *
	 * @since unknown
	 * @since 1.9.14 Updated to public access.
	 * @since 2.4.15 Updated to use GFFormsModel::get_next_field_id().
	 *
	 * @param array $form The current form object.
	 *
	 * @return int
	 */
	public static function get_max_field_id( $form ) {
		if ( ! empty( $form['fields'] ) ) {
			$max = GFFormsModel::get_next_field_id( $form['fields'] ) - 1;
		} else {
			$max = 0;
		}

		return $max;
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @param GF_Field $field
	 * @param int $form_id
	 *
	 * @return bool
	 */
	public static function is_empty( $field, $form_id = 0 ) {

		if ( empty( $_POST[ 'is_submit_' . $field->formId ] ) ) {
			return true;
		}

		return $field->is_value_submission_empty( $form_id );
	}

	/**
	 * Triggers saving or updating of the entry, spam eval, post creation, sending of notifications, and then returns the confirmation to be used for the current submission.
	 *
	 * @since unknown
	 * @since 2.7 Updated the $form param to pass by reference.
	 *
	 * @param array $form The form being processed.
	 * @param array $lead The entry being saved.
	 * @param bool  $ajax Indicates if ajax is enabled for the form.
	 *
	 * @return string|array
	 */
	public static function handle_submission( &$form, &$lead, $ajax = false ) {
		$form_id = absint( rgar( $form, 'id' ) );

		$lead_id = gf_apply_filters( array( 'gform_entry_id_pre_save_lead', $form_id ), null, $form );

		if ( ! empty( $lead_id ) ) {
			GFCommon::log_debug( __METHOD__ . '(): The gform_entry_id_pre_save_lead filter was used to set the entry ID to ' . var_export( $lead_id, true ) );

			if ( empty( $lead ) ) {
				$lead = array();
			}
			$lead['id'] = $lead_id;
		}

		// Passwords are not saved to the database but should be available during the submission process.
		GF_Field_Password::stash_passwords( $form );

		//creating entry in DB
		RGFormsModel::save_lead( $form, $lead );

		$lead = GFFormsModel::set_entry_meta( $lead, $form );

		$is_spam = GFCommon::is_spam_entry( $lead, $form );

		if ( $is_spam ) {

			// Marking entry as spam.
			GFFormsModel::update_entry_property( $lead['id'], 'status', 'spam', false, true );
			$lead['status'] = 'spam';

			// Creating entry note.
			self::create_spam_entry_note( $lead['id'], $form['id'] );
		}

		// Passwords are not saved to the database but should be available during the submission process.
		$lead = GF_Field_Password::hydrate_passwords( $lead );

		if ( has_action( 'gform_entry_created' ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_entry_created.' );
			/**
			 * Fired after an entry is created.
			 *
			 * @since 1.6.2
			 *
			 * @param array $lead The Entry object.
			 * @param array $form The Form object.
			 */
			do_action( 'gform_entry_created', $lead, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_entry_created.' );
		}

		$gform_entry_post_save_args = array( 'gform_entry_post_save', $form_id );
		if ( gf_has_filter( $gform_entry_post_save_args ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_entry_post_save.' );
			/**
			 * Allows filtering of the entry after it has been saved to the database.
			 *
			 * @since Unknown.
			 *
			 * @param array $lead The entry that was saved to the database.
			 * @param array $form The form currently being processed.
			 */
			$lead = gf_apply_filters( $gform_entry_post_save_args, $lead, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_entry_post_save.' );
		}

		gf_feed_processor()->save()->dispatch();

		RGFormsModel::set_current_lead( $lead );

		if ( ! $is_spam ) {
			GFCommon::create_post( $form, $lead );
			//send notifications
			GFCommon::send_form_submission_notifications( $form, $lead );
		}

		self::clean_up_files( $form );

		// remove incomplete submission and purge expired
		if ( rgars( $form, 'save/enabled' ) ) {
			GFFormsModel::delete_draft_submission( rgpost( 'gform_resume_token' ) );
			GFFormsModel::purge_expired_draft_submissions();
		}

		if ( has_action( 'gform_pre_handle_confirmation' ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_handle_confirmation.' );
			/**
			 * Fires during submission before the confirmation is processed.
			 *
			 * @since 2.3.3.10
			 *
			 * @param array $lead The entry array.
			 * @param array $form The Form array.
			 */
			do_action( 'gform_pre_handle_confirmation', $lead, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_handle_confirmation.' );
		}

		if ( has_filter( 'gform_entry_pre_handle_confirmation' ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_entry_pre_handle_confirmation.' );
			/**
			 * Allows the entry to be modified before the confirmation is processed.
			 *
			 * @since 2.3.4.2
			 *
			 * @param array $lead The entry array.
			 * @param array $form The Form array.
			 */
			$lead = apply_filters( 'gform_entry_pre_handle_confirmation', $lead, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_entry_pre_handle_confirmation.' );
		}

		//display confirmation message or redirect to confirmation page
		return self::handle_confirmation( $form, $lead, $ajax );
	}

	/**
	 * Creates an entry note with the spam reason and spam filter information in it.
	 *
	 * @since 2.7
	 *
	 * @param int $entry_id Submitted entry id.
	 * @param int $form_id Submitted form id.
	 */
	private static function create_spam_entry_note( $entry_id, $form_id ) {

		$spam_filter = rgars( self::$submission, "{$form_id}/spam_filter" );
		if ( empty( $spam_filter ) ) {
			return;
		}

		$filter_name = ! rgempty( 'filter', $spam_filter ) ? $spam_filter['filter'] : __( 'Spam Filter', 'gravityforms' );
		$note        = __( 'This entry has been flagged as spam.', 'gravityforms' );
		if ( ! rgempty( 'reason', $spam_filter ) ) {
			// translators: Variable is a complete sentence containing the reason the entry was marked as spam.
			$note .= ' ' . sprintf( __( 'Reason: %s', 'gravityforms' ), $spam_filter['reason'] );
		}

		GFAPI::add_note( $entry_id, 0, $filter_name, $note );
	}

	/**
	 * Deletes tmp files for the given form.
	 *
	 * @since Unknown
	 * @since 2.8.15 Added the $is_submission param.
	 *
	 * @param array $form          The form the tmp files are to be deleted for.
	 * @param bool  $is_submission Indicates if tmp files for the current form submission should be deletes as well.
	 *
	 * @return false|void
	 */
	public static function clean_up_files( $form, $is_submission = true ) {
		$tmp_location  = GFFormsModel::get_tmp_upload_location( $form['id'] );
		$target_path   = $tmp_location['path'];

		if ( $is_submission ) {
			$unique_form_id = rgpost( 'gform_unique_id' );
			if ( ! ctype_alnum( $unique_form_id ) ) {
				return false;
			}
			$filename    = $unique_form_id . '_input_*';
			$files       = GFCommon::glob( $filename, $target_path );
			if ( is_array( $files ) ) {
				array_map( 'unlink', $files );
			}
		}

		// clean up files from abandoned submissions older than 48 hours (30 days if Save and Continue is enabled)
		$files = GFCommon::glob( '*', $target_path );
		if ( is_array( $files ) ) {
			$seconds_in_day  = 24 * 60 * 60;
			$save_enabled    = rgars( $form, 'save/enabled' );
			$expiration_days = $save_enabled ? 30 : 2;

			/**
			 * Filter lifetime in days of temporary files.
			 *
			 * @since 2.1.3.5
			 *
			 * @param int   $expiration_days The number of days temporary files should remain in the uploads directory. Default is 2 or 30 if save and continue is enabled.
			 * @param array $form            The form currently being processed.
			 */
			$expiration_days = apply_filters( 'gform_temp_file_expiration_days', $expiration_days, $form );

			if ( $save_enabled ) {

				/**
				 * Filter lifetime in days of an incomplete form submission
				 *
				 * @since 2.1.3.5
				 *
				 * @param int $expiration_days The number of days temporary files should remain in the uploads directory.
				 */
				$expiration_days = apply_filters( 'gform_incomplete_submissions_expiration_days', $expiration_days );

			}

			$lifespan = $expiration_days * $seconds_in_day;

			foreach ( $files as $file ) {
				if ( is_file( $file ) && time() - filemtime( $file ) >= $lifespan ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Prepares the confirmation message or redirect to be used by the current submission.
	 *
	 * @since 2.1.1.11 Refactored to use GFFormDisplay::get_confirmation_message().
	 * @since 2.5      Updated to use GFFormDisplay::get_confirmation_url().
	 * @since 2.7      Updated the $form param to pass by reference.
	 *
	 * @param array $form     The Form Object.
	 * @param array $entry    The Entry Object.
	 * @param bool  $ajax     If AJAX is being used. Defaults to false.
	 * @param array $aux_data Additional data to use when building the confirmation message. Defaults to empty array.
	 *
	 * @return string|array
	 */
	public static function handle_confirmation( &$form, $entry, $ajax = false, $aux_data = array() ) {

		$form = self::update_confirmation( $form, $entry );
		GFCommon::log_debug( sprintf( '%s(): Preparing confirmation (#%s - %s).', __METHOD__, rgar( $form['confirmation'], 'id' ), rgar( $form['confirmation'], 'name' ) ) );

		if ( rgar( $form['confirmation'], 'type' ) == 'message' ) {
			$confirmation = self::get_confirmation_message( $form['confirmation'], $form, $entry, $aux_data );
		} else {
			$confirmation = array( 'redirect' => self::get_confirmation_url( $form['confirmation'], $form, $entry ) );
		}

		$form_id = absint( $form['id'] );
		$filter  = array( 'gform_confirmation', $form_id );
		if ( gf_has_filters( $filter ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_confirmation.' );

			/**
			 * Allows the form confirmation to be overridden.
			 *
			 * @since unknown
			 *
			 * @param string|array $confirmation The confirmation message or an array when performing a redirect.
			 * @param array        $form         The form which was submitted.
			 * @param array        $entry        The entry created from the form submission.
			 * @param bool         $ajax         Indicates if ajax is enabled for the current form.
			 */
			$confirmation = gf_apply_filters( $filter, $confirmation, $form, $entry, $ajax );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_confirmation.' );
		}

		if ( is_array( $confirmation ) && ! empty( $confirmation['redirect'] ) ) {
			$suppress_redirect = false;

			/**
			 * Allows the confirmation redirect header to be suppressed. Required by GFAPI::submit_form().
			 *
			 * @since 2.3
			 *
			 * @param bool $suppress_redirect Indicates if the redirect header should be suppressed.
			 */
			$suppress_redirect = apply_filters( 'gform_suppress_confirmation_redirect', $suppress_redirect );

			if ( ( headers_sent() || $ajax ) && ! $suppress_redirect ) {
				// Using client side redirect for AJAX forms or if headers have already been sent.
				$confirmation = self::get_js_redirect_confirmation( $confirmation['redirect'], $ajax );
			}
		} elseif ( is_string( $confirmation ) && ! empty( $confirmation ) ) {
			$confirmation = GFCommon::gform_do_shortcode( $confirmation );
		} else {
			$confirmation = null;
		}

		if ( empty( $confirmation ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Invalid confirmation; using default text instead.' );
			$form['confirmation'] = GFFormsModel::get_default_confirmation();
			$confirmation         = self::get_confirmation_message( $form['confirmation'], $form, $entry );
		}

		GFCommon::log_debug( __METHOD__ . '(): Confirmation to be used => ' . print_r( $confirmation, true ) );

		return $confirmation;
	}

	/**
	 * Returns the redirect URL for the current submission.
	 *
	 * @since 2.5
	 *
	 * @param array $confirmation The confirmation properties.
	 * @param array $form         The form which was submitted.
	 * @param array $entry        The entry created from the form submission.
	 *
	 * @return string
	 */
	public static function get_confirmation_url( $confirmation, $form, $entry ) {
		if ( ! empty( $confirmation['pageId'] ) && $confirmation['type'] === 'page' ) {
			$url = get_permalink( $confirmation['pageId'] );
			if ( empty( $url ) ) {
				GFCommon::log_debug( sprintf( '%s(): Selected page (%s) is invalid.', __METHOD__, $confirmation['pageId'] ) );

				return '';
			}
		} else {
			$url = rgar( $confirmation, 'url' );
			if ( ! empty( $url ) ) {
				$url = trim( GFCommon::replace_variables( $url, $form, $entry, false, false, true, 'text' ) );
			}

			if ( empty( $url ) ) {
				GFCommon::log_debug( __METHOD__ . '(): URL is empty.' );

				return '';
			}
		}

		$url_info      = parse_url( $url );
		$query_string  = rgar( $url_info, 'query' );
		$dynamic_query = GFCommon::replace_variables( trim( $confirmation['queryString'] ), $form, $entry, true, false, false, 'text' );
		$dynamic_query = str_replace( array( "\r", "\n" ), '', $dynamic_query );
		$query_string  .= rgempty( 'query', $url_info ) || empty( $dynamic_query ) ? $dynamic_query : '&' . $dynamic_query;

		if ( ! empty( $url_info['fragment'] ) ) {
			$query_string .= '#' . rgar( $url_info, 'fragment' );
		}

		$url = isset( $url_info['scheme'] ) ? $url_info['scheme'] : 'http';
		$url .= '://' . rgar( $url_info, 'host' );
		if ( ! empty( $url_info['port'] ) ) {
			$url .= ':' . rgar( $url_info, 'port' );
		}

		$url .= rgar( $url_info, 'path' );
		if ( ! empty( $query_string ) ) {
			$url .= "?{$query_string}";
		}

		return $url;
	}

	/**
	 * Gets the confirmation message to be displayed.
	 *
	 * @since  2.1.1.11
	 * @access public
	 *
	 * @param  array $confirmation The Confirmation Object.
	 * @param  array $form         The Form Object.
	 * @param  array $entry        The Entry Object.
	 * @param  array $aux_data     Additional data to be passed to GFCommon::replace_variables().
	 *
	 * @return string The confirmation message.
	 */
	public static function get_confirmation_message( $confirmation, $form, $entry, $aux_data = array() ) {
		$ajax   = isset( $_POST['gform_ajax'] );
		$anchor = self::get_anchor( $form, $ajax );
		$anchor = $anchor['tag'];

		$nl2br     = rgar( $confirmation, 'disableAutoformat' ) ? false : true;
		$css_class = esc_attr( rgar( $form, 'cssClass' ) );

		$message = GFCommon::replace_variables( $confirmation['message'], $form, $entry, false, true, $nl2br, 'html', $aux_data );
		$message = self::maybe_sanitize_confirmation_message( $message );
		$message = empty( $confirmation['message'] ) ? "{$anchor} " : "{$anchor}<div id='gform_confirmation_wrapper_{$form['id']}' class='gform_confirmation_wrapper {$css_class}'><div id='gform_confirmation_message_{$form['id']}' class='gform_confirmation_message_{$form['id']} gform_confirmation_message'>" . $message . '</div></div>';

		return $message;
	}

	/**
	 * Sanitizes a confirmation message.
	 *
	 * @since 2.0.0
	 * @param $confirmation_message
	 *
	 * @return string
	 */
	private static function maybe_sanitize_confirmation_message( $confirmation_message ) {
		return GFCommon::maybe_sanitize_confirmation_message( $confirmation_message );
	}

	private static function get_js_redirect_confirmation( $url, $ajax ) {
		// JSON_HEX_TAG is available on PHP >= 5.3. It will prevent payloads such as <!--<script> from causing an error on redirection.
		$url =  defined( 'JSON_HEX_TAG' ) ? json_encode( $url, JSON_HEX_TAG ) : json_encode( $url );
		$script_body = "function gformRedirect(){document.location.href={$url};}";
		if ( ! $ajax ) {
			$script_body .= 'gformRedirect();';
		}

		return GFCommon::get_inline_script_tag( $script_body );
	}

	public static function send_emails( $form, $lead ) {
		_deprecated_function( 'send_emails', '1.7', 'GFCommon::send_form_submission_notifications' );
		GFCommon::send_form_submission_notifications( $form, $lead );
	}

	/**
	 * Returns the context for the current submission.
	 *
	 * @since 2.6.4
	 *
	 * @return string
	 */
	public static function get_submission_context() {
		switch ( self::$submission_initiated_by ) {
			case self::SUBMISSION_INITIATED_BY_WEBFORM:
				return 'form-submit';

			case self::SUBMISSION_INITIATED_BY_API_VALIDATION:
				return 'api-validate';
		}

		return 'api-submit';
	}

	/**
	 * Determines if the current form submission is valid.
	 *
	 * @since unknown
	 * @since 2.4.19 Updated to use GFFormDisplay::is_field_validation_supported().
	 *
	 * @param array $form                   The form being processed.
	 * @param array $field_values           The dynamic population parameter names and values.
	 * @param int   $page_number            The current page number.
	 * @param int   $failed_validation_page The page number which has failed validation.
	 *
	 * @return bool
	 */
	public static function validate( &$form, $field_values, $page_number = 0, &$failed_validation_page = 0 ) {
		$form_id = absint( rgar( $form, 'id' ) );
		GFCommon::log_debug( __METHOD__ . "(): Starting for form #{$form_id}." );

		$gform_pre_validation_args = array( 'gform_pre_validation', $form_id );
		if ( gf_has_filter( $gform_pre_validation_args ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_validation.' );
			/**
			 * Allows the form to be modified before the submission is validated.
			 *
			 * @since 1.7
			 * @since 1.9 Added the form specific version.
			 *
			 * @param array $form The form for the submission to be validated.
			 */
			$form = gf_apply_filters( $gform_pre_validation_args, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_validation.' );
		}

		GFCommon::log_debug( __METHOD__ . '(): Checking restrictions.' );

		// validate form schedule
		if ( self::validate_form_schedule( $form ) ) {
			return false;
		}

		// validate entry limit
		if ( self::validate_entry_limit( $form ) ) {
			return false;
		}

		// make sure database isn't being upgraded now and submissions are blocked
		if ( gf_upgrade()->get_submissions_block() ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting. Database upgrade in progress.' );

			return false;
		}

		// Prevent tampering with the submitted form
		if ( empty( $_POST[ 'is_submit_' . $form_id ] ) ) {
			GFCommon::log_debug( __METHOD__ . "(): Aborting. The is_submit_{$form_id} input is empty." );

			return false;
		}

		$context = self::get_submission_context();

		$is_valid     = true;
		$is_last_page = self::get_target_page( $form, $page_number, $field_values ) == '0';

		GFCommon::log_debug( __METHOD__ . '(): Completed restrictions. Starting field validation.' );
		GFCommon::timer_start( 'field-validation' );

		foreach ( $form['fields'] as &$field ) {
			/* @var GF_Field $field */

			if ( ! self::is_field_validation_supported( $field ) ) {
				continue;
			}

			// If a page number is specified, only validates fields that are on current page
			$field_in_other_page = $page_number > 0 && $field->pageNumber != $page_number;

			// validate fields with 'no duplicate' functionality when they are present on pages before the current page.
			$validate_duplicate_feature = $field->noDuplicates && $page_number > 0 && $field->pageNumber <= $page_number;

			if ( $field_in_other_page && ! $is_last_page && ! $validate_duplicate_feature ) {
				continue;
			}

			//ignore validation if field is hidden
			if ( RGFormsModel::is_field_hidden( $form, $field, $field_values ) ) {
				$field->is_field_hidden = true;

				continue;
			}

			self::validate_field( $field, $form, $context );

			if ( $field->failed_validation ) {
				$failed_validation_page = $field->pageNumber;
				$is_valid               = false;
			}
		}

		if ( $is_valid && $is_last_page && self::is_form_empty( $form ) ) {
			foreach ( $form['fields'] as &$field ) {
				if ( ! self::is_field_validation_supported( $field ) ) {
					continue;
				}

				$field->failed_validation  = true;
				$field->validation_message = esc_html__( 'At least one field must be filled out', 'gravityforms' );
				$is_valid                  = false;
				unset( $field->is_field_hidden );
			}
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Field validation completed in %F seconds.', GFCommon::timer_end( 'field-validation' ) ) );

		$gform_validation_args = array( 'gform_validation', $form_id );
		if ( ! gf_has_filter( $gform_validation_args ) ) {
			return $is_valid;
		}

		GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_validation.' );

		$validation_result = array(
			'is_valid'               => $is_valid,
			'form'                   => $form,
			'failed_validation_page' => $failed_validation_page,
		);

		/**
		 * Allows custom validation of the form.
		 *
		 * @since Unknown
		 * @since 2.6.4 Added the $context param.
		 *
		 * @param array  $validation_result {
		 *    An array containing the validation properties.
		 *
		 *    @type bool  $is_valid               The validation result.
		 *    @type array $form                   The form currently being validated.
		 *    @type int   $failed_validation_page The number of the page that failed validation or the current page if the form is valid.
		 * }
		 * @param string $context           The context for the current submission. Possible values: form-submit, api-submit, api-validate.
		 */
		$validation_result = gf_apply_filters( $gform_validation_args, $validation_result, $context );
		GFCommon::log_debug( __METHOD__ . '(): Completed gform_validation.' );

		$is_valid               = $validation_result['is_valid'];
		$form                   = $validation_result['form'];
		$failed_validation_page = $validation_result['failed_validation_page'];

		return $is_valid;
	}

	/**
	 * Validates the submitted value of the given field.
	 *
	 * @since 2.7
	 *
	 * @param GF_Field $field   The field currently being validated.
	 * @param array    $form    The form currently being validated.
	 * @param string   $context The context for the current submission. Possible values: form-submit, api-submit, api-validate.
	 *
	 * @return array
	 */
	public static function validate_field( $field, $form, $context ) {
		$value = GFFormsModel::get_field_value( $field );

		if ( $field->isRequired && self::is_empty( $field, $form['id'] ) ) {
			// Invalid when marked as required and there is no value.
			$field->set_required_error( $value );
		} elseif ( $field->noDuplicates ) {
			/**
			 * Filter the value checked during duplicate value checks.
			 *
			 * @since 2.9.2
			 *
			 * @param string     $value   The value being checked against existing entries for duplicates.
			 * @param \GF_Field  $field   The field being checked for duplicates.
			 * @param int        $form_id The ID of the form being checked for duplicates.
			 */
			$value = apply_filters( 'gform_value_pre_duplicate_check', $value, $field, $form['id'] );

			if ( GFFormsModel::is_duplicate( $form['id'], $field, $value ) ) {
				// Invalid when the value has been used by an existing entry and duplicate values aren't allowed.
				$field->failed_validation = true;

				switch ( $field->get_input_type() ) {
					case 'date' :
						$message = esc_html__( 'This date has already been taken. Please select a new date.', 'gravityforms' );
						break;

					default:
						$message = is_array( $value ) ? esc_html__( 'This field requires a unique entry and the values you entered have already been used.', 'gravityforms' ) :
							sprintf( esc_html__( "This field requires a unique entry and '%s' has already been used", 'gravityforms' ), $value );
						break;
				}

				/**
				 * Allows the no duplicate validation message to be customized.
				 *
				 * @param string $message The no duplicate validation message.
				 * @param array $form The form currently being validated.
				 * @param GF_Field $field The field currently being validated.
				 * @param mixed $value The value currently being validated.
				 *
				 * @since 1.8.5 Added $field and $value params.
				 * @since 2.7   Moved from GFFormDisplay::validate().
				 *
				 * @since 1.5
				 */
				$field->validation_message = gf_apply_filters( array(
					'gform_duplicate_message',
					$form['id']
				), $message, $form, $field, $value );
			} else {
				// Running the field type specific validation.
				$field->validate( $value, $form );
			}
		} elseif ( self::failed_state_validation( $form['id'], $field, $value ) ) {
			// Invalid when the field or state input values have been tampered with.
			$field->failed_validation  = true;
			$field->validation_message = in_array( $field->inputType, array(
				'singleproduct',
				'singleshipping',
				'hiddenproduct',
				'consent',
			) ) ? esc_html__( 'Please enter a valid value.', 'gravityforms' ) : esc_html__( 'Invalid selection. Please select from the available choices.', 'gravityforms' );
		} else {
			// Running the field type specific validation.
			$field->validate( $value, $form );
		}

		$result = array(
			'is_valid' => ! $field->failed_validation,
			'message'  => $field->validation_message,
		);

		$result = self::validate_character_encoding( $result, $value, $field );

		/**
		 * Allows custom validation of the field value.
		 *
		 * @since Unknown
		 * @since 2.6.4 Added the $context param.
		 * @since 2.7   Moved from GFFormDisplay::validate().
		 *
		 * @param array    $result   {
		 *    An array containing the validation result properties.
		 *
		 *    @type bool  $is_valid The field validation result.
		 *    @type array $message  The field validation message.
		 * }
		 * @param mixed    $value    The field value currently being validated.
		 * @param array    $form     The form currently being validated.
		 * @param GF_Field $field    The field currently being validated.
		 * @param string   $context  The context for the current submission. Possible values: form-submit, api-submit, api-validate.
		 */
		$result = gf_apply_filters( array( 'gform_field_validation', $form['id'], $field->id ), $result, $value, $form, $field, $context );

		$field->failed_validation = ! rgar( $result, 'is_valid' );
		$field->validation_message = rgar( $result, 'message' );

		return $result;
	}

	/**
	 * Checks for valid character encoding in the submitted value of the given field.
	 *
	 * @since 2.7.14
	 *
	 * @param array    $result   {
	 *     An array containing the validation result properties.
	 *
	 *     @type bool  $is_valid The field validation result.
	 *     @type array $message  The field validation message.
	 *  }
	 * @param mixed    $value    The field value currently being validated.
	 * @param GF_Field $field    The field currently being validated.
	 *
	 * @return array
	 */
	public static function validate_character_encoding( $result, $value, $field ) {
		if ( GFCommon::is_empty_array( $value ) || ! in_array( $field->get_input_type(), array( 'textarea', 'text', 'post_title', 'post_content', 'address', 'name' ) ) || ! rgar( $result, 'is_valid' ) ) {
			return $result;
		}

		$event = sprintf( '%d()', __METHOD__ );
		GFCommon::timer_start( $event );
		GFCommon::log_debug( __METHOD__ . "(): Starting invalid characters validation for field: {$field->label} ({$field->id} - {$field->type})" );

		global $wpdb;

		static $charset;

		if ( is_null( $charset ) ) {
			$charset = $wpdb->get_col_charset( GFFormsModel::get_entry_meta_table_name(), 'meta_value' );
			GFCommon::log_debug( __METHOD__ . '(): gf_entry_meta meta_value charset = ' . print_r( $charset, true ) ); //phpcs:ignore
		}

		static $reflected = array();

		if ( empty( $reflected ) ) {
			GFCommon::log_debug( __METHOD__ . '(): reflecting methods' );
			$to_reflect = array( 'check_ascii', 'strip_invalid_text' );

			foreach ( $to_reflect as $name ) {
				$reflected[ $name ] = new ReflectionMethod( $wpdb, $name );
				$reflected[ $name ]->setAccessible( true );
			}
		}

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		$values = array_values( $value );

		$is_ascii = true;

		foreach ( $values as $field_value ) {
			if ( empty( $field_value ) ) {
				continue;
			}

			$is_ascii = $reflected['check_ascii']->invoke( $wpdb, $field_value );

			if ( ! $is_ascii ) {
				break;
			}
		}

		if ( $is_ascii ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Completed in %F seconds. Value is valid ascii', GFCommon::timer_end( $event ) ) );

			return $result;
		}

		foreach ( $values as $field_value ) {
			$data = array(
				'value'   => $field_value,
				'charset' => $charset,
				'ascii'   => false,
				'length'  => false,
			);

			$log_value = json_encode( $field_value, JSON_INVALID_UTF8_SUBSTITUTE ); //phpcs:ignore
			if ( ! $log_value ) {
				$log_value = $field_value;
			}

			$data_check = $reflected['strip_invalid_text']->invoke( $wpdb, array( $data ) );

			if ( ! is_wp_error( $data_check ) && $data_check[0]['value'] != $field_value ) {
				$result['is_valid'] = false;
				$result['message']  = esc_html__( 'The text entered contains invalid characters.', 'gravityforms' );
				GFCommon::log_debug( __METHOD__ . '(): Value to validate = ' . $log_value );
				GFCommon::log_debug( __METHOD__ . '(): Value contains invalid characters. Cleaned value = ' . json_encode( $data_check[0]['value'] ) ); //phpcs:ignore
				break;
			}
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Completed in %F seconds.', GFCommon::timer_end( $event ) ) );

		return $result;
	}

	/**
	 * Determines if the supplied field is suitable for validation.
	 *
	 * @since 2.4.19
	 * @since 2.4.20 Added the second param.
	 *
	 * @param GF_Field $field           The field being processed.
	 * @param bool     $type_check_only Indicates if only the field type property should be evaluated.
	 *
	 * @return bool
	 */
	public static function is_field_validation_supported( $field, $type_check_only = false ) {
		$is_valid_type = ! in_array( $field->type, array( 'html', 'page', 'section' ) );

		if ( ! $is_valid_type || $type_check_only ) {
			return $is_valid_type;
		}

		return ! ( $field->is_administrative() || $field->visibility === 'hidden' );
	}

	/**
	 * Determines if the current form submission is empty.
	 *
	 * @since unknown
	 * @since 2.4.19 Updated to use GFFormDisplay::is_field_validation_supported().
	 *
	 * @param array $form The form being processed.
	 *
	 * @return bool
	 */
	public static function is_form_empty( $form ) {

		foreach ( $form['fields'] as $field ) {
			if ( self::is_field_validation_supported( $field, true ) && ! $field->is_field_hidden && ! self::is_empty( $field, $form['id'] ) ) {
				return false;
			}
		}

		return true;
	}

	public static function failed_state_validation( $form_id, $field, $value ) {

		global $_gf_state;

		if ( ! $field->is_state_validation_supported() ) {
			return false;
		}

		if ( ! isset( $_gf_state ) ) {

			if ( empty( $_POST["state_{$form_id}"] ) || ! is_string( $_POST["state_{$form_id}"] ) ) {
				return true;
			}

			$state = json_decode( base64_decode( $_POST[ "state_{$form_id}" ] ), true );

			if ( ! $state || ! is_array( $state ) || sizeof( $state ) != 2 ) {
				return true;
			}

			//making sure state wasn't tampered with by validating checksum
			$checksum = wp_hash( crc32( $state[0] ) );

			if ( $checksum !== $state[1] ) {
				return true;
			}

			$_gf_state = json_decode( $state[0], true );
		}

		if ( ! is_array( $value ) ) {
			$value = array( $field->id => $value );
		}

		foreach ( $value as $key => $input_value ) {
			$state = isset( $_gf_state[ $key ] ) ? $_gf_state[ $key ] : false;

			//converting price to a number for single product fields and single shipping fields
			if ( ( in_array( $field->inputType, array( 'singleproduct', 'hiddenproduct' ) ) && $key == $field->id . '.2' ) || $field->inputType == 'singleshipping' ) {
				$input_value = GFCommon::to_number( $input_value );
			}

			$sanitized_input_value = wp_kses( $input_value, wp_kses_allowed_html( 'post' ) );

			$hash 			= wp_hash( $input_value );
			$sanitized_hash = wp_hash( $sanitized_input_value );

			$fails_hash 			= strlen( $input_value ) > 0 && $state !== false && ( ( is_array( $state ) && ! in_array( $hash, $state ) ) || ( ! is_array( $state ) && $hash != $state ) );
			$fails_sanitized_hash = strlen( $sanitized_input_value ) > 0 && $state !== false && ( ( is_array( $state ) && ! in_array( $sanitized_hash, $state ) ) || ( ! is_array( $state ) && $sanitized_hash != $state ) );

			if ( $fails_hash && $fails_sanitized_hash ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueues scripts/styles for forms embedded via blocks and shortcodes.
	 *
	 * @since unknown
	 * @since 2.4.18 Added support for blocks and the gform_post_enqueue_scripts hook.
	 */
	public static function enqueue_scripts() {
		global $wp_query;

		if ( ! isset( $wp_query->posts ) || ! is_array( $wp_query->posts ) ) {
			return;
		}

		foreach ( $wp_query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$found_forms = $found_blocks = array();
			self::parse_forms( $post->post_content, $found_forms, $found_blocks );

			if ( ! empty( $found_forms ) ) {
				foreach ( $found_forms as $form_id => $attributes ) {
					$form = GFAPI::get_form( $form_id );
					$ajax  = $attributes['ajax'];
					$form['theme'] = ! rgempty( 'theme', $attributes ) ? $attributes['theme'] : GFForms::get_default_theme();
					unset( $attributes['theme'] ); // removing theme from styles for consistency. $form['theme'] should be used instead.
					$form['styles'] = self::get_form_styles( $attributes );

					if ( $form && $form['is_active'] && ! $form['is_trash'] ) {
						self::enqueue_form_scripts( $form, $ajax, $form['theme'] );
					}
				}

				/**
				 * Allows custom actions to be performed when scripts/styles are enqueued.
				 *
				 * @since 2.4.18
				 *
				 * @param array   $found_forms  An array of found forms using the form ID as the key to the ajax status.
				 * @param array   $found_blocks An array of found GF blocks.
				 * @param WP_Post $post         The post which was processed.
				 */
				do_action( 'gform_post_enqueue_scripts', $found_forms, $found_blocks, $post );
			}
		}
	}

	/**
	 * Parses the supplied post content for forms embedded via blocks and shortcodes.
	 *
	 * @since 2.4.18
	 *
	 * @param string $post_content The post content to be parsed.
	 * @param array  $found_forms  An array of found forms using the form ID as the key to the ajax status.
	 * @param array  $found_blocks An array of found GF blocks.
	 */
	public static function parse_forms( $post_content, &$found_forms, &$found_blocks ) {
		if ( empty( $post_content ) ) {
			return;
		}

		self::parse_forms_from_shortcode( $post_content, $found_forms );

		if ( ! function_exists( 'has_blocks' ) || ! has_blocks( $post_content ) ) {
			return;
		}

		self::parse_forms_from_blocks( parse_blocks( $post_content ), $found_forms, $found_blocks );
	}

	/**
	 * Finds forms embedded in the supplied blocks.
	 *
	 * @since 2.4.18
	 *
	 * @param array $blocks       The blocks found in the post content.
	 * @param array $found_forms  An array of found forms using the form ID as the key to the ajax status.
	 * @param array $found_blocks An array of found GF blocks.
	 */
	public static function parse_forms_from_blocks( $blocks, &$found_forms, &$found_blocks ) {
		if ( ! method_exists( 'GF_Blocks', 'get_all_types' ) ) {
			return;
		}

		$block_types = GF_Blocks::get_all_types();

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( $block['blockName'] === 'core/block' ) {
				self::parse_forms_from_reusable_block( $block, $found_forms, $found_blocks );
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::parse_forms_from_blocks( $block['innerBlocks'], $found_forms, $found_blocks );
				continue;
			}

			$supported_type = in_array( $block['blockName'], $block_types, true );

			if ( ! $supported_type || ( $supported_type && empty( $block['attrs']['formId'] ) ) ) {
				continue;
			}

			$found_blocks[] = $block;

			// Get the form ID and AJAX attributes.
			$form_id = (int) $block['attrs']['formId'];
			$ajax    = isset( $block['attrs']['ajax'] ) ? (bool) $block['attrs']['ajax'] : false;

			if ( self::is_applicable_form( $form_id, $ajax, $found_forms ) ) {
				$found_forms[ $form_id ] = $block['attrs'];
				$found_forms[ $form_id ]['ajax']  = $ajax;
				$found_forms[ $form_id ]['theme'] = isset( $found_forms[ $form_id ]['theme'] ) ? $found_forms[ $form_id ]['theme'] : '';
			}
		}
	}

	/**
	 * Finds forms embedded in the supplied reusable block.
	 *
	 * @since 2.4.18
	 *
	 * @param array $block        The block to be processed.
	 * @param array $found_forms  An array of found forms using the form ID as the key to the ajax status.
	 * @param array $found_blocks An array of found GF blocks.
	 */
	public static function parse_forms_from_reusable_block( $block, &$found_forms, &$found_blocks ) {
		if ( empty( $block['attrs']['ref'] ) ) {
			return;
		}

		$reusable_block = get_post( $block['attrs']['ref'] );

		if ( empty( $reusable_block ) || $reusable_block->post_type !== 'wp_block' ) {
			return;
		}

		self::parse_forms( $reusable_block->post_content, $found_forms, $found_blocks );
	}

	/**
	 * Finds forms embedded in the supplied post content.
	 *
	 * @since 2.4.18
	 *
	 * @param string $post_content The post content to be processed.
	 * @param array  $found_forms  An array of found forms using the form ID as the key to the ajax status.
	 */
	public static function parse_forms_from_shortcode( $post_content, &$found_forms ) {
		if ( empty( $post_content ) ) {
			return;
		}

		if ( preg_match_all( '/\[gravityform[s]? +.*?((id=.+?)|(name=.+?))\]/is', $post_content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$attr    = shortcode_parse_atts( $match[1] );
				$form_id = rgar( $attr, 'id' );

				if ( empty( $form_id ) && ! empty( $attr['name'] ) ) {
					$form_id = $attr['name'];
				}

				if ( $form_id && ! is_numeric( $form_id ) ) {
					$form_id = GFFormsModel::get_form_id( $form_id );
				}

				if ( empty( $form_id ) ) {
					continue;
				}

				$form_id = (int) $form_id;
				$ajax    = isset( $attr['ajax'] ) && strtolower( substr( $attr['ajax'], 0, 4 ) ) == 'true';
				$styles  = json_decode( rgar( $attr, 'styles' ), true ) ? json_decode( rgar( $attr, 'styles' ), true ) : array();
				if ( self::is_applicable_form( $form_id, $ajax, $found_forms ) ) {
					$found_forms[ $form_id ]           = $styles;
					$found_forms[ $form_id ]['ajax']   = $ajax;
					$found_forms[ $form_id ]['theme']  = rgar( $attr, 'theme' );
				}
			}
		}
	}

	/**
	 * Determines if the supplied form ID should be added to the found forms array.
	 *
	 * @since 2.4.18
	 *
	 * @param int   $form_id     The form ID to be checked.
	 * @param bool  $ajax        Indicates if Ajax is enabled for the found form.
	 * @param array $found_forms An array of found forms using the form ID as the key to the ajax status.
	 *
	 * @return bool
	 */
	public static function is_applicable_form( $form_id, $ajax, $found_forms ) {
		return ! isset( $found_forms[ $form_id ] ) || ( isset( $found_forms[ $form_id ] ) && true === $ajax && false === $found_forms[ $form_id ] );
	}

	/**
	 * Returns forms embedded in the supplied post content.
	 *
	 * @since unknown
	 * @since 2.4.18 Updated to use GFFormDisplay::parse_forms_from_shortcode().
	 *
	 * @param string $post_content The post content to be processed.
	 * @param bool   $ajax         Indicates if Ajax is enabled for at least one of the forms.
	 *
	 * @return array
	 */
	public static function get_embedded_forms( $post_content, &$ajax ) {
		$found_forms = $forms = array();
		self::parse_forms_from_shortcode( $post_content, $found_forms );

		if ( empty( $found_forms ) ) {
			return $forms;
		}

		foreach ( $found_forms as $form_id => $is_ajax ) {
			$forms[] = GFAPI::get_form( $form_id );
			if ( ! $ajax && $is_ajax ) {
				$ajax = true;
			}
		}

		return $forms;
	}

	/**
	 * Get the various enqueueable assets for a given form.
	 *
	 * @since 2.5
	 * @since 2.7 Added $theme parameter
	 *
	 * @param array $form An array representing the current Form object.
	 * @param string $theme The theme slug for the form.
	 *
	 * @return GF_Asset[]
	 */
	public static function get_form_enqueue_assets( $form, $theme = null ) {
		$assets = array();

		if ( ! GFCommon::is_frontend_default_css_disabled() ) {

			if ( GFCommon::is_legacy_markup_enabled( $form ) ) {

				/**
				 * Allows users to disable legacy CSS files from being loaded on the Front End.
				 *
				 * @since 2.5-beta-rc-3
				 *
				 * @param boolean Whether to disable legacy css.
				 */
				$disable_legacy_css = apply_filters( 'gform_disable_form_legacy_css', false );

				if ( ! $disable_legacy_css ) {

					$assets[] = new GF_Style_Asset( 'gforms_reset_css' );

					if ( self::has_datepicker_field( $form ) ) {
						$assets[] = new GF_Style_Asset( 'gforms_datepicker_css' );
					}

					$assets[] = new GF_Style_Asset( 'gforms_formsmain_css' );
					$assets[] = new GF_Style_Asset( 'gforms_ready_class_css' );
					$assets[] = new GF_Style_Asset( 'gforms_browsers_css' );

					if ( is_rtl() ) {
						$assets[] = new GF_Style_Asset( 'gforms_rtl_css' );
					}
				}

			} else {
				// Theme related styles will be enqueued by the theme layer process initiated by form-display/block-styles/block-styles-handler.php
			}

			if ( self::has_password_visibility( $form ) ) {
				$assets[] = new GF_Style_Asset( 'dashicons' );
			}
		}

		$assets[] = new GF_Script_Asset( 'wp-a11y' );

		$gf_main = new GF_Script_Asset( 'gform_gravityforms' );

		if ( self::has_checkbox_field( $form, true ) ) {
			$gf_main->add_localize_data( 'gf_field_checkbox', array(
				'strings' => array(
					'selected'   => wp_strip_all_tags( __( 'All choices are selected.', 'gravityforms' ) ),
					'deselected' => wp_strip_all_tags( __( 'All choices are unselected.', 'gravityforms' ) ),
				),
			) );
		}

		if ( self::has_fileupload_field( $form ) ) {
			$gf_main->add_localize_data( 'gf_legacy', array( 'is_legacy' => GFCommon::is_legacy_markup_enabled( $form ) ) );

			GFCommon::localize_gform_gravityforms_multifile();

			if ( ! GFCommon::is_legacy_markup_enabled( $form ) ) {
				$assets[] = new GF_Style_Asset( 'dashicons' );
			}
		}

		$gf_main->add_localize_data( 'gf_global', GFCommon::gf_global( false, true ) );

		$assets[] = $gf_main;

		$has_logic = false;

		add_filter( 'gform_gf_legacy_multi', function( $data ) use ( $form ) {
			$data[ $form['id'] ] = GFCommon::is_legacy_markup_enabled( $form );

			return $data;
		}, 10, 1 );

		if ( self::has_conditional_logic( $form ) ) {
			$has_logic = true;
		}

		if ( self::has_page_conditional_logic( $form ) ) {
			$assets[]  = new GF_Script_Asset( 'gform_page_conditional_logic' );
			$has_logic = true;
		}

		// Conditional logic script is required for any type of conditional logic (page or field-level). Enqueue it if true.
		if ( $has_logic ) {
			$gf_conditional_logic = new GF_Script_Asset( 'gform_conditional_logic' );
			$gf_conditional_logic->add_localize_data( 'gf_legacy', array( 'is_legacy' => GFCommon::is_legacy_markup_enabled( $form ) ) );
			$assets[] = $gf_conditional_logic;
		}

		if ( self::has_datepicker_field( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gform_datepicker_init' );
		}

		if ( self::has_recaptcha_field( $form ) ) {
			$language = self::get_recaptcha_language( $form );
			$assets[] = new GF_Script_Asset( 'gform_recaptcha', esc_url( sprintf( 'https://www.google.com/recaptcha/api.js?hl=%s&render=explicit', $language ) ) );
		}

		if ( self::has_password_strength( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gforms_zxcvbn', includes_url( '/js/zxcvbn.min.js' ) );
			$assets[] = new GF_Script_Asset( 'password-strength-meter' );
		}

		if ( GFCommon::has_multifile_fileupload_field( $form ) ) {
			$assets[] = new GF_Script_Asset( 'plupload-all' );
		}

		if ( self::has_enhanced_dropdown( $form ) || self::has_pages( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gform_json' );
		}

		if ( self::has_character_counter( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gform_textarea_counter' );
		}

		if ( self::has_input_mask( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gform_masked_input' );
		}

		if ( self::has_enhanced_dropdown( $form ) ) {
			if ( wp_script_is( 'chosen', 'registered' ) ) {
				$assets[] = new GF_Script_Asset( 'chosen' );
			} else {
				$assets[] = new GF_Script_Asset( 'gform_chosen' );
			}
		}

		if ( self::has_placeholder( $form ) ) {
			$assets[] = new GF_Script_Asset( 'gform_placeholder' );
		}

		// enqueue jQuery every time form is displayed to allow 'gform_post_render' js hook
		// to be available to users even when GF is not using it
		$assets[] = new GF_Script_Asset( 'jquery' );

		return $assets;
	}

	/**
	 * Enqueue the required scripts for this form.
	 *
	 * @since 2.7 Added the $theme parameter
	 *
	 * @param array $form An array representing the current Form object.
	 * @param false $ajax Whether this is being requested via AJAX.
	 * @param string $theme The form theme slug.
	 *
	 * @return void
	 */
	public static function enqueue_form_scripts( $form, $ajax = false, $theme = null ) {

		// adding pre enqueue scripts hook so that scripts can be added first if a need exists
		/**
		 * Fires before any scripts are enqueued (form specific using the ID as well)
		 *
		 * @param array $form The Form Object
		 * @param bool  $ajax Whether AJAX is on or off (True or False)
		 */
		gf_do_action( array( 'gform_pre_enqueue_scripts', $form['id'] ), $form, $ajax );

		add_filter( 'script_loader_tag', array( 'GFFormDisplay', 'add_script_defer' ), 10, 2 );

		$assets = self::get_form_enqueue_assets( $form, $theme );

		foreach( $assets as $asset ) {
			/**
			 * @var GF_Asset $asset
			 */
			$asset->enqueue_asset();
		}

        /**
         * Fires after any scripts are enqueued (form specific using the ID as well)
         *
         * @param array $form The Form Object
         * @param bool  $ajax Whether AJAX is on or off (True or False)
         */
		gf_do_action( array( 'gform_enqueue_scripts', $form['id'] ), $form, $ajax );
	}

	/**
	 * Add defer attribute to Gravity Forms scripts and any script dependent on a Gravity Forms script.
	 *
	 * @since 2.5
	 *
	 * @param string $tag    The complete script markup that will be output.
	 * @param string $handle The handle of the current script.
	 *
	 * @return string
	 */
	public static function add_script_defer( $tag, $handle ) {
		// If this is one of our scripts, let's defer it.
		if( strpos( $handle, 'gform_' ) !== false && strpos( $tag, ' defer' ) === false ) {
			$tag = str_replace( ' src', " defer='defer' src", $tag );
		}
		// Otherwise, let's hunt for scripts that have our scripts as dependencies of other scripts and defer those scripts too.
		else {
			global $wp_scripts;
			$script = rgar( $wp_scripts->registered, $handle );
			if ( $script && ! empty( $script->deps ) ) {
				foreach( $script->deps as $dep_handle ) {
					$tag = self::add_script_defer( $tag, $dep_handle );
				}
			}
		}
		return $tag;
	}

	private static $printed_scripts = array();

	/**
	 * Print the required scripts for this form, since we're hooking in after enqueues have processed.
	 *
	 * @param array $form An array representing the current Form object.
	 * @param false $ajax Whether this is being requested via AJAX.
	 *
	 * @return void
	 */
	public static function print_form_scripts( $form, $ajax ) {
		// adding pre print scripts hook so that scripts can be added first if a need exists
		/**
		 * Fires before any scripts are printed (form specific using the ID as well)
		 *
		 * @since 2.5
		 *
		 * @param array $form The Form Object
		 * @param bool  $ajax Whether AJAX is on or off (True or False)
		 */
		gf_do_action( array( 'gform_pre_print_scripts', $form['id'] ), $form, $ajax );

		add_filter( 'script_loader_tag', array( 'GFFormDisplay', 'add_script_defer' ), 10, 2 );

		$assets = self::get_form_enqueue_assets( $form );

		foreach( $assets as $asset ) {
			/**
			 * @var GF_Asset $asset
			 */
			$asset->print_asset();
		}

		/**
		 * Fires after any scripts are enqueued (form specific using the ID as well)
		 *
		 * @since 2.5
		 *
		 * @param array $form The Form Object
		 * @param bool  $ajax Whether AJAX is on or off (True or False)
		 */
		gf_do_action( array( 'gform_print_scripts', $form['id'] ), $form, $ajax );
	}

	/**
	 * Check if a form has any Image Choice fields.
	 *
	 * @since 2.8
	 *
	 * @param $form
	 *
	 * @return mixed|null
	 */
	public static function has_image_choices( $form ) {
		$has_image_choices = GFAPI::get_fields_by_type( $form, 'image_choice' ) ? true : false;

		/**
		 * A filter to determine if a form has image choices.
		 *
		 * @param bool $has_image_choices True or False if there are any image choice fields in the form
		 * @param array $form The Current form object
		 */
		return apply_filters( 'gform_has_image_choices', $has_image_choices, $form );
	}

	public static function has_conditional_logic( $form ) {
		$has_conditional_logic = self::has_conditional_logic_legwork( $form );

		/**
		 * A filter that runs through a form that has conditional logic
		 *
		 * @param bool $has_conditional_logic True or False if the user has conditional logic active in their current form settings
		 * @param array $form The Current form object
		 */
		return apply_filters( 'gform_has_conditional_logic', $has_conditional_logic, $form );
	}

	private static function has_conditional_logic_legwork( $form ) {

		if ( empty( $form ) ) {
			return false;
		}

		if ( isset( $form['button']['conditionalLogic'] ) ) {
			return true;
		}

		if ( is_array( rgar( $form, 'fields' ) ) ) {
			foreach ( rgar( $form, 'fields' ) as $field ) {
				if ( isset( $field->fields ) && is_array( $field->fields ) && self::has_conditional_logic_legwork( array( 'fields' => $field->fields ) ) ) {
					return true;
				}
				if ( ! empty( $field->conditionalLogic ) ) {
					return true;
				} else if ( isset( $field->nextButton ) && ! empty( $field->nextButton['conditionalLogic'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines if the form has page conditional logic.
	 *
	 * @since 2.5
	 *
	 * @param array $form Form object.
	 *
	 * @return bool
	 */
	public static function has_page_conditional_logic( $form ) {
		if ( ! GFCommon::form_has_fields( $form ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'page' && ! empty( $field->conditionalLogic ) && is_array( $field->conditionalLogic ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get init script and all necessary data for conditional logic.
	 *
	 * @todo: Replace much of the field value retrieval with a get_original_value() method in GF_Field class.
	 *
	 * @param       $form
	 * @param array $field_values
	 *
	 * @return string
	 */
	private static function get_conditional_logic( $form, $field_values = array() ) {
		$logics            = '';
		$dependents        = '';
		$fields_with_logic = array();
		$default_values    = array();
		$field_dependents  = array();

		foreach ( $form['fields'] as $field ) {

			/* @var GF_Field $field */

			$field_deps = self::get_conditional_logic_fields( $form, $field->id );
			$field_dependents[ $field->id ] = ! empty( $field_deps ) ? $field_deps : array();

			//use section's logic if one exists
			$section       = RGFormsModel::get_section( $form, $field->id );
			$section_logic = ! empty( $section ) ? $section->conditionalLogic : null;

			$field_logic = $field->type != 'page' ? $field->conditionalLogic : null; //page break conditional logic will be handled during the next button click

			$next_button_logic = ! empty( $field->nextButton ) && ! empty( $field->nextButton['conditionalLogic'] ) ? $field->nextButton['conditionalLogic'] : null;

			if ( ! empty( $field_logic ) || ! empty( $next_button_logic ) ) {

				$field_section_logic = array( 'field' => $field_logic, 'nextButton' => $next_button_logic, 'section' => $section_logic );

				$logics .= $field->id . ': ' . GFCommon::json_encode( $field_section_logic ) . ',';

				$fields_with_logic[] = $field->id;

				$peers    = $field->type == 'section' ? GFCommon::get_section_fields( $form, $field->id ) : array( $field );
				$peer_ids = array();

				foreach ( $peers as $peer ) {
					$peer_ids[] = $peer->id;
				}

				$dependents .= $field->id . ': ' . GFCommon::json_encode( $peer_ids ) . ',';
			}

			//-- Saving default values so that they can be restored when toggling conditional logic ---
			$field_val  = '';
			$input_type = $field->get_input_type();
			$inputs     = $field->get_entry_inputs();

			//get parameter value if pre-populate is enabled
			if ( $field->allowsPrepopulate ) {
				if ( $input_type == 'checkbox' || $input_type == 'multiselect' ) {
					$field_val = RGFormsModel::get_parameter_value( $field->inputName, $field_values, $field );
					if ( ! is_array( $field_val ) ) {
						$field_val = explode( ',', $field_val );
					}
				} elseif ( is_array( $inputs ) ) {
					$field_val = array();
					foreach ( $inputs as $input ) {
						$field_val[ $input['id'] ] = RGFormsModel::get_parameter_value( rgar( $input, 'name' ), $field_values, $field );
					}
				} elseif ( $input_type == 'time' ) { // maintained for backwards compatibility. The Time field now has an inputs array.
					$parameter_val = RGFormsModel::get_parameter_value( $field->inputName, $field_values, $field );
					if ( ! empty( $parameter_val ) && ! is_array( $parameter_val ) && preg_match( '/^(\d*):(\d*) ?(.*)$/', $parameter_val, $matches ) ) {
						$field_val   = array();
						$field_val[] = esc_attr( $matches[1] ); //hour
						$field_val[] = esc_attr( $matches[2] ); //minute
						$field_val[] = rgar( $matches, 3 );     //am or pm
					}
				} elseif ( $input_type == 'list' ) {
					$parameter_val = RGFormsModel::get_parameter_value( $field->inputName, $field_values, $field );
					$field_val     = is_array( $parameter_val ) ? $parameter_val : explode( ',', str_replace( '|', ',', $parameter_val ) );

					if ( is_array( rgar( $field_val, 0 ) ) ) {
						$list_values = array();
						foreach ( $field_val as $row ) {
							$list_values = array_merge( $list_values, array_values( $row ) );
						}
						$field_val = $list_values;
					}
				} else {
					$field_val = RGFormsModel::get_parameter_value( $field->inputName, $field_values, $field );
				}
			}

			//use default value if pre-populated value is empty
			$field_val = $field->get_value_default_if_empty( $field_val );

			if ( is_array( $field->choices ) && $input_type != 'list' ) {

				//radio buttons start at 0 and checkboxes start at 1
				$choice_index     = $input_type == 'radio' ? 0 : 1;
				$is_pricing_field = GFCommon::is_pricing_field( $field->type );

				foreach ( $field->choices as $choice ) {

					if ( $input_type == 'checkbox' && ( $choice_index % 10 ) == 0 ){
						$choice_index++;
					}

					$is_prepopulated    = is_array( $field_val ) ? in_array( $choice['value'], $field_val ) : $choice['value'] == $field_val;
					$is_choice_selected = rgar( $choice, 'isSelected' ) || $is_prepopulated;

					if ( $is_choice_selected ) {
						// Select
						if ( $input_type == 'select' ) {
							$price                        = GFCommon::to_number( rgar( $choice, 'price' ) ) == false ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
							$val                          = $is_pricing_field && $field->type != 'quantity' ? $choice['value'] . '|' . $price : $choice['value'];
							$default_values[ $field->id ] = $val;
						}
						else {
							if ( ! isset( $default_values[ $field->id ] ) ) {
								$default_values[ $field->id ] = array();
							}
							// Multiselect
							if ( $input_type == 'multiselect' ) {
								$default_values[ $field->id ][] = $choice['value'];
							}
							// Checkboxes & Radio Buttons
							else {
								$default_values[ $field->id ][] = "choice_{$form['id']}_{$field->id}_{$choice_index}";
							}
						}
					}
					$choice_index ++;
				}
			} elseif ( ! rgblank( $field_val ) ) {

				switch ( $input_type ) {
					case 'date':
						// for date fields; that are multi-input; and where the field value is a string
						// (happens with prepop, default value will always be an array for multi-input date fields)
						if ( is_array( $field->inputs ) && ( ! is_array( $field_val ) || ! isset( $field_val['m'] ) ) ) {

							$format    = empty( $field->dateFormat ) ? 'mdy' : esc_attr( $field->dateFormat );
							$date_info = GFcommon::parse_date( $field_val, $format );

							// converts date to array( 'm' => 1, 'd' => '13', 'y' => '1987' )
							$field_val = $field->get_date_array_by_format( array( $date_info['month'], $date_info['day'], $date_info['year'] ) );

						}
						break;
					case 'time':
						if ( is_array( $field_val ) ) {
							$ampm_key               = key( array_slice( $field_val, - 1, 1, true ) );
							$field_val[ $ampm_key ] = strtolower( $field_val[ $ampm_key ] );
						}
						break;
					case 'address':

						$state_input_id = sprintf( '%s.4', $field->id );
						if ( isset( $field_val[ $state_input_id ] ) && ! $field_val[ $state_input_id ] ) {
							$field_val[ $state_input_id ] = $field->defaultState;
						}

						$country_input_id = sprintf( '%s.6', $field->id );
						if ( isset( $field_val[ $country_input_id ] ) && ! $field_val[ $country_input_id ] ) {
							$field_val[ $country_input_id ] = $field->defaultCountry;
						}

						break;
				}

				$default_values[ $field->id ] = $field_val;

			}

		}

		//adding form button conditional logic if enabled
		if ( isset( $form['button']['conditionalLogic'] ) ) {
			$logics .= '0: ' . GFCommon::json_encode( array( 'field' => $form['button']['conditionalLogic'], 'section' => null ) ) . ',';
			$dependents .= '0: ' . GFCommon::json_encode( array( 0 ) ) . ',';
			$fields_with_logic[] = 0;
		}

		if ( ! empty( $logics ) ) {
			$logics = substr( $logics, 0, strlen( $logics ) - 1 );
		} //removing last comma;

		if ( ! empty( $dependents ) ) {
			$dependents = substr( $dependents, 0, strlen( $dependents ) - 1 );
		} //removing last comma;

		$animation = rgar( $form, 'enableAnimation' ) ? '1' : '0';
		global $wp_locale;
		$number_format = $wp_locale->number_format['decimal_point'] == ',' ? 'decimal_comma' : 'decimal_dot';

		$str = "if(window['jQuery']){" .

			"if(!window['gf_form_conditional_logic'])" .
			"window['gf_form_conditional_logic'] = new Array();" .
		    "window['gf_form_conditional_logic'][{$form['id']}] = { logic: { {$logics} }, dependents: { {$dependents} }, animation: {$animation}, defaults: " . json_encode( $default_values ) . ", fields: " . json_encode( $field_dependents ) . " }; " .

			"if(!window['gf_number_format'])" .
			"window['gf_number_format'] = '" . $number_format . "';" .

			'jQuery(document).ready(function(){' .
			"gform.utils.trigger({ event: 'gform/conditionalLogic/init/start', native: false, data: { formId: {$form['id']}, fields: null, isInit: true } });" .
            "window['gformInitPriceFields']();" .
	        "gf_apply_rules({$form['id']}, " . json_encode( $fields_with_logic ) . ', true);' .
			"jQuery('#gform_wrapper_{$form['id']}').show();" .
			"jQuery('#gform_wrapper_{$form['id']} form').css('opacity', '');" .
			"jQuery(document).trigger('gform_post_conditional_logic', [{$form['id']}, null, true]);" .
			"gform.utils.trigger({ event: 'gform/conditionalLogic/init/end', native: false, data: { formId: {$form['id']}, fields: null, isInit: true } });" .

			'} );' .

			'} ';

		return $str;
	}

	/**
	 * Get conditional logic rules from page fields in a form.
	 *
	 * @since 2.5
	 *
	 * @param $form
	 *
	 * @return string
	 */
	private static function get_page_conditional_logic( $form ) {
		$page_fields = array();

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'page' ) {
				$page_fields[] = array(
					'fieldId'          => $field->id,
					'conditionalLogic' => $field->conditionalLogic,
					'nextButton'       => $field->nextButton,
				);
			}
		}

		$args = array(
			'formId'     => $form['id'],
			'formButton' => $form['button'],
			'pagination' => $form['pagination'],
			'pages'      => $page_fields,
		);

		return sprintf( '; new GFPageConditionalLogic( %s );', json_encode( $args ) );
	}


	/**
	 * Enqueue and retrieve all inline scripts that should be executed when the form is rendered.
	 * Use add_init_script() function to enqueue scripts.
	 *
	 * @param array $form
	 * @param array $field_values
	 * @param bool  $is_ajax
	 */
	public static function register_form_init_scripts( $form, $field_values = array(), $is_ajax = false ) {

		if ( rgars( $form, 'save/enabled' ) ) {
			$save_script = "jQuery('#gform_save_{$form['id']}').val('');";
			self::add_init_script( $form['id'], 'save', self::ON_PAGE_RENDER, $save_script );
		}

		// adding conditional logic script if conditional logic is configured for this form.
		// get_conditional_logic also adds the chosen script for the enhanced dropdown option.
		// if this form does not have conditional logic, add chosen script separately
		if ( self::has_conditional_logic( $form ) ) {
			self::add_init_script( $form['id'], 'number_formats', self::ON_PAGE_RENDER, self::get_number_formats_script( $form ) );
			self::add_init_script( $form['id'], 'conditional_logic', self::ON_PAGE_RENDER, self::get_conditional_logic( $form, $field_values ) );
		}

		if ( self::has_page_conditional_logic( $form ) ) {
			self::add_init_script( $form['id'], 'page_conditional_logic', self::ON_PAGE_RENDER, self::get_page_conditional_logic( $form ) );
		}

		//adding currency config if there are any product fields in the form
		if ( self::has_price_field( $form ) ) {
			self::add_init_script( $form['id'], 'number_formats', self::ON_PAGE_RENDER, self::get_number_formats_script( $form ) );
			self::add_init_script( $form['id'], 'pricing', self::ON_PAGE_RENDER, self::get_pricing_init_script( $form ) );
		}

		if ( self::has_password_strength( $form ) ) {
			$password_script = self::get_password_strength_init_script( $form );
			self::add_init_script( $form['id'], 'password', self::ON_PAGE_RENDER, $password_script );
		}

		if ( self::has_enhanced_dropdown( $form ) ) {
			$chosen_script = self::get_chosen_init_script( $form );
			self::add_init_script( $form['id'], 'chosen', self::ON_PAGE_RENDER, $chosen_script );
			self::add_init_script( $form['id'], 'chosen', self::ON_CONDITIONAL_LOGIC, $chosen_script );
		}

		if ( self::has_character_counter( $form ) ) {
			self::add_init_script( $form['id'], 'character_counter', self::ON_PAGE_RENDER, self::get_counter_init_script( $form ) );
		}

		if ( self::has_input_mask( $form ) ) {
			self::add_init_script( $form['id'], 'input_mask', self::ON_PAGE_RENDER, self::get_input_mask_init_script( $form ) );
		}

		if ( self::has_calculation_field( $form ) ) {
			self::add_init_script( $form['id'], 'number_formats', self::ON_PAGE_RENDER, self::get_number_formats_script( $form ) );
			self::add_init_script( $form['id'], 'calculation', self::ON_PAGE_RENDER, self::get_calculations_init_script( $form ) );
		}

		if ( self::has_currency_format_number_field( $form ) ) {
			self::add_init_script( $form['id'], 'currency_format', self::ON_PAGE_RENDER, self::get_currency_format_init_script( $form ) );
		}

		if ( self::has_currency_copy_values_option( $form ) ) {
			self::add_init_script( $form['id'], 'copy_values', self::ON_PAGE_RENDER, self::get_copy_values_init_script( $form ) );
		}

		if ( self::has_placeholder( $form ) ) {
			self::add_init_script( $form['id'], 'placeholders', self::ON_PAGE_RENDER, self::get_placeholders_init_script( $form ) );
		}

		if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				/* @var GF_Field $field */
				if ( is_subclass_of( $field, 'GF_Field' ) ) {
					$field->register_form_init_scripts( $form );
				}
			}
		}
        /**
         * Fires when inline Gravity Forms scripts are enqueued
         *
         * Used to enqueue additional inline scripts
         *
         * @param array  $form       The Form object
         * @param string $field_vale The current value of the selected field
         * @param bool   $is_ajax    Returns true if using AJAX.  Otherwise, false
         */
		gf_do_action( array( 'gform_register_init_scripts', $form['id'] ), $form, $field_values, $is_ajax );

	}

	public static function get_form_init_scripts( $form ) {

		$script_body = '';

		if ( ! $form ) {
			return $script_body;
		}

		/* rendering initialization scripts */
		$init_scripts = rgar( self::$init_scripts, $form['id'] );

		if ( ! empty( $init_scripts ) ) {
			$script_body = isset( $gf_global_script ) ? $gf_global_script : '';

			$script_body .=
				"gform.initializeOnLoaded( function() { jQuery(document).on('gform_post_render', function(event, formId, currentPage){" .
				"if(formId == {$form['id']}) {";

			foreach ( $init_scripts as $init_script ) {
				if ( $init_script['location'] == self::ON_PAGE_RENDER ) {
					$script_body .= $init_script['script'];
				}
			}

			$script_body .=
				"} " . //keep the space. needed to prevent plugins from replacing }} with ]}
				"} );" .

				"jQuery(document).on('gform_post_conditional_logic', function(event, formId, fields, isInit){";
			foreach ( $init_scripts as $init_script ) {
				if ( $init_script['location'] == self::ON_CONDITIONAL_LOGIC ) {
					$script_body .= $init_script['script'];
				}
			}

			$script_body .= '} ) } );';
		}

		return GFCommon::get_inline_script_tag( $script_body );
	}

	public static function get_chosen_init_script( $form ) {
		$chosen_fields = array();
		foreach ( $form['fields'] as $field ) {
			$input_type = GFFormsModel::get_input_type( $field );
			if ( $field->enableEnhancedUI && in_array( $input_type, array( 'select', 'multiselect' ) ) ) {
				$chosen_fields[] = "#input_{$form['id']}_{$field->id}";
			}
		}

		return "gformInitChosenFields('" . implode( ',', $chosen_fields ) . "','" . esc_attr( gf_apply_filters( array( 'gform_dropdown_no_results_text', $form['id'] ), __( 'No results matched', 'gravityforms' ), $form['id'] ) ) . "');";
	}

	public static function get_currency_format_init_script( $form ) {
		$currency_fields = array();
		foreach ( $form['fields'] as $field ) {
			if ( $field->numberFormat == 'currency' ) {
				$currency_fields[] = "#input_{$form['id']}_{$field->id}";
			}
		}

		return "gformInitCurrencyFormatFields('" . implode( ',', $currency_fields ) . "');";
	}

	public static function get_copy_values_init_script( $form ) {
		$script = "jQuery('.copy_values_activated').on('click', function(){
                        var inputId = this.id.replace('_copy_values_activated', '');
                        jQuery('#' + inputId).toggle(!this.checked);
                    });";

		return $script;
	}

	public static function get_placeholders_init_script( $form ) {

		$script = "if(typeof Placeholders != 'undefined'){
                        Placeholders.enable();
                    }";

		return $script;
	}


	public static function get_counter_init_script( $form ) {
		$script = '';

		/** @var GF_Field $field */
		foreach ( $form['fields'] as $field ) {

			$max_length = absint( $field->maxLength );
			$input_id   = "input_{$form['id']}_{$field->id}";

			if ( ! empty( $max_length ) && ! $field->is_administrative() ) {
				$rte_enabled   = $field instanceof GF_Field_Textarea && $field->is_rich_edit_enabled();
				$truncate      = $rte_enabled ? 'false' : 'true';
				$tinymce_style = $rte_enabled ? ' ginput_counter_tinymce' : '';
				$error_style   = $rte_enabled ? ' ginput_counter_error' : '';

				$field_script =
					"if(!jQuery('#{$input_id}+.ginput_counter').length){jQuery('#{$input_id}').textareaCount(" .
					"    {'maxCharacterSize': {$max_length}," .
					"    'originalStyle': 'ginput_counter gfield_description{$tinymce_style}'," .
					"	 'truncate': {$truncate}," .
					"	 'errorStyle' : '{$error_style}'," .
					"    'displayFormat' : '#input " . esc_js( __( 'of', 'gravityforms' ) ) . ' #max ' . esc_js( __( 'max characters', 'gravityforms' ) ) . "'" .
					"    });" . "jQuery('#{$input_id}').next('.ginput_counter').attr('aria-live','polite');}";

				$script .= gf_apply_filters( array( 'gform_counter_script', $form['id'] ), $field_script, $form['id'], $input_id, $max_length, $field );
			}
		}

		return $script;
	}

	public static function get_pricing_init_script( $form ) {

		return "if(window[\"gformInitPriceFields\"]) jQuery(document).ready(function(){gformInitPriceFields();} );";
	}

	public static function get_password_strength_init_script( $form ) {

		$field_script = "if(!window['gf_text']){window['gf_text'] = new Array();} window['gf_text']['password_blank'] = '" . esc_js( __( 'Strength indicator', 'gravityforms' ) ) . "'; window['gf_text']['password_mismatch'] = '" . esc_js( __( 'Mismatch', 'gravityforms' ) ) . "';window['gf_text']['password_unknown'] = '" . esc_js( __( 'Password strength unknown', 'gravityforms' ) ) . "';window['gf_text']['password_bad'] = '" . esc_js( __( 'Weak', 'gravityforms' ) ) . "'; window['gf_text']['password_short'] = '" . esc_js( __( 'Very weak', 'gravityforms' ) ) . "'; window['gf_text']['password_good'] = '" . esc_js( __( 'Medium', 'gravityforms' ) ) . "'; window['gf_text']['password_strong'] = '" . esc_js( __( 'Strong', 'gravityforms' ) ) . "';";

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'password' && $field->passwordStrengthEnabled ) {
				$field_id = "input_{$form['id']}_{$field->id}";
				$field_script .= "gformShowPasswordStrength(\"$field_id\");";
			}
		}

		return $field_script;
	}

	public static function get_input_mask_init_script( $form ) {

		$script_str = '';

		foreach ( $form['fields'] as $field ) {

			if ( ! $field->inputMask || ! $field->inputMaskValue ) {
				continue;
			}

			$mask   = $field->inputMaskValue;
			$script = "jQuery('#input_{$form['id']}_{$field->id}').mask('" . esc_js( $mask ) . "').bind('keypress', function(e){if(e.which == 13){jQuery(this).blur();} } );";

			$script_str .= gf_apply_filters( array( 'gform_input_mask_script', $form['id'] ), $script, $form['id'], $field->id, $mask );
		}

		return $script_str;
	}

	public static function get_calculations_init_script( $form ) {
		$formula_fields = array();

		foreach ( $form['fields'] as $field ) {

			if ( ! $field->enableCalculation || ! $field->calculationFormula ) {
				continue;
			}

			$formula_fields[] = array( 'field_id' => $field->id, 'formula' => $field->calculationFormula, 'rounding' => $field->calculationRounding );
		}

		if ( empty( $formula_fields ) ) {
			return '';
		}

		$script = 'if( typeof window.gf_global["gfcalc"] == "undefined" ) { window.gf_global["gfcalc"] = {}; } window.gf_global["gfcalc"][' . $form['id'] . '] = new GFCalc(' . $form['id'] . ', ' . GFCommon::json_encode( $formula_fields ) . ');';

		return $script;
	}

	/**
	 * Generates a map of fields IDs and their corresponding number formats used by the GFCalc JS object for correctly
	 * converting field values to clean numbers.
	 *
	 * - Number fields have a 'numberFormat' setting (w/ UI).
	 * - Single-input product fields (i.e. 'singleproduct', 'calculation', 'price' and 'hiddenproduct') should default to
	 *   the number format of the configured currency.
	 * - All other product fields will default to 'decimal_dot' for the number format.
	 * - All other fields will have no format (false) and inherit the format of the formula field when the formula is
	 *   calculated.
	 *
	 * @param mixed $form
	 * @return string
	 */
	public static function get_number_formats_script( $form ) {
		$number_formats = array();
		$currency       = RGCurrency::get_currency( GFCommon::get_currency() );

		foreach ( $form['fields'] as $field ) {

			// default format is false, fields with no format will inherit the format of the formula field when calculated
			// price format is specified for product fields, value format is specified number fields; used in conditional
			// logic to determine if field or rule value should be formatted
			$price_format = false;
			$value_format = false;

			switch ( GFFormsModel::get_input_type( $field ) ) {
				case 'number':
					$value_format = $field->numberFormat ? $field->numberFormat : 'decimal_dot';
					break;
				case 'singleproduct':
				case 'calculation':
				case 'price':
				case 'hiddenproduct':
				case 'singleshipping':
					$price_format = $currency['decimal_separator'] == ',' ? 'decimal_comma' : 'decimal_dot';
					break;
				default:

					// we check above for all single-input product types, for all other products, assume decimal format
					if ( in_array( $field->type, array( 'product', 'option', 'shipping' ) ) ) {
						$price_format = 'decimal_dot';
					}
			}

			$number_formats[ $field->id ] = array(
				'price' => $price_format,
				'value' => $value_format
			);

		}

		return 'gf_global["number_formats"][' . $form['id'] . '] = ' . json_encode( $number_formats ) . ';';
	}

	private static function has_datepicker_field( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {

				if ( isset( $field->fields ) && is_array( $field->fields ) ) {
					return self::has_datepicker_field( array( 'fields' => $field->fields) );
				}

				if ( RGFormsModel::get_input_type( $field ) == 'date' && $field->dateType == 'datepicker' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines if the supplied form has a Checkbox field.
	 *
	 * @since  2.3
	 * @access public
	 *
	 * @param array $form               The current forms properties.
	 * @param bool  $select_all_enabled Check if the "Select All" choices setting is enabled
	 *
	 * @return bool
	 */
	private static function has_checkbox_field( $form, $select_all_enabled = false ) {

		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( $field->get_input_type() == 'checkbox' && ( ! $select_all_enabled || ( $select_all_enabled && $field->enableSelectAll ) ) ) {
					return true;
				}
			}
		}

		return false;

	}

	/**
	 * Determines if the supplied form has a product field.
	 *
	 * @since 2.1.1.12 Updated to check the $field->type instead of the $field->inputType.
	 * @since Unknown
	 *
	 * @uses GFCommon::is_product_field()
	 *
	 * @param array $form The current forms properties.
	 *
	 * @return bool
	 */
	private static function has_price_field( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( GFCommon::is_product_field( $field->type ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private static function has_fileupload_field( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$input_type = RGFormsModel::get_input_type( $field );
				if ( in_array( $input_type, array( 'fileupload', 'post_image' ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private static function has_currency_format_number_field( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$input_type = RGFormsModel::get_input_type( $field );
				if ( $input_type == 'number' && $field->numberFormat == 'currency' ) {
					return true;
				}
			}
		}

		return false;
	}

	private static function has_currency_copy_values_option( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( $field->enableCopyValuesOption == true ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Gets the language set for the recaptcha field on a form, defaults to english. Only one recaptcha field can be
	 * used per form.
	 *
	 * @since 2.5.6
	 *
	 * @param $form
	 *
	 * @return string
	 */

	private static function get_recaptcha_language( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( ( $field->type == 'captcha' || $field->inputType == 'captcha' ) && ! in_array( $field->captchaType, array( 'simple_captcha', 'math' ) ) ) {
					return empty( $field->captchaLanguage ) ? 'en' : $field->captchaLanguage;
				}
			}
		}

		return 'en';
	}

	private static function has_recaptcha_field( $form ) {
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( ( $field->type == 'captcha' || $field->inputType == 'captcha' ) && ! in_array( $field->captchaType, array( 'simple_captcha', 'math' ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function has_input_mask( $form, $field = false ) {

		if ( $field ) {
			if ( self::has_field_input_mask( $field ) ) {
				return true;
			}
		} else {

			if ( ! is_array( $form['fields'] ) ) {
				return false;
			}

			foreach ( $form['fields'] as $field ) {
				if ( self::has_field_input_mask( $field ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines if the current field has an input mask.
	 *
	 * @param GF_Field $field The field to be checked.
	 *
	 * @return bool
	 */
	public static function has_field_input_mask( $field ) {

		if ( $field->get_input_type() == 'phone' ) {
			$phone_format = $field->get_phone_format();

			if ( ! rgempty( 'mask', $phone_format ) ) {
				return true;
			}
		}

		if ( $field->inputMask && $field->inputMaskValue && ! $field->enablePasswordInput ) {
			return true;
		}

		return false;
	}

	public static function has_calculation_field( $form ) {

		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			/* @var $field GF_Field */
			if ( $field->has_calculation() ) {
				return true;
			}
		}

		return false;
	}

	/***
	 * Determines if this form will have support for JS merge tags
	 *
	 * @since 2.4
	 *
	 * @param array $form The current form object
	 *
	 * @return bool True if the form supports JS merge tags. False otherwise.
	 */
	public static function has_js_merge_tag( $form ){

		/***
		 * Determines if javascript merge tags are supported. Defaults to false (not supported).
		 *
		 * @since 2.4
		 *
		 * @param bool $has_js_merge_tags Value to be filtered. Return true to add support for Javascript merge tags. Return false to disable it.
		 * @param array $form The current Form Object
		 */
		$has_js_merge_tags = gf_apply_filters( array( 'gform_has_js_merge_tag', $form['id'] ), false, $form );
		return $has_js_merge_tags;
	}

	//Getting all fields that have a rule based on the specified field id
	public static function get_conditional_logic_fields( $form, $fieldId ) {
		$fields = array();

		//adding submit button field if enabled
		if ( isset( $form['button']['conditionalLogic'] ) ) {
			$fields[] = 0;
		}

		foreach ( $form['fields'] as $field ) {

			if ( $field->type != 'page' && ! empty( $field->conditionalLogic ) ) {
				foreach ( $field->conditionalLogic['rules'] as $rule ) {
					if ( intval( $rule['fieldId'] ) == $fieldId ) {
						$fields[] = floatval( $field->id );

						//if field is a section, add all fields in the section that have conditional logic (to support nesting)
						if ( $field->type == 'section' ) {
							$section_fields = GFCommon::get_section_fields( $form, $field->id );
							foreach ( $section_fields as $section_field ) {
								if ( ! empty( $section_field->conditionalLogic ) ) {
									$fields[] = floatval( $section_field->id );
								}
							}
						}
						break;
					}
				}
			}
			//adding fields with next button logic
			if ( ! empty( $field->nextButton['conditionalLogic'] ) ) {
				foreach ( $field->nextButton['conditionalLogic']['rules'] as $rule ) {
					if ( intval( $rule['fieldId'] ) == $fieldId && ! in_array( $fieldId, $fields ) ) {
						$fields[] = floatval( $field->id );
						break;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * @param GF_Field $field
	 * @param string   $value
	 * @param bool     $force_frontend_label
	 * @param null     $form
	 * @param null     $field_values
	 *
	 * @return string|string[]|void
	 */
	public static function get_field( $field, $value = '', $force_frontend_label = false, $form = null, $field_values = null ) {
		$is_form_editor  = GFCommon::is_form_editor();
		$is_entry_detail = GFCommon::is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;
		$custom_class    = $is_admin ? esc_attr( $field->cssClass ) : esc_attr( self::convert_legacy_class( $form, $field->cssClass ) );
		$form_id         = (int) rgar( $form, 'id' );

		if ( $field->type == 'page' ) {
			if ( $is_entry_detail ) {
				return; //ignore page breaks in the entry detail page
			} else if ( ! $is_form_editor ) {

				$previous_button_alt = rgempty( 'imageAlt', $field->previousButton ) ? __( 'Previous Page', 'gravityforms' ) : $field->previousButton['imageAlt'];
				$previous_button = $field->pageNumber == 2 ? '' : self::get_form_button( $form_id, "gform_previous_button_{$form_id}_{$field->id}", $field->previousButton, __( 'Previous', 'gravityforms' ), 'gform_previous_button gform-theme-button gform-theme-button--secondary', $previous_button_alt, $field->pageNumber - 2 );
				if ( ! empty( $previous_button ) ) {
					$previous_button = gf_apply_filters( array( 'gform_previous_button', $form_id ), $previous_button, $form );
				}

				$next_button_alt = rgempty( 'imageAlt', $field->nextButton ) ? __( 'Next Page', 'gravityforms' ) : $field->nextButton['imageAlt'];
				$next_button     = self::get_form_button( $form_id, "gform_next_button_{$form_id}_{$field->id}", $field->nextButton, __( 'Next', 'gravityforms' ), 'gform_next_button gform-theme-button', $next_button_alt, $field->pageNumber );
				$next_button     = gf_apply_filters( array( 'gform_next_button', $form_id ), $next_button, $form );

				$save_button = rgars( $form, 'save/enabled' ) ? self::get_form_button( $form_id, "gform_save_{$form_id}_{$field->pageNumber}", $form['save']['button'], rgars( $form, 'save/button/text' ), 'gform_save_link gform-theme-button gform-theme-button--secondary', rgars( $form, 'save/button/text' ), 0, "jQuery(\"#gform_save_{$form_id}\").val(1);" ) : '';

				/**
				 * Filters the save and continue link allowing the tag to be customized
				 *
				 * @since 2.0.7.7
				 *
				 * @param string $save_button The string containing the save and continue link markup.
				 * @param array  $form        The Form object associated with the link.
				 */
				$save_button = apply_filters( 'gform_savecontinue_link', $save_button, $form );
				$save_button = apply_filters( "gform_savecontinue_link_{$form_id}", $save_button, $form );

				$style           = self::is_page_active( $form_id, $field->pageNumber ) ? '' : "style='display:none;'";
				$custom_class    = ! empty( $custom_class ) ? " {$custom_class}" : '';
				$label_placement = rgar( $form, 'labelPlacement', 'top_label' );
				$tag             = GFCommon::is_legacy_markup_enabled( $form ) ? 'ul' : 'div';
				$html            = "</{$tag}>
                    </div>
                    <div class='gform-page-footer gform_page_footer {$label_placement}'>
                        {$previous_button} {$next_button} {$save_button}
                    </div>
                </div>
                <div id='gform_page_{$form['id']}_{$field->pageNumber}' class='gform_page{$custom_class}' data-js='page-field-id-{$field->id}' {$style}>
                    <div class='gform_page_fields'>
                        <{$tag} id='gform_fields_{$form['id']}_{$field->pageNumber}' class='" . GFCommon::get_ul_classes( $form ) . "'>";

				return $html;
			}
		}

		if ( ! $is_admin && $field->visibility == 'administrative' ) {
			if ( $field->allowsPrepopulate ) {
				$field->inputType = 'adminonly_hidden';
			} else {
				return;
			}
		}

		$id = $field->id;

		$input_type = GFFormsModel::get_input_type( $field );

		$error_class        = $field->failed_validation ? 'gfield_error' : '';
		$admin_only_class   = $field->visibility == 'administrative' ? 'field_admin_only' : ''; // maintain for backwards compat
		$admin_hidden_class = ( $is_admin && $field->visibility == 'hidden' ) ? 'admin-hidden' : '';
		$visibility_class   = $is_admin ? 'gfield_visibility_visible' : sprintf( 'gfield_visibility_%s', ( $field->visibility ? $field->visibility : 'visible' ) );
		$selectable_class   = $is_admin ? 'selectable' : '';
		$hidden_class       = in_array( $input_type, array( 'hidden', 'hiddenproduct' ) ) ? 'gform_hidden' : '';

		$choice_fields                  = array( 'checkbox', 'radio', 'consent' );
		$choice_input_type_class        = in_array( $field->type, $choice_fields ) || ( isset( $field->inputType ) && in_array( $field->inputType, $choice_fields ) ) ? 'gfield--type-choice' : '';
		$choice_alignment_class         = $field->type === 'multi_choice' ? 'gfield--choice-align-' . GF_Field_Multiple_Choice::get_field_choice_alignment( $field ) : '';
		$choice_input_image_shape_class = '';
		$choice_input_image_style_class = '';
		if ( $field->type === 'image_choice' ) {
			$choice_input_image_shape_class = isset( $form['styles'] ) && rgar( $form['styles'], 'inputImageChoiceAppearance' ) ? 'gfield--image-choice-appearance-' . $form['styles']['inputImageChoiceAppearance'] : 'gfield--image-choice-appearance-card';
			$choice_input_image_style_class = isset( $form['styles'] ) && rgar( $form['styles'], 'inputImageChoiceStyle' ) ? 'gfield--image-choice-style-' . $form['styles']['inputImageChoiceStyle'] : 'gfield--image-choice-style-square';
		}

		$field_input_type_class  = isset( $field->inputType ) && ! empty( $field->inputType ) ? sprintf( 'gfield--input-type-%s', $field->inputType ) : '';

		$field_specific_class = $field->get_field_css_class();

		$section_class              = $field->type == 'section' ? 'gsection' : '';
		$page_class                 = $field->type == 'page' ? 'gpage gform-theme__disable' : '';
		$html_block_class           = $field->type == 'html' ? 'gfield_html' : '';
		$html_formatted_class       = $field->type == 'html' && ! $field->disableMargins ? 'gfield_html_formatted' : '';
		$html_no_follows_desc_class = $field->type == 'html' && ! $is_admin && ! self::prev_field_has_description( $form, $field->id ) ? 'gfield_no_follows_desc' : '';

		$calculation_class = $input_type == 'calculation' || ( $input_type == 'number' && $field->has_calculation() )  ? 'gfield_calculation' : '';

		$product_suffix            = "_{$form_id}_" . $field->productField;
		$option_class             = $field->type == 'option' ? "gfield_price gfield_price{$product_suffix} gfield_option{$product_suffix}" : '';
		$quantity_class           = $field->type == 'quantity' ? "gfield_price gfield_price{$product_suffix} gfield_quantity gfield_quantity{$product_suffix}" : '';
        $total_class              = $field->type == 'total' ? "gfield_price gfield_price{$product_suffix} gfield_total gfield_total{$product_suffix}" : '';
		$shipping_class           = $field->type == 'shipping' ? "gfield_price gfield_shipping gfield_shipping_{$form_id}" : '';
		$product_class            = $field->type == 'product' ? "gfield_price gfield_price_{$form_id}_{$field->id} gfield_product_{$form_id}_{$field->id}" : '';
		$hidden_product_class     = $input_type == 'hiddenproduct' ? 'gfield_hidden_product' : '';
		$donation_class           = $field->type == 'donation' ? "gfield_price gfield_price_{$form_id}_{$field->id} gfield_donation_{$form_id}_{$field->id}" : '';
		$required_class           = $field->isRequired ? 'gfield_contains_required' : '';
		$creditcard_warning_class = $input_type == 'creditcard' && ! GFCommon::is_ssl() ? 'gfield_creditcard_warning' : '';

		$submit_width_class = $field->type == 'submit' && $field->submitWidth == 'full' ? 'width-full' : '';

		$form_sublabel_setting = rgempty( 'subLabelPlacement', $form ) ? 'below' : $form['subLabelPlacement'];
		$sublabel_setting	   = ! isset( $field->subLabelPlacement ) || empty( $field->subLabelPlacement ) ? $form_sublabel_setting : $field->subLabelPlacement;
		$sublabel_class        = "field_sublabel_{$sublabel_setting}";

		$has_description_class    = ! empty( $field->description ) ? 'gfield--has-description' : 'gfield--no-description';
		$description_setting      = $field->is_description_above( $form ) ? 'above' : 'below';
		$description_class        = "field_description_{$description_setting}";

		$form_validation_setting = rgempty( 'validationPlacement', $form ) ? 'below' : $form['validationPlacement'];
		$validation_setting      = ! isset( $field->validationPlacement ) || empty( $field->validationPlacement ) ? $form_validation_setting : $field->validationPlacement;
		$validation_class        = "field_validation_{$validation_setting}";

		$field_setting_label_placement = $field->labelPlacement;
		$label_placement               = empty( $field_setting_label_placement ) ? '' : $field_setting_label_placement;

		$span_class = $field->get_css_grid_class( $form );

		$css_class = "gfield gfield--type-{$field->type} $choice_input_type_class $choice_input_image_shape_class $choice_input_image_style_class $field_input_type_class $field_specific_class $selectable_class $span_class $error_class $section_class $admin_only_class $custom_class $hidden_class $html_block_class $html_formatted_class $html_no_follows_desc_class $option_class $quantity_class $product_class $total_class $donation_class $shipping_class $page_class $required_class $hidden_product_class $creditcard_warning_class $submit_width_class $calculation_class $sublabel_class $has_description_class $description_class $label_placement $validation_class $visibility_class $admin_hidden_class $choice_alignment_class";
		$css_class = preg_replace( '/\s+/', ' ', $css_class ); // removing extra spaces

		/*
		 * This filter is applied twice because fields may either be using it to modify the collection of HTML classes
		 * by removing elements, or by providing their own custom HTML classes, as well. We want to capture any
		 * custom classes which are provided, but cannot guarantee that the $css_class string has been manipulated
		 * in a reliable way. As of 2.5, the $field_classes value is used by the Settings API to apply those classes
		 * to the settings sidebar panel while a field is active.
		 */
		$field_classes = gf_apply_filters( array( 'gform_field_css_class', $form_id ), '', $field, $form );
		$css_class    = gf_apply_filters( array( 'gform_field_css_class', $form_id ), trim( $css_class ), $field, $form );

		$style = '';

		$field_id = $is_admin || empty( $form ) ? "field_$id" : 'field_' . $form_id . "_$id";

		$field_content = self::get_field_content( $field, $value, $force_frontend_label, $form_id, $form );

		$css_class = esc_attr( $css_class );

		$field_container = $field->get_field_container(
			array(
				'id'              => $field_id,
				'class'           => $css_class,
				'style'           => $style,
				'data-field-class' => trim( $field_classes ),
			),
			$form
		);


		/**
		 * Modify the markup used for the field container.
		 *
		 * @since 1.8.9
		 *
		 * @param string   $field_container The field container markup. {FIELD_CONTENT} placeholder indicates where the markup for the field content should be located.
		 * @param GF_Field $field           The Field currently being processed.
		 * @param array    $form            The Form currently being processed.
		 * @param string   $css_class       The CSS classes to be assigned to the container element.
		 * @param string   $style           Holds the conditional logic display style. Deprecated in 1.9.4.4.
		 * @param string   $field_content   The markup for the field content: label, description, inputs, etc.
		 */
		if ( rgar( $field, 'type' ) !== 'submit' ) {
			$field_container = gf_apply_filters( array( 'gform_field_container', $form_id, $field->id ), $field_container, $field, $form, $css_class, $style, $field_content );
		}

		$field_markup = str_replace( '{FIELD_CONTENT}', $field_content, $field_container );

		return $field_markup;
	}

	private static function prev_field_has_description( $form, $field_id ) {
		if ( ! is_array( $form['fields'] ) ) {
			return false;
		}

		$prev = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->id == $field_id ) {
				return $prev != null && ! empty( $prev->description );
			}
			$prev = $field;
		}

		return false;
	}

	/**
	 * @param GF_Field  	$field
	 * @param string 		$value
	 * @param bool   		$force_frontend_label
	 * @param int   		$form_id
	 * @param null|array   	$form
	 *
	 * @return string
	 */
	public static function get_field_content( $field, $value = '', $force_frontend_label = false, $form_id = 0, $form = null ) {

		$field_label   = $field->get_field_label( $force_frontend_label, $value );
		$admin_buttons = $field->get_admin_buttons();

		$input_type = GFFormsModel::get_input_type( $field );

		$is_form_editor  = GFCommon::is_form_editor();
		$is_entry_detail = GFCommon::is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		if ( $input_type == 'adminonly_hidden' ) {
			$field_content = ! $is_admin ? '{FIELD}' : sprintf( "%s<label class='gfield_label gform-field-label' >%s</label>{FIELD}", $admin_buttons, esc_html( $field_label ) );
		} else {
			$field_content = $field->get_field_content( $value, $force_frontend_label, $form );
		}

		$value = $field->get_value_default_if_empty( $value );

		$field_content = str_replace( '{FIELD}', GFCommon::get_field_input( $field, $value, 0, $form_id, $form ), $field_content );

		$field_content = gf_apply_filters( array( 'gform_field_content', $form_id, $field->id ), $field_content, $field, $value, 0, $form_id );

		$admin_compact_view_menu = $is_form_editor ? sprintf( "<div id='dropdown_field_%s' data-js='gform-compact-view-overflow-menu' class='gform-compact-view-overflow-menu gform-theme__disable'></div>", $field->id ) : '';

		if( $is_form_editor ) {
			$field_content = '<div class="gfield-admin-wrapper">' . $field_content . '</div>' . ( $field->type !== 'submit' ? $admin_compact_view_menu : '' );
		}
		return $field_content;
	}

	public static function get_progress_bar( $form, $page, $confirmation_message = '' ) {

		$form_id           = $form['id'];
		$progress_complete = false;
		$progress_bar      = '';
		$page_count        = self::get_max_page_number( $form );
		$current_page      = $page;
		$page_name         = rgars( $form['pagination'], sprintf( 'pages/%d', $current_page - 1 ) );
		$page_name         = ! empty( $page_name ) ? " - " . $page_name : '';
		$style             = $form['pagination']['style'];
		$color             = $style == 'custom' ? " color:{$form['pagination']['color']};" : '';
		$bgcolor           = $style == 'custom' ? " background-color:{$form['pagination']['backgroundColor']};" : '';

		if ( ! empty( $confirmation_message ) ) {
			$progress_complete = true;
		}
		//check admin setting for whether the progress bar should start at zero
		$start_at_zero = rgars( $form, 'pagination/display_progressbar_on_confirmation' );
		//check for filter
		$start_at_zero          = apply_filters( 'gform_progressbar_start_at_zero', $start_at_zero, $form );
		$progressbar_page_count = $start_at_zero ? $current_page - 1 : $current_page;
		$percent                = ! $progress_complete ? floor( ( ( $progressbar_page_count ) / $page_count ) * 100 ) . '%' : '100%';
		$percent_number         = ! $progress_complete ? floor( ( ( $progressbar_page_count ) / $page_count ) * 100 ) . '' : '100';

		if ( $progress_complete ) {
			$wrapper_css_class = GFCommon::get_browser_class() . ' gform_wrapper';

			//add on surrounding wrapper class when confirmation page
			$progress_bar = "<div class='{$wrapper_css_class}' id='gform_wrapper_$form_id' >";
			$page_name    = ! empty( $form['pagination']['progressbar_completion_text'] ) ? $form['pagination']['progressbar_completion_text'] : '';
		}

		$progress_bar_title_open  = GFCommon::is_legacy_markup_enabled( $form ) ? '<h3 class="gf_progressbar_title">' : '<p class="gf_progressbar_title">';
		$progress_bar_title_close = GFCommon::is_legacy_markup_enabled( $form ) ? '</h3>' : '</p>';

		$progress_bar .= "
        <div id='gf_progressbar_wrapper_{$form_id}' class='gf_progressbar_wrapper' data-start-at-zero='{$start_at_zero}'>
        	{$progress_bar_title_open}";
		$progress_bar .= ! $progress_complete ? esc_html__( 'Step', 'gravityforms' ) . " <span class='gf_step_current_page'>{$current_page}</span> " . esc_html__( 'of', 'gravityforms' ) . " <span class='gf_step_page_count'>{$page_count}</span><span class='gf_step_page_name'>{$page_name}</span>" : "{$page_name}";
		$progress_bar .= "
        	{$progress_bar_title_close}
            <div class='gf_progressbar gf_progressbar_{$style}' aria-hidden='true'>
                <div class='gf_progressbar_percentage percentbar_{$style} percentbar_{$percent_number}' style='width:{$percent};{$color}{$bgcolor}'><span>{$percent}</span></div>
            </div></div>";
		//close div for surrounding wrapper class when confirmation page
		$progress_bar .= $progress_complete ? $confirmation_message . '</div>' : '';

		/**
		 * Filter the mulit-page progress bar markup.
		 *
		 * @since 2.0
		 *
		 * @param string $progress_bar         Progress bar markup as an HTML string.
		 * @param array  $form                 Current form object.
		 * @param string $confirmation_message The confirmation message to be displayed on the confirmation page.
		 *
		 * @see   https://docs.gravityforms.com/gform_progress_bar/
		 */
		$progress_bar = apply_filters( 'gform_progress_bar', $progress_bar, $form, $confirmation_message );
		$progress_bar = apply_filters( "gform_progress_bar_{$form_id}", $progress_bar, $form, $confirmation_message );

		return $progress_bar;
	}

	public static function get_progress_steps( $form, $page ) {

		$progress_steps = "<div id='gf_page_steps_{$form['id']}' class='gf_page_steps'>";
		$pages  = isset( $form['pagination']['pages'] ) ? $form['pagination']['pages'] : array();

		for ( $i = 0, $count = sizeof( $pages ); $i < $count; $i ++ ) {
			$step_number    = $i + 1;
			$active_class   = $step_number == $page ? ' gf_step_active' : '';
			$first_class    = $i == 0 ? ' gf_step_first' : '';
			$last_class     = $i + 1 == $count ? ' gf_step_last' : '';
			$complete_class = $step_number < $page ? ' gf_step_completed' : '';
			$previous_class = $step_number + 1 == $page ? ' gf_step_previous' : '';
			$next_class     = $step_number - 1 == $page ? ' gf_step_next' : '';
			$pending_class  = $step_number > $page ? ' gf_step_pending' : '';
			$classes        = 'gf_step' . $active_class . $first_class . $last_class . $complete_class . $previous_class . $next_class . $pending_class;

			$classes = GFCommon::trim_all( $classes );

			$progress_steps .= "<div id='gf_step_{$form['id']}_{$step_number}' class='{$classes}'><span class='gf_step_number'>{$step_number}</span><span class='gf_step_label'>{$pages[ $i ]}</span></div>";

		}

		$progress_steps .= "</div>";

		/**
		 * Filter the multi-page progress steps markup.
		 *
		 * @since 2.0-beta-3
		 *
		 * @param string $progress_steps HTML string containing the progress steps markup.
		 * @param array $form The current form object.
		 * @param int $page The current page number.
		 *
		 * @see   https://docs.gravityforms.com/gform_progress_steps/
		 */
		$progress_steps = apply_filters( 'gform_progress_steps', $progress_steps, $form, $page );
		$progress_steps = apply_filters( "gform_progress_steps_{$form['id']}", $progress_steps, $form, $page );

		return $progress_steps;
	}

	/**
	 * Validates the form's entry limit settings. Returns the entry limit message if entry limit exceeded.
	 *
	 * @param array $form current GF form object
	 *
	 * @return string|null If entry limit exceeded returns entry limit setting.
	 */
	public static function validate_entry_limit( $form ) {

		if ( ! rgar( $form, 'limitEntries' ) ) {
			return null;
		}

		$form_id         = absint( rgar( $form, 'id' ) );
		$period          = rgar( $form, 'limitEntriesPeriod' );
		$range           = self::get_limit_period_dates( $period );
		$search_criteria = array(
			'status'     => 'active',
			'start_date' => $range['start_date'],
			'end_date'   => $range['end_date'],
		);

		/**
		 * Allows the search criteria for the entry limit validation to be customized.
		 *
		 * @since 2.7.1
		 *
		 * @param array $search_criteria An array containing the search criteria.
		 * @param array $form            The form currently being validated.
		 */
		$search_criteria = gf_apply_filters( array(
			'gform_search_criteria_entry_limit_validation',
			$form_id
		), $search_criteria, $form );

		$entry_count = GFAPI::count_entries( $form_id, $search_criteria );
		$limit       = rgar( $form, 'limitEntriesCount' );

		if ( $entry_count >= $limit ) {
			$error = empty( $form['limitEntriesMessage'] ) ? "<div class='gf_submission_limit_message'><p>" . esc_html__( 'Sorry. This form is no longer accepting new submissions.', 'gravityforms' ) . '</p></div>' : '<p>' . GFCommon::gform_do_shortcode( $form['limitEntriesMessage'] ) . '</p>';
			self::set_submission_if_null( $form_id, 'form_restriction_error', $error );
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Form (#%d) entry limit reached. Limit: %d; Count: %d.', $form_id, $limit, $entry_count ) );

			return $error;
		}

		return null;
	}

	public static function validate_form_schedule( $form ) {

		//If form has a schedule, make sure it is within the configured start and end dates
		if ( rgar( $form, 'scheduleForm' ) ) {
			$local_time_start = sprintf( '%s %02d:%02d %s', $form['scheduleStart'], $form['scheduleStartHour'], $form['scheduleStartMinute'], $form['scheduleStartAmpm'] );
			$local_time_end   = sprintf( '%s %02d:%02d %s', $form['scheduleEnd'], $form['scheduleEndHour'], $form['scheduleEndMinute'], $form['scheduleEndAmpm'] );
			$timestamp_start  = strtotime( $local_time_start . ' +0000' );
			$timestamp_end    = strtotime( $local_time_end . ' +0000' );
			$now              = current_time( 'timestamp' );

			if ( ! empty( $form['scheduleStart'] ) && $now < $timestamp_start ) {
				$error = empty( $form['schedulePendingMessage'] ) ? '<p>' . esc_html__( 'This form is not yet available.', 'gravityforms' ) . '</p>' : '<p>' . GFCommon::gform_do_shortcode( $form['schedulePendingMessage'] ) . '</p>';
				self::set_submission_if_null( $form['id'], 'form_restriction_error', $error );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): The form (#%d) is not yet available. Scheduled for: %d; Now: %d.', rgar( $form, 'id' ), $timestamp_start, $now ) );

				return $error;
			} elseif ( ! empty( $form['scheduleEnd'] ) && $now > $timestamp_end ) {
				$error = empty( $form['scheduleMessage'] ) ? '<p>' . esc_html__( 'Sorry. This form is no longer available.', 'gravityforms' ) . '</p>' : '<p>' . GFCommon::gform_do_shortcode( $form['scheduleMessage'] ) . '</p>';
				self::set_submission_if_null( $form['id'], 'form_restriction_error', $error );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): The form (#%d) is no longer available. Ended: %d; Now: %d.', rgar( $form, 'id' ), $timestamp_end, $now ) );

				return $error;
			}
		}

	}

	/**
	 * Populates the form confirmation property with the confirmation to be used for the current submission.
	 *
	 * @since unknown
	 *
	 * @param array      $form  The form being processed.
	 * @param null|array $entry Null, the entry being processed, or an empty array when the submission fails honeypot validation.
	 * @param string     $event The confirmation event or an empty string.
	 *
	 * @return array
	 */
	public static function update_confirmation( $form, $entry = null, $event = '' ) {
		if ( ( is_array( $entry ) && ( empty( $entry ) || rgar( $entry, 'status' ) === 'spam' ) ) || empty( $form['confirmations'] ) || ! is_array( $form['confirmations'] ) ) {
			$form['confirmation'] = GFFormsModel::get_default_confirmation();

			return $form;
		}

		if ( ! empty( $event ) ) {
			$confirmations = wp_filter_object_list( $form['confirmations'], array( 'event' => $event ) );
		} else {
			$confirmations = $form['confirmations'];
		}

		// if there is only one confirmation, don't bother with the conditional logic, just return it
		// this is here mostly to avoid the semi-costly GFFormsModel::create_lead() function unless we really need it
		if ( count( $confirmations ) <= 1 ) {
			$form['confirmation'] = reset( $confirmations );

			return $form;
		}

		if ( is_null( $entry ) ) {
			$entry = GFFormsModel::create_lead( $form );
		}

		GFCommon::log_debug( __METHOD__ . '(): Evaluating conditional logic.' );

		foreach ( $confirmations as $confirmation ) {

			if ( rgar( $confirmation, 'event' ) != $event ) {
				continue;
			}

			if ( rgar( $confirmation, 'isDefault' ) ) {
				continue;
			}

			if ( isset( $confirmation['isActive'] ) && ! $confirmation['isActive'] ) {
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Confirmation (#%s - %s) is inactive.', rgar( $confirmation, 'id' ), rgar( $confirmation, 'name' ) ) );
				continue;
			}

			$logic = rgar( $confirmation, 'conditionalLogic' );
			if ( GFCommon::evaluate_conditional_logic( $logic, $form, $entry ) ) {
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Confirmation (#%s - %s) conditional logic matches.', rgar( $confirmation, 'id' ), rgar( $confirmation, 'name' ) ) );
				$form['confirmation'] = $confirmation;

				return $form;
			}

			GFCommon::log_debug( __METHOD__ . sprintf( '(): Confirmation (#%s - %s) conditional logic not met.', rgar( $confirmation, 'id' ), rgar( $confirmation, 'name' ) ) );
		}

		GFCommon::log_debug( __METHOD__ . '(): No conditional logic match found; using default.' );
		$filtered_list = wp_filter_object_list( $form['confirmations'], array( 'isDefault' => true ) );

		$form['confirmation'] = reset( $filtered_list );

		return $form;
	}

	public static function process_send_resume_link() {

		$form_id      = rgpost( 'gform_send_resume_link' );
		$form_id      = absint( $form_id );
		$email        = rgpost( 'gform_resume_email' );
		$resume_token = rgpost( 'gform_resume_token' );
		$resume_token = sanitize_key( $resume_token );

		if ( empty( $form_id ) || ! GFFormDisplay::is_submit_form_id_valid( $form_id ) || empty( $email ) || empty( $resume_token ) || ! GFCommon::is_valid_email( $email ) ) {
			return;
		}

		$form = GFFormsModel::get_form_meta( $form_id );

		if ( empty( $form ) ) {
			return;
		}

		if ( GFCommon::form_requires_login( $form ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die();
			}
			check_admin_referer( 'gform_send_resume_link', '_gform_send_resume_link_nonce' );
		}

		$draft_submission = GFFormsModel::get_draft_submission_values( $resume_token );

		$submission = json_decode( $draft_submission['submission'], true );

		$partial_entry = $submission['partial_entry'];

		$notifications_to_send = GFCommon::get_notifications_to_send( 'form_save_email_requested', $form, $partial_entry );

		$log_notification_event = empty( $notifications_to_send ) ? 'No notifications to process' : 'Processing notifications';
		GFCommon::log_debug( "GFFormDisplay::process_send_resume_link(): {$log_notification_event} for form_save_email_requested event." );

		foreach ( $notifications_to_send as $notification ) {
			if ( isset( $notification['isActive'] ) && ! $notification['isActive'] ) {
				GFCommon::log_debug( "GFFormDisplay::process_send_resume_link(): Notification is inactive, not processing notification (#{$notification['id']} - {$notification['name']})." );
				continue;
			}
			if ( $notification['toType'] == 'hidden' ) {
				$notification['to'] = $email;
			}
			$notification['message'] = self::replace_save_variables( $notification['message'], $form, $resume_token, $email );
			GFCommon::send_notification( $notification, $form, $partial_entry );
		}

		GFFormsModel::add_email_to_draft_sumbmission( $resume_token, $email );
	}

	public static function replace_save_variables( $text, $form, $resume_token, $email = null ) {
		$resume_token = sanitize_key( $resume_token );
		$form_id      = intval( $form['id'] );
		$page_url     = rgpost( 'current_page_url' ) ? sanitize_url( rawurldecode( rgpost( 'current_page_url' ) ) ): GFFormsModel::get_current_page_url();
		/**
		 * Filters the 'Save and Continue' URL to be used with a partial entry submission.
		 *
		 * @since 1.9
		 *
		 * @param string $resume_url   The URL to be used to resume the partial entry.
		 * @param array  $form         The Form Object.
		 * @param string $resume_token The token that is used within the URL.
		 * @param string $email        The email address associated with the partial entry.
		 */
		$resume_url  = apply_filters( 'gform_save_and_continue_resume_url', add_query_arg( array( 'gf_token' => $resume_token ), $page_url ), $form, $resume_token, $email );
		$resume_url  = esc_url( $resume_url );
		$resume_link = "<a href=\"{$resume_url}\" class='resume_form_link'>{$resume_url}</a>";
		$text        = str_replace( '{save_link}', $resume_link, $text );
		$text        = str_replace( '{save_token}', $resume_token, $text );

		$text = str_replace( '{save_url}', $resume_url, $text );

		$email_esc = esc_attr( $email );
		$text      = str_replace( '{save_email}', $email_esc, $text );

		$resume_submit_button_text       = esc_html__( 'Send Link', 'gravityforms' );
		$resume_email_validation_message = esc_html__( 'Please enter a valid email address.', 'gravityforms' );
		$email_input_label               = esc_html__( 'Email Address', 'gravityforms' );
		$email_input_label_required      = GFFormsModel::get_required_indicator( $form_id );

		// The {save_email_input} accepts shortcode-style options button_text and validation_message. E.g.,
		// {save_email_input: button_text="Send the link to my email address" validation_message="The link couldn't be sent because the email address is not valid."}
		preg_match_all( '/\{save_email_input:(.*?)\}/', $text, $matches, PREG_SET_ORDER );

		if ( is_array( $matches ) && isset( $matches[0] ) && isset( $matches[0][1] ) ) {
			$options_string = isset( $matches[0][1] ) ? $matches[0][1] : '';
			$options        = shortcode_parse_atts( $options_string );
			if ( isset( $options['button_text'] ) ) {
				$resume_submit_button_text = $options['button_text'];
			}
			if ( isset( $options['validation_message'] ) ) {
				$resume_email_validation_message = $options['validation_message'];
			}
			if ( ! empty( $options['placeholder'] ) ) {
				$email_input_placeholder = esc_attr( $options['placeholder'] );
			}
			$full_tag = $matches[0][0];
			$text     = str_replace( $full_tag, '{save_email_input}', $text );
		}

		$action = esc_url( remove_query_arg( 'gf_token' ) );

		$submission_method = self::get_submission_method();
		$is_iframe_ajax    = self::is_iframe_submission_method();
		$anchor            = self::get_anchor( $form, $is_iframe_ajax );
		$action           .= $anchor['id'];

		$resume_token = esc_attr( $resume_token );

		$form_is_invalid = ! is_null( $email ) && ! GFCommon::is_valid_email( $email );

		$validation_output = $form_is_invalid ? sprintf( '<div class="gfield_description gfield_validation_message" id="email-validation-error" aria-live="assertive">%s</div>', $resume_email_validation_message ) : '';

		$nonce_input = '';

		if ( GFCommon::form_requires_login( $form ) ) {
			$nonce_input = wp_nonce_field( 'gform_send_resume_link', '_gform_send_resume_link_nonce', true, false );
		}

		$target = $is_iframe_ajax ? "target='gform_ajax_frame_{$form_id}'" : '';

		$iframe_ajax_fields = '';
		if ( $is_iframe_ajax ) {
			$ajax_value         = self::prepare_ajax_input_value( $form_id, true, true, 1 );
			$iframe_ajax_fields = "<input type='hidden' name='gform_ajax' value='" . esc_attr( $ajax_value ) . "' />";
			$iframe_ajax_fields .= "<input type='hidden' name='gform_field_values' value='' />";
		}

		$form_submission_inputs = "<input type='hidden' class='gform_hidden' name='gform_submission_method' data-js='gform_submission_method_{$form_id}' value='{$submission_method}' />
								   <input type='hidden' class='gform_hidden' name='is_submit_{$form_id}' value='1' />
								   <input type='hidden' class='gform_hidden' name='gform_submit' value='{$form_id}' />";

		$ajax_submit = $is_iframe_ajax ? "onclick='jQuery(\"#gform_{$form_id}\").trigger(\"submit\",[true]);'" : '';

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			$resume_form = "<div class='form_saved_message_emailform'>
							<form action='{$action}' method='POST' id='gform_{$form_id}' data-formid='{$form_id}' {$target}>
								{$iframe_ajax_fields}
								<label for='gform_resume_email' class='gform_resume_email_label gfield_label' aria-describedby='email-validation-error'>{$email_input_label}</label>
								<input type='email' name='gform_resume_email' value='{$email_esc}' id='gform_resume_email' placeholder='{$email_input_label}' aria-describedby='email-validation-error'/>
								<input type='hidden' name='gform_resume_token' value='{$resume_token}' />
								<input type='hidden' name='gform_send_resume_link' value='{$form_id}' />
								{$form_submission_inputs}
	                            <input type='submit' name='gform_send_resume_link_button' id='gform_send_resume_link_button_{$form_id}' onclick='gform.submission.handleButtonClick(this);' value='{$resume_submit_button_text}' {$ajax_submit}/>
	                            {$validation_output}
	                            {$nonce_input}
							</form>
	                    </div>";
		} else {
			$resume_form = "<div class='form_saved_message_emailform'>
						<form action='{$action}' method='POST' id='gform_{$form_id}' data-formid='{$form_id}' {$target}>
							<div class='gform-body gform_body'>
								<div id='gform_fields_{$form_id}' class='gform_fields top_label form_sublabel_below description_below'>
									{$iframe_ajax_fields}
									<div class='gfield gfield--type-email gfield--width-full field_sublabel_below field_description_below gfield_visibility_visible'>
										<label for='gform_resume_email' class='gform_resume_email_label gfield_label gform-field-label'>{$email_input_label}{$email_input_label_required}</label>
										<div class='ginput_container ginput_container_text'>
											<input type='email' name='gform_resume_email' class='large' id='gform_resume_email' value='{$email_esc}' aria-describedby='email-validation-error' />
											{$validation_output}
										</div>
									</div>
								</div>
							</div>
							<div class='gform-footer gform_footer top_label'>
								<input type='hidden' name='gform_resume_token' value='{$resume_token}' />
								<input type='hidden' name='gform_send_resume_link' value='{$form_id}' />
								{$form_submission_inputs}
								<input type='submit' name='gform_send_resume_link_button' id='gform_send_resume_link_button_{$form_id}' onclick='gform.submission.handleButtonClick(this);' value='{$resume_submit_button_text}' {$ajax_submit}/>
                                {$nonce_input}
                            </div>
						</form>
	                    		</div>";
		}

		/**
		 * Allows users to disable the spinner on non-ajax forms.
		 *
		 * @since 2.7
		 *
		 * @param bool $show Whether to show the spinner on non-ajax-forms.
		 *
		 * @return bool
		 */
		$always_show_spinner = gf_apply_filters( array( 'gform_always_show_spinner', $form_id ), true );
		if ( ! $is_iframe_ajax && $always_show_spinner ) {
			$default_spinner = GFCommon::get_base_url() . '/images/spinner.svg';
			$spinner_url     = gf_apply_filters( array( 'gform_ajax_spinner_url', $form_id ), $default_spinner, $form );
			$theme_slug      = self::get_form_theme_slug( $form );
			$is_legacy       = $default_spinner !== $spinner_url || in_array( $theme_slug, array( 'gravity-theme', 'legacy' ) );

			$resume_form .= '<script>gform.initializeOnLoaded( function() {' .
			         "gformInitSpinner( {$form_id}, '{$spinner_url}', " . ( $is_legacy ? 'true' : 'false' ) . " );" .
			         " });</script>";
		}

		$text = str_replace( '{save_email_input}', $resume_form, $text );

		return $text;
	}

	public static function handle_save_email_confirmation( $form, $ajax ) {
		$resume_email = $_POST['gform_resume_email'];
		if ( ! GFCommon::is_valid_email( $resume_email ) ) {
			GFCommon::log_debug( 'GFFormDisplay::handle_save_email_confirmation(): Invalid email address: ' . $resume_email );

			return new WP_Error( 'invalid_email' );
		}
		$resume_token       = $_POST['gform_resume_token'];
		$submission_details = GFFormsModel::get_draft_submission_values( $resume_token );
		$submission_json    = $submission_details['submission'];
		$submission         = json_decode( $submission_json, true );
		$entry              = $submission['partial_entry'];
		$form               = self::update_confirmation( $form, $entry, 'form_save_email_sent' );
		$css_class          = esc_attr( rgar( $form, 'cssClass' ) );
		$form_theme         = "data-form-theme='" . GFFormDisplay::get_form_theme_slug( $form ) . "'";

		$confirmation_message = rgar( $form['confirmation'], 'message' );

		$confirmation            = "<div id='gform_confirmation_wrapper_{$form['id']}' class='form_saved_message_sent gform_confirmation_wrapper {$css_class} gform_wrapper' role='alert' {$form_theme}>{$confirmation_message}</div>";
		$nl2br                   = rgar( $form['confirmation'], 'disableAutoformat' ) ? false : true;
		$save_email_confirmation = self::replace_save_variables( $confirmation, $form, $resume_token, $resume_email );
		$save_email_confirmation = GFCommon::replace_variables( $save_email_confirmation, $form, $entry, false, true, $nl2br );
		$save_email_confirmation = GFCommon::gform_do_shortcode( $save_email_confirmation );
		$save_email_confirmation = self::maybe_sanitize_confirmation_message( $save_email_confirmation );

		$anchor                  = self::get_anchor( $form, $ajax );
		$save_email_confirmation = $anchor['tag'] . $save_email_confirmation;

		if ( $ajax ) {
			$save_email_confirmation = self::get_ajax_postback_html( $save_email_confirmation );
		}

		GFCommon::log_debug( 'GFFormDisplay::handle_save_email_confirmation(): Confirmation => ' . print_r( $save_email_confirmation, true ) );

		/**
		 * Filters the form confirmation text.
		 *
		 * This filter allows the form confirmation text to be programmatically changed before it is rendered to the page.
		 *
		 * @since 2.7
		 *
		 * @param string  $save_email_confirmation Confirmation text to be filtered.
		 * @param array $form The current form object
		 */
		return gf_apply_filters( array( 'gform_get_form_save_email_confirmation_filter', $form['id'] ), $save_email_confirmation, $form );
	}

	public static function handle_save_confirmation( $form, $resume_token, $confirmation_message, $ajax ) {
		$resume_email = isset( $_POST['gform_resume_email'] ) ? $_POST['gform_resume_email'] : null;

		$confirmation_message = self::maybe_sanitize_confirmation_message( $confirmation_message );
		$confirmation_message = self::replace_save_variables( $confirmation_message, $form, $resume_token, $resume_email );
		$confirmation_message = GFCommon::gform_do_shortcode( $confirmation_message );
		$confirmation_message = "<div class='form_saved_message'>" . $confirmation_message . '</div>';

		$anchor               = self::get_anchor( $form, $ajax );
		$confirmation_message = $anchor['tag'] . $confirmation_message;

		$form_id           = absint( $form['id'] );
		$wrapper_css_class = GFCommon::get_browser_class() . ' gform_wrapper';
		$page_instance     = isset( $form['page_instance'] ) ? "data-form-index='{$form['page_instance']}'" : null;
		$form_theme        = "data-form-theme='" . GFFormDisplay::get_form_theme_slug( $form ) . "'";

		$wrapper_open = "<div class='{$wrapper_css_class}' {$page_instance} {$form_theme} id='gform_wrapper_{$form_id}'>";

		/**
		 * Allows markup to be added directly after the opening form wrapper.
		 *
		 * @since 2.7
		 *
		 * @param string $markup The current string to append.
		 * @param array  $form   The form being displayed.
		 *
		 * @return string
		 */
		$wrapper_open .= gf_apply_filters( array( 'gform_form_after_open', $form_id ), '', $form );

		$confirmation_message = $wrapper_open . $confirmation_message . '</div>';

		if ( $ajax ) {
			$confirmation_message = self::get_ajax_postback_html( $confirmation_message );
		}

		GFCommon::log_debug( 'GFFormDisplay::handle_save_confirmation(): Confirmation => ' . print_r( $confirmation_message, true ) );

		/**
		 * Filters the form save confirmation text.
		 *
		 * This filter allows the form save confirmation text to be programmatically changed before it is rendered to the page.
		 *
		 * @since 2.7
		 *
		 * @param string  $confirmation_message Confirmation text to be filtered.
		 * @param array   $form The current form object
		 */
		return gf_apply_filters( array( 'gform_get_form_save_confirmation_filter', $form_id ), $confirmation_message, $form );
	}

	/**
	 * Insert review page into form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $form        The current Form object
	 * @param array $review_page The review page
	 *
	 * @return array $form
	 */
	public static function insert_review_page( $form, $review_page ) {

		/* Get field ID and page number for new fields. */
		$new_field_id = self::get_max_field_id( $form ) + 1;
		$page_number  = self::get_max_page_number( $form );
		$page_number  = $page_number == 0 ? 2 : $page_number+1;

		/* Create new Page field for review page. */
		$review_page_break             = new GF_Field_Page();
		$review_page_break->id         = $new_field_id;
		$review_page_break->pageNumber = $page_number;
		$review_page_break->nextButton = rgar( $review_page, 'nextButton' );
		$review_page_break->cssClass   = 'gform_review_page ' . rgar( $review_page, 'cssClass', '' );

		/* Add review page break field to form. */
		$form['fields'][] = $review_page_break;

		/* Create new HTML field for review page. */
		$review_page_field             = new GF_Field_HTML();
		$review_page_field->id         = ++$new_field_id;
		$review_page_field->pageNumber = $page_number;
		$review_page_field->content    = rgar( $review_page, 'content' );

		/* Add review page field to form. */
		$form['fields'][] = $review_page_field;

		/* Configure the last page previous button */
		$form['lastPageButton'] = rgar( $review_page, 'previousButton' );

		return $form;

	}

	/**
	 * Get the anchor config for the current form.
	 *
	 * @since 2.2.2.1
	 *
	 * @param array $form The current Form object.
	 * @param bool $ajax Indicates if AJAX is enabled for the current form.
	 *
	 * @return array
	 */
	public static function get_anchor( $form, $ajax ) {
		$form_id = absint( $form['id'] );
		$anchor  = $ajax || self::has_pages( $form ) ? true : false;

		/**
		 * Allow the anchor to be enabled/disabled or set to a scroll distance.
		 *
		 * @since 1.9.17.12 Added the $form parameter.
		 * @since Unknown
		 *
		 * @param bool|int $anchor Is the form anchor enabled? True when ajax enabled or when the form has multiple pages.
		 * @param array    $form   The current Form object.
		 */
		$anchor = gf_apply_filters( array( 'gform_confirmation_anchor', $form_id ), $anchor, $form );

		return array(
			'scroll' => $anchor,
			'tag'    => $anchor !== false ? "<div id='gf_{$form_id}' class='gform_anchor' tabindex='-1'></div>" : '',
			'id'     => $anchor !== false ? "#gf_{$form_id}" : ''
		);
	}

	/**
	 * Validates the posted input values from the form footer are for the same form, that it exists, is active, and not trashed.
	 *
	 * @since 2.4.18
	 *
	 * @param null|int $ajax_form_id Null or the form ID parsed from the gform_ajax input value.
	 *
	 * @return bool|int False or the ID of the form being processed.
	 */
	public static function is_submit_form_id_valid( $ajax_form_id = null ) {
		if ( empty( $_POST['gform_submit'] ) ) {
			return false;
		}

		$form_id = absint( $_POST['gform_submit'] );

		if ( $form_id === 0 || rgpost( 'is_submit_' . $form_id ) !== '1' ) {
			return false;
		}

		if ( is_null( $ajax_form_id ) ) {
			$ajax_args    = self::parse_ajax_input();
			$ajax_form_id = is_array( $ajax_args ) ? rgar( $ajax_args, 'form_id', 0 ) : null;
		}

		if ( $ajax_form_id !== null && absint( $ajax_form_id ) !== $form_id ) {
			return false;
		}

		$form_info = GFFormsModel::get_form( $form_id );

		if ( ! $form_info || ! $form_info->is_active || $form_info->is_trash ) {
			return false;
		}

		return $form_id;
	}

	/**
	 * Returns the safe value of/for the gform_submission_method input.
	 *
	 * @since 2.9.2
	 *
	 * @param string $method The method or an empty string to get it from the submission.
	 *
	 * @return string
	 */
	public static function get_submission_method( $method = '' ) {
		if ( empty( $method ) ) {
			$method = rgpost( 'gform_submission_method' );
		}

		return GFCommon::whitelist( $method, array(
			self::SUBMISSION_METHOD_POSTBACK,
			self::SUBMISSION_METHOD_IFRAME,
			self::SUBMISSION_METHOD_AJAX,
			self::SUBMISSION_METHOD_CUSTOM,
		) );
	}

	/**
	 * Determines if the iframe-based Ajax submission method is in use.
	 *
	 * @since 2.9.2
	 *
	 * @return bool
	 */
	public static function is_iframe_submission_method() {
		return self::get_submission_method() === self::SUBMISSION_METHOD_IFRAME;
	}

	/**
	 * Parses and sanitizes the value of the gform_ajax input.
	 *
	 * @since 2.9.2
	 *
	 * @param bool $bypass_cache Indicates if the cached arguments should be ignored. Default is false.
	 *
	 * @return array|false
	 */
	public static function parse_ajax_input( $bypass_cache = false ) {
		static $args = null;

		if ( ! $bypass_cache && ! is_null( $args ) ) {
			return $args;
		}

		$args = false;
		if ( ! self::is_iframe_submission_method() || ! isset( $_POST['gform_ajax'] ) ) {
			return false;
		}

		$args  = array();
		$value = rgpost( 'gform_ajax' );
		if ( empty( $value ) || ! is_string( $value ) ) {
			return array();
		}

		parse_str( $value, $args );

		if ( empty( $args['form_id'] ) ) {
			GFCommon::log_debug( __METHOD__ . '(): form_id arg is missing or empty.' );
			$args = array();

			return $args;
		}

		if ( empty( $args['hash'] ) ) {
			GFCommon::log_debug( __METHOD__ . '(): hash arg is missing or empty.' );
			$args = array();

			return $args;
		}

		$args['form_id']     = absint( $args['form_id'] );
		$args['title']       = ! isset( $args['title'] ) || ! empty( $args['title'] );
		$args['description'] = ! isset( $args['description'] ) || ! empty( $args['description'] );
		$args['tabindex']    = isset( $args['tabindex'] ) ? intval( $args['tabindex'] ) : 0;
		$args['theme']       = isset( $args['theme'] ) ? sanitize_text_field( $args['theme'] ) : null;
		$args['styles']      = isset( $args['styles'] ) ? GFCommon::strip_all_tags_from_json_string( $args['styles'] ) : null;

		$expected_hash = wp_hash( self::prepare_ajax_input_value( $args['form_id'], $args['title'], $args['description'], $args['tabindex'], $args['theme'], $args['styles'], false ) );
		if ( $args['hash'] !== $expected_hash ) {
			GFCommon::log_debug( __METHOD__ . '(): args failed hash validation.' );
			$args = array();

			return $args;
		}

		return $args;
	}

	/**
	 * Prepares the value for the gform_ajax input.
	 *
	 * @since 2.9.2
	 *
	 * @param int         $form_id             The form ID.
	 * @param bool        $display_title       Indicates if display of the form title is enabled.
	 * @param bool        $display_description Indicates if display of the form description is enabled.
	 * @param int         $tabindex            The starting tabindex.
	 * @param null|string $theme               Null or the name of the form theme.
	 * @param null|string $styles              Null or the JSON encoded form styles.
	 * @param bool        $include_hash        Indicates if the hash should be included.
	 *
	 * @return string
	 */
	public static function prepare_ajax_input_value( $form_id, $display_title, $display_description, $tabindex, $theme = null, $styles = null, $include_hash = true ) {
		$value = "form_id={$form_id}&amp;title={$display_title}&amp;description={$display_description}&amp;tabindex={$tabindex}";

		if ( ! empty( $theme ) ) {
			$value .= '&amp;theme=' . $theme;
		}

		if ( ! empty( $styles ) ) {
			$value .= '&amp;styles=' . $styles;
		}

		if ( $include_hash ) {
			$value .= '&amp;hash=' . wp_hash( $value );
		}

		return $value;
	}

	/**
	 * Returns the HTML for the ajax postback.
	 *
	 * @since 2.4.18
	 *
	 * @param string $body_content The content to be included in the body of the ajax postback.
	 *
	 * @return string
	 */
	public static function get_ajax_postback_html( $body_content ) {
		$ajax_iframe_content = "<!DOCTYPE html><html><head><meta charset='UTF-8' /></head><body class='GF_AJAX_POSTBACK'>" . $body_content . '</body></html>';

		/**
		 * Allows the content of the iframe for the ajax postback to be overridden.
		 *
		 * @since unknown
		 *
		 * @param string $ajax_iframe_content The HTML to be returned for the ajax postback.
		 */
		return apply_filters( 'gform_ajax_iframe_content', $ajax_iframe_content );
	}

	/**
	 * Returns the HTML to be be output when the requested form is not found.
	 *
	 * @since 2.5.7 Added the $ajax argument.
	 * @since 2.4.18
	 *
	 * @param int|string $form_id The ID or Title of the form requested for display.
	 * @param bool       $ajax    Whether to return the html as part of the ajax postback html or on its own.
	 *
	 * @return string
	 */
	public static function get_form_not_found_html( $form_id, $ajax = false ) {
		$form_not_found_message = '<p class="gform_not_found">' . esc_html__( 'Oops! We could not locate your form.', 'gravityforms' ) . '</p>';

		/**
		 * Allows the HTML that is displayed when the requested form is not found to be overridden.
		 *
		 * @since 2.2.6
		 *
		 * @param string     $form_not_found_message The default form not found message.
		 * @param int|string $form_id                The ID or Title of the form requested for display.
		 */
		$form_not_found_message = apply_filters( 'gform_form_not_found_message', $form_not_found_message, $form_id );

		return $ajax ? self::get_ajax_postback_html( $form_not_found_message ) : $form_not_found_message;
	}

	/**
	 * Generates the markup for the validation errors list that goes on top of the form.
	 *
	 * @since 2.5
	 *
	 * @param array $form          Current form being displayed.
	 * @param array $values        Submitted values.
	 * @param bool  $show_summary  Whether to show a summary of validation errors or just show the validation message.
	 *
	 * @return string              Validation errors markup.
	 */
	public static function get_validation_errors_markup( $form, $values, $show_summary = false ) {

		$error_messages_list = '';
		$hide_summary_class  = $show_summary ? '' : ' hide_summary';
		if ( gf_upgrade()->get_submissions_block() ) {
			$validation_message_markup = "<h2 class='gf_submission_limit_message'>" . esc_html__( 'Your form was not submitted. Please try again in a few minutes.', 'gravityforms' ) . '</h2>';
		} else {
			$validation_message_markup = "<h2 class='gform_submission_error{$hide_summary_class}'><span class='gform-icon gform-icon--circle-error'></span>" . esc_html__( 'There was a problem with your submission.', 'gravityforms' ) . ' ' . esc_html__( 'Please review the fields below.', 'gravityforms' ) . '</h2>';
			// Generate validation errors summary if required.
			if ( $show_summary ) {
				$errors = self::get_validation_errors( $form, $values );
				$error_messages_list = '<ol>';
				foreach ( $errors as $error ) {
					$separator = $error['field_label'] ? ': ' : '';
					$error_messages_list .= '<li><a class="gform_validation_error_link" href="' . $error['field_selector'] . '">' . $error['field_label'] . $separator . $error['message'] . '</a></li>';
				}
				$error_messages_list .= '</ol>';
			}
		}

		$validation_container_id = 'gform_' . $form['id'] . '_validation_container';
		$validation_message_markup = gf_apply_filters( array( 'gform_validation_message', $form['id'] ), $validation_message_markup, $form );

		// If validation message markup already has a list of errors after being filtered, remove our list.
		if ( $show_summary && preg_match( '/<\s*ul[^>]*>(.*?)<\s*\/\s*ul>/', $validation_message_markup ) || preg_match( '/<\s*ol[^>]*>(.*?)<\s*\/\s*ol>/', $validation_message_markup ) ) {
			$error_messages_list = '';
		}

		$wrapper_class = GFCommon::is_legacy_markup_enabled( $form ) ? 'gform_validation_errors validation_error' : 'gform_validation_errors';

		$validation_errors_markup = sprintf(
			'<div class="%s" id="%s" data-js="gform-focus-validation-error" autofocus>%s%s</div>',
			$wrapper_class,
			$validation_container_id,
			$validation_message_markup,
			$error_messages_list
		);

		/**
		 * Filter validation errors markup.
		 *
		 * @since 2.5
		 *
		 * @param string $validation_errors_markup Validation errors markup.
		 * @param array  $form                     The current form object.
		 */
		return gf_apply_filters( array( 'gform_form_validation_errors_markup', $form['id'] ), $validation_errors_markup, $form );

	}

	/**
	 * Gets a list of validation errors.
	 *
	 * @since 2.5
	 *
	 * @param array $form   Current form being displayed.
	 * @param array $values Submitted values.
	 *
	 * @return array        List of validation errors for each field, each item contains the error message and its corresponding field label and selector.
	 */
	public static function get_validation_errors( $form, $values ) {
		$errors = array();
		foreach ( $form['fields'] as $field ) {

			/* @var GF_Field $field */
			if ( ( $field->failed_validation && ! empty( $field->validation_message ) ) ) {
				$errors[] = array(
					'field_label'       => $field->get_field_label( true, $values ),
					'field_selector'    => '#field_' . $form['id'] . '_' . $field->id,
					'message'           => $field->validation_message,
				);
			}
		}

		/**
		* Filter validation errors array.
		*
		* @since 2.5
		*
		* @param array $errors List of validation errors.
		* @param array $form   The current form object.
		*/
		return gf_apply_filters( array( 'gform_form_validation_errors', $form['id'] ), $errors, $form );

	}

	/**
	 * Convert legacy ready class to the new equivalent.
	 *
	 * @since 2.5
	 *
	 * @param array  $form    The current form object.
	 * @param string $classes The class or classes to convert.
	 *
	 * @return string|void
	 */
	public static function convert_legacy_class( $form, $classes ) {
		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return $classes;
		}

		$upgraded_classes = array(
			'gf_left_half'      => 'gfield--width-half',
			'gf_right_half'     => 'gfield--width-half',
			'gf_left_third'     => 'gfield--width-third',
			'gf_middle_third'   => 'gfield--width-third',
			'gf_right_third'    => 'gfield--width-third',
			'gf_first_quarter'  => 'gfield--width-quarter',
			'gf_second_quarter' => 'gfield--width-quarter',
			'gf_third_quarter'  => 'gfield--width-quarter',
			'gf_fourth_quarter' => 'gfield--width-quarter',
		);

		$class_list = explode( ' ', $classes );

		foreach ( $class_list as $class ) {
			if ( array_key_exists( $class, $upgraded_classes ) ) {
				$class_list[] = $upgraded_classes[ $class ];
			}
		}

		$classes = implode( ' ', array_unique( $class_list ) );

		return $classes;
	}


	/**
	 * Parse and validates styles from the gform_default_styles filter.
	 *
	 * @since 2.7.15
	 *
	 * @param mixed $styles Array or JSON string of styles.
	 *
	 * @return array|bool|null $styles
	 */
	public static function validate_form_styles( $styles ) {
		if ( $styles === false || is_null( $styles ) ) {
			return $styles;
		}

		if ( ! is_array( $styles ) ) {
			$styles = json_decode( $styles, true );
		}

		if ( ! is_array( $styles ) ) {
			return array();
		}

		$whitelist = array(
			'theme',
			'inputSize',
			'inputBorderRadius',
			'inputBorderColor',
			'inputBackgroundColor',
			'inputColor',
			'inputPrimaryColor',
			'inputImageChoiceAppearance',
			'inputImageChoiceStyle',
			'inputImageChoiceSize',
			'labelFontSize',
			'labelColor',
			'descriptionFontSize',
			'descriptionColor',
			'buttonPrimaryBackgroundColor',
			'buttonPrimaryColor',
		);

		foreach( $styles as $key => $value ) {
			if ( ! in_array( $key, $whitelist ) ) {
				unset( $styles[ $key ] );
			}
		}

		return $styles;
	}

	/**
	 * Get the form styles from the form parameters and the global style filter.
	 *
	 * @since 2.7.15
	 *
	 * @param array|string $style_settings Array or JSON string of styles.
	 *
	 * @return array|false|string
	 */
	public static function get_form_styles( $style_settings ) {
		$global_styles = apply_filters( 'gform_default_styles', false );

		$form_styles = '';

		if ( $style_settings === false ) {
			// if $style_settings is false, ignore the gform_default_styles filter.
			return false;
		} else if ( ! empty( $style_settings ) ) {
			// if we have style settings, merge them with the gform_default_styles filter.
			if ( ! is_array( $style_settings ) ) {
				$style_settings = json_decode( $style_settings, true );
			}
			if ( $global_styles !== null ) {
				$style_settings = array_merge( is_array( $global_styles ) ? $global_styles : array(), is_array( $style_settings ) ? $style_settings : array() );
			}
			$form_styles = $style_settings;
		} else if ( ! empty( $global_styles ) ) {
			// if we don't have style settings, just use the filter.
			$form_styles = $global_styles;
		}

		return self::validate_form_styles( $form_styles );
	}

	/**
	 * Applies the form styles and form theme to the form object so that the proper style block is rendered to the page.
	 *
	 * @since 2.9.0
	 *
	 * @param mixed       $form           The form object.
	 * @param string|null $style_settings Style settings for the form. This will either come from the shortcode or from the block editor settings.
	 * @param string|null $form_theme     The theme selected for the form. This will either come from the shortcode or from the block editor settings.

	 * @return array Returns the form object with the 'styles' and 'theme' properties set.
	 */
	private static function set_form_styles( $form, $style_settings, $form_theme ) {
		if ( $style_settings === false ) {
			// Form styles specifically set to false disables inline form css styles.
			$form_styles = false;
		} else {
			$form_styles = !empty( $style_settings ) ? json_decode( $style_settings, true ) : array();
		}

		// Removing theme from styles for consistency. $form['theme'] should be used instead.
		if ( $form_styles ) {
			unset( $form_styles['theme'] );
		}
		$form['styles'] = self::get_form_styles( $form_styles );
		$form['theme'] = ! empty( $form_theme ) ? $form_theme : GFForms::get_default_theme();
		return $form;
	}

	/**
	 * Get the spacer to add to the end of the row, if needed
	 *
	 * @since 2.8.2
	 *
	 * @param array $form The current form object.
	 * @param array $field The current field object.
	 *
	 * @return string
	 */
	public static function get_row_spacer( $field, $form ) {
		$spacer = '';

		if ( $field->layoutSpacerGridColumnSpan && ! GFCommon::is_legacy_markup_enabled( $form ) ) {
			// check if this row needs a spacer
			$span = intval( $field->layoutGridColumnSpan );
			foreach ( $form['fields'] as $field2 ) {
				if ( $field2->layoutGroupId == $field->layoutGroupId ) {
					$span += intval( $field2->layoutGridColumnSpan );
				}
			}

			if ( $span < 12 ) {
				$spacer = sprintf( '<div data-fieldId="%s" class="spacer gfield" style="grid-column: span %d;" data-groupId="%s"></div>', $field->id, $field->layoutSpacerGridColumnSpan, $field->layoutGroupId );
			}
		}

		return $spacer;
	}

	/**
	 * @var array Cached forms that have been filtered by the gform_pre_render filter.
	 */
	private static $cached_forms = array();

	/**
	 * Filters the $form object through the gform_pre_render filter and caches the result so that this filter is only triggered once per request.
	 *
	 * @since 2.9.0
	 *
	 * @param array      $form          The form object being filtered.
	 * @param string     $context       The context that the method is being called in. Possible values are 'form_display' and 'form_config'.
	 * @param bool|null  $ajax          Whether the form is being displayed via AJAX. Only used when $context is 'form_display'.
	 * @param array|null $field_values  The field values to be used to populate the form. Only used when $context is 'form_display'.
	 *
	 * @return array Returns the form object after being filtered by the gform_pre_render filter.
	 */
	public static function gform_pre_render( $form, $context, $ajax = null, $field_values = null ) {
		$cache_key = $form['id'] . '_' . $context;

		if ( ! isset( self::$cached_forms[ $cache_key ] ) ) {

			/**
			 * Fired right before the form rendering process. Allow users to manipulate the form object before it gets displayed in the front end.
			 *
			 * @since 2.9.0 Added the $context parameter.
			 *
			 * @param array      $form          The form object being filtered.
			 * @param bool|null  $ajax          Whether the form is being displayed via AJAX. Only used when $context is 'form_display'.
			 * @param array|null $field_values  The field values to be used to populate the form. Only used when $context is 'form_display'.
			 * @param string     $context       The context that the method is being called in. Possible values are 'form_display' and 'form_config'.
			 */
			self::$cached_forms[ $cache_key ] = gf_apply_filters( array( 'gform_pre_render', $form['id'] ), $form, $ajax, $field_values, $context );
		}

		return self::$cached_forms[ $cache_key ];
	}

	/**
	 * Flushes the forms cached by the gform_pre_render method.
	 *
	 * @since 2.9.0
	 *
	 * @param string $cache_key The cache key to flush. The format is FORM-ID_CONTEXT. Defaults to null and if not provided, all cached forms will be flushed.
	 *
	 * @return void
	 */
	public static function flush_cached_forms( $cache_key = null ) {
		if ( $cache_key ) {
			unset( self::$cached_forms[ $cache_key ] );
		} else {
			self::$cached_forms = array();
		}
	}

	/**
	 * @param array $form
	 * @param string $confirmation_message
	 * @param bool $ajax
	 * @return mixed
	 */
	public static function get_confirmation_markup( $form, $confirmation_message, $ajax, $style_settings = false, $form_theme = null ) {

		// Ensuring styles and theme are set on the form object.
		$form = self::set_form_styles( $form, $style_settings, $form_theme );

		//check admin setting for whether the progress bar should start at zero
		$start_at_zero = rgars($form, 'pagination/display_progressbar_on_confirmation');

		/**
		 * Filters whether the progress bar should start at zero.
		 *
		 * Change the progress bar on multi-page forms to start at zero percent.
		 * By default, the progress bar starts as if your first step has been completed.
		 *
		 * @param string $start_at_zero Admin setting for progress bar.
		 * @param array $form The current form object.
		 * @since 1.6.3
		 *
		 */
		$start_at_zero = apply_filters('gform_progressbar_start_at_zero', $start_at_zero, $form );

		$confirmation_type = rgars( $form, 'confirmation/type' );
		$pagination_type   = rgars( $form, 'pagination/type' );
		$is_admin          = GFCommon::is_form_editor() || GFCommon::is_entry_detail();
		$has_pages         = self::has_pages( $form );

		$has_confirmation_message = isset( $form['confirmation'] ) && $form['confirmation']['type'] == 'message';
		$has_progress_bar         = $start_at_zero && $has_pages && $form['pagination']['type'] == 'percentage';
		$confirmation_markup      = '';

		if ( $has_confirmation_message && $has_progress_bar && ! $is_admin  ) {
			//show progress bar on confirmation
			$confirmation_markup = self::get_progress_bar( $form, 0, $confirmation_message );
			if ( $ajax ) {
				$confirmation_markup = self::get_ajax_postback_html( $confirmation_markup );
			}
		} else {
			//return regular confirmation message
			if ( $ajax ) {
				$confirmation_markup = self::get_ajax_postback_html( $confirmation_message );
			} else {
				$confirmation_markup = $confirmation_message;
			}
		}

		/**
		 * Filters the form confirmation text.
		 *
		 * This filter allows the form confirmation text to be programmatically changed before it is rendered to the page.
		 *
		 * @param string $confirmation_markup Confirmation text to be filtered.
		 * @param array $form The current form object
		 * @since 2.5.15
		 *
		 */
		$confirmation_markup = gf_apply_filters( array( 'gform_get_form_confirmation_filter', $form['id'] ), $confirmation_markup, $form );

		GFCommon::log_debug(__METHOD__ . sprintf('(): Preparing form (#%d) confirmation completed in %F seconds.', $form['id'], GFCommon::timer_end(__METHOD__)));
		return $confirmation_markup;
	}
}
