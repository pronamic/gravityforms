<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_FileUpload extends GF_Field {

	public $type = 'fileupload';

	/**
	 * Stores the upload root dir for forms.
	 *
	 * @since 2.5.16
	 *
	 * @var string[]
	 */
	public static $forms_upload_roots;

	/**
	 * Stores the default upload root dir for forms.
	 *
	 * @since 2.5.16
	 *
	 * @var string[]
	 */
	public static $forms_default_upload_roots;

	/**
	 * Gets the file upload path information including the actual saved physical path from the entry meta if found.
	 *
	 * @since 2.5.16
	 *
	 * @param string       $file_url The file URL to look for.
	 * @param integer|null $entry_id The entry ID.
	 *
	 * @return array
	 */
	public static function get_file_upload_path_info( $file_url, $entry_id = null ) {

		$path_info = $entry_id ? gform_get_meta( $entry_id, self::get_file_upload_path_meta_key_hash( $file_url ) ) : null;

		if ( empty( $path_info ) || ! is_array( $path_info ) ) {
			return array(
				'path' => GFFormsModel::get_upload_root(),
				'url'  => GFFormsModel::get_upload_url_root(),
			);
		}

		return $path_info;
	}

	/**
	 * Gets the default upload roots using the form ID and current time.
	 *
	 * @since 2.5.16
	 *
	 * @param int $form_id  The form ID to create the root for,
	 *
	 * @return string[] The root path and url.
	 */
	public static function get_default_upload_roots( $form_id ) {

		$cached_default_root = rgar( self::$forms_default_upload_roots, $form_id );
		if ( $cached_default_root ) {
			return $cached_default_root;
		}

		// Generate the yearly and monthly dirs
		$time                    = current_time( 'mysql' );
		$y                       = substr( $time, 0, 4 );
		$m                       = substr( $time, 5, 2 );
		$default_target_root     = GFFormsModel::get_upload_path( $form_id ) . "/$y/$m/";
		$default_target_root_url = GFFormsModel::get_upload_url( $form_id ) . "/$y/$m/";

		self::$forms_default_upload_roots[ $form_id ] = array(
			'path' => $default_target_root,
			'url'  => $default_target_root_url,
			'y'    => $y,
			'm'    => $m,
		);

		return self::$forms_default_upload_roots[ $form_id ];
	}

	/**
	 * Returns the default file upload root and url for files stored by the provided form.
	 *
	 * @since 2.5.16
	 *
	 * @param integer $form_id The form ID of the form that will be used to generate the directory name.
	 *
	 * @return array
	 */
	public static function get_upload_root_info( $form_id ) {

		$cached_root = rgar( self::$forms_upload_roots, $form_id );
		if ( $cached_root ) {
			return $cached_root;
		}

		$default_upload_root_info             = self::get_default_upload_roots( $form_id );
		self::$forms_upload_roots[ $form_id ] = gf_apply_filters( array( 'gform_upload_path', $form_id ), $default_upload_root_info, $form_id );
		return self::$forms_upload_roots[ $form_id ];
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'File Upload', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to upload a file.', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--upload';
	}

	/**
	 * Returns the class names of the settings which should be available on the field in the form editor.
	 *
	 * @since 1.9
	 * @since 2.9.18 Updated to include the dynamic population setting.
	 *
	 * @return string[]
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'file_extensions_setting',
			'file_size_setting',
			'multiple_files_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'prepopulate_field_setting',
		);
	}

	/**
	 * Determines if the file type and extension check should be disabled.
	 *
	 * @since 2.9.18
	 *
	 * @return bool
	 */
	public function is_check_type_and_ext_disabled() {
		static $disabled;

		if ( ! is_bool( $disabled ) ) {
			/**
			 * Allows disabling the file type and extension check.
			 *
			 * @param bool $disabled Is the file type and extension check disabled? Default is false.
			 */
			$disabled = (bool) apply_filters( 'gform_file_upload_whitelisting_disabled', false );
		}

		return $disabled;
	}

	/**
	 * Returns the maximum file size in bytes.
	 *
	 * @since 2.9.18
	 *
	 * @return int
	 */
	public function get_max_file_size_bytes() {
		$max_size = $this->get_context_property( 'max_file_size_bytes' );
		if ( is_null( $max_size ) ) {
			$max_size = $this->maxFileSize > 0 ? $this->maxFileSize * 1048576 : wp_max_upload_size();
			$this->set_context_property( 'max_file_size_bytes', $max_size );
		}

		return $max_size;
	}

	/**
	 * Returns an array of cleaned allowed extensions.
	 *
	 * @since 2.9.18
	 *
	 * @return array
	 */
	public function get_clean_allowed_extensions() {
		$extensions = $this->get_context_property( 'clean_allowed_extensions' );
		if ( is_null( $extensions ) ) {
			$extensions = GFCommon::clean_extensions( $this->allowedExtensions );
			$this->set_context_property( 'clean_allowed_extensions', $extensions );
		}

		return $extensions;
	}

	/**
	 * Returns the file size validation message.
	 *
	 * @since 2.9.18
	 *
	 * @return string
	 */
	public function get_size_validation_message() {
		$max_upload_size_in_bytes = $this->get_max_file_size_bytes();
		$max_upload_size_in_mb    = $max_upload_size_in_bytes / 1048576;

		/* translators: %d: maximum file size in MB. */
		return sprintf( esc_html__( 'File exceeds size limit. Maximum file size: %dMB.', 'gravityforms' ), $max_upload_size_in_mb );
	}

	/**
	 * Returns an array containing the error message and file ID.
	 *
	 * @since 2.9.18
	 *
	 *
	 * @param array  $file     The file that was validated.
	 * @param string $message  The error message.
	 * @param string $name_key The key used to access the file name.
	 *
	 * @return array
	 */
	private function get_invalid_file_result( $file, $message, $name_key ) {
		$name = rgar( $file, $name_key );
		$name = ( $name_key === 'url' ) ? esc_url( $name ) : sanitize_file_name( $name );

		return array(
			'message' => $name . ' - ' . $message,
			'id'      => rgar( $file, 'id' ),
		);
	}

	/**
	 * Validates the given file.
	 *
	 * @since 2.9.18
	 *
	 * @param array $file   The file to validate.
	 * @param bool  $is_new Whether the file is new (from $_FILES) or an existing file (from GFFormsModel::$uploaded_files).
	 *
	 * @return array|false Returns an array containing the sanitized file name and validation message when invalid, or false when valid.
	 */
	public function is_invalid_file( $file, $is_new = true ) {
		GFCommon::log_debug( __METHOD__ . '(): Validating file: ' . json_encode( $file ) );
		$name_key  = $is_new ? 'name' : 'uploaded_filename';
		$file_name = rgar( $file, $name_key );

		if ( $is_new ) {
			$max_upload_size_in_bytes = $this->get_max_file_size_bytes();
			$max_upload_size_in_mb    = $max_upload_size_in_bytes / 1048576;

			if ( rgar( $file, 'error' ) !== UPLOAD_ERR_OK ) {
				switch ( rgar( $file, 'error' ) ) {
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						GFCommon::log_debug( __METHOD__ . '(): File exceeds size limit. Maximum file size: ' . $max_upload_size_in_mb . 'MB' );
						$message = $this->get_size_validation_message();
						break;
					default:
						/* translators: %d: PHP file upload error code. */
						$message = $this->errorMessage ?: sprintf( esc_html__( 'There was an error while uploading the file. Error code: %d.', 'gravityforms' ), $file['error'] );
				}

				return $this->get_invalid_file_result( $file, $message, $name_key );
			} elseif ( rgar( $file, 'size' ) > 0 && $file['size'] > $max_upload_size_in_bytes ) {
				GFCommon::log_debug( __METHOD__ . '(): File exceeds size limit. Maximum file size: ' . $max_upload_size_in_mb . 'MB' );

				return $this->get_invalid_file_result( $file, $this->get_size_validation_message(), $name_key );
			} elseif ( ! is_uploaded_file( rgar( $file, 'tmp_name' ) ) ) {
				GFCommon::log_debug( __METHOD__ . '(): File was not uploaded via HTTP POST.' );
				$message = $this->errorMessage ?: esc_html__( 'The file is not valid.', 'gravityforms' );

				return $this->get_invalid_file_result( $file, $message, $is_new );
			}

			if ( ! empty( $file_name ) && ! $this->is_check_type_and_ext_disabled() ) {
				$check_result = GFCommon::check_type_and_ext( $file, $file_name );
				if ( is_wp_error( $check_result ) ) {
					GFCommon::log_debug( sprintf( '%s(): %s; %s', __METHOD__, $check_result->get_error_code(), $check_result->get_error_message() ) );

					return $this->get_invalid_file_result( $file, $check_result->get_error_message(), $name_key );
				}
			}
		} elseif ( isset( $file['url'] ) && ! GFCommon::is_valid_url( $file['url'] ) ) {
			$message = $this->errorMessage ?: esc_html__( 'The file URL is not valid.', 'gravityforms' );

			return $this->get_invalid_file_result( $file, $message, 'url' );
		}

		$allowed_extensions = $this->get_clean_allowed_extensions();

		if ( empty( $allowed_extensions ) ) {
			if ( GFCommon::file_name_has_disallowed_extension( $file_name ) ) {
				GFCommon::log_debug( __METHOD__ . '(): The file has a disallowed extension.' );
				$message = $this->errorMessage ?: esc_html__( 'The uploaded file type is not allowed.', 'gravityforms' );

				return $this->get_invalid_file_result( $file, $message, $name_key );
			}
		} else {
			if ( ! GFCommon::match_file_extension( $file_name, $allowed_extensions ) ) {
				$allowed_extensions = implode( ', ', $allowed_extensions );
				GFCommon::log_debug( __METHOD__ . '(): The file extension is not allowed. Allowed extensions: ' . $allowed_extensions . '.' );

				/* translators: %s: comma-separated list of allowed file extensions. */
				$message = $this->errorMessage ?: sprintf( esc_html__( 'The uploaded file type is not allowed. Must be one of the following: %s.', 'gravityforms' ), $allowed_extensions );

				return $this->get_invalid_file_result( $file, $message, $name_key );
			}
		}

		return false;
	}

	/**
	 * Validates the field value(s).
	 *
	 * @since 1.9
	 * @since 2.9.18 Updated to use $this->get_submission_files() & $this->is_valid_file().
	 *
	 * @param string $value Empty or the JSON encoded array of files for an existing entry.
	 * @param array  $form  The form being processed.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		$files = $this->get_submission_files();
		if ( $this->is_submission_files_empty( $files ) ) {
			return;
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Validating field #%d (name: input_%d).', $this->id, $this->id ) );

		if ( ! $this->multipleFiles || ! rgblank( $this->maxFiles ) ) {
			$limit = $this->multipleFiles ? absint( $this->maxFiles ) : 1;
			$count = count( $files['existing'] ) + count( $files['new'] );

			if ( ! empty( $value ) && $this->type !== 'post_image' ) {
				$entry_files = is_array( $value ) ? $value : json_decode( $value, true );
				$count      += is_array( $entry_files ) ? count( $entry_files ) : 1;
			}

			if ( $count && $count > $limit ) {
				$this->failed_validation = true;
				/* translators: %1$d: the number of submitted files. %2$d: the allowed limit. */
				$this->validation_message = $this->errorMessage ?: sprintf( esc_html__( 'Number of files (%1$d) exceeds limit (%2$d).', 'gravityforms' ), $count, $limit );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Number of files (%d) exceeds limit (%d).', $count, $limit ) );

				return;
			}
		}

		$errors = array();

		foreach ( array( 'existing', 'new' ) as $key ) {
			foreach ( $files[ $key ] as $file ) {
				$is_new = ( $key === 'new' );
				$result = $this->is_invalid_file( $file, $is_new );
				if ( ! $result ) {
					continue;
				}

				$this->failed_validation = true;
				$errors[]                = $result;
			}
		}

		GFCommon::log_debug( __METHOD__ . '(): Validation complete.' );

		if ( ! $this->failed_validation ) {
			return;
		}

		if ( GFFormDisplay::get_submission_context() !== 'form-submit' ) {
			$this->validation_message = $this->multipleFiles ? array_column( $errors, 'message' ) : rgars( $errors, '0/message' );
		} else {
			if ( $this->multipleFiles ) {
				$count = count( $errors );
				/* translators: %d: the number of invalid files. */
				$this->set_context_property( 'validation_summary_message', sprintf( esc_html( _n( '%d file is invalid.', '%d files are invalid.', $count, 'gravityforms' ) ), $count ) );
				$this->set_context_property( 'multifile_messages', $errors );
			} else {
				$this->validation_message = rgars( $errors, '0/message' );
			}
		}
	}

	public function get_first_input_id( $form ) {

		return $this->multipleFiles ? 'gform_browse_button_' . $form['id'] . '_' . $this->id : 'input_' . $form['id'] . '_' . $this->id;
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since 1.9
	 * @since 2.9.18 Updated to use $this->get_clean_allowed_extensions(), $this->get_max_file_size_bytes(), and $this->get_submission_files_for_preview().
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$lead_id = absint( rgar( $entry, 'id' ) );

		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = absint( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;
		$class        = esc_attr( $class );

		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$tabindex        = $this->get_tabindex();
		$multiple_files  = $this->multipleFiles;
		$file_list_id    = 'gform_preview_' . $form_id . '_' . $id;

		// Generate upload rules messages ( allowed extensions, max no. of files, max file size ).
		$upload_rules_messages = array();
		// Extensions.
		$allowed_extensions = implode( ',', $this->get_clean_allowed_extensions() );
		if ( ! empty( $allowed_extensions ) ) {
			$upload_rules_messages[] = esc_attr( sprintf( __( 'Accepted file types: %s', 'gravityforms' ), str_replace( ',', ', ', $allowed_extensions ) ) );
		}
		// File size.
		$max_upload_size = $this->get_max_file_size_bytes();
		// translators: %s is replaced with a numeric string representing the maximum file size
		$upload_rules_messages[] = esc_attr( sprintf( __( 'Max. file size: %s', 'gravityforms' ), GFCommon::format_file_size( $max_upload_size ) ) );
		// No. of files.
		$max_files = ( $multiple_files && $this->maxFiles > 0 ) ? $this->maxFiles : 0;
		if ( $max_files ) {
			// translators: %s is replaced with a numeric string representing the maximum number of files
			$upload_rules_messages[] = esc_attr( sprintf( __( 'Max. files: %s', 'gravityforms' ), $max_files ) );
		}

		$rules_messages = implode( ', ', $upload_rules_messages ) . '.';

		$rules_messages_id = empty( $rules_messages ) ? '' : "gfield_upload_rules_{$this->formId}_{$this->id}";
		$describedby       = $this->get_aria_describedby( array( $rules_messages_id ) );

		if ( $multiple_files ) {
			$upload_action_url = trailingslashit( site_url() ) . '?gf_page=' . GFCommon::get_upload_page_slug();

			$browse_button_id  = 'gform_browse_button_' . $form_id . '_' . $id;
			$container_id      = 'gform_multifile_upload_' . $form_id . '_' . $id;
			$drag_drop_id      = 'gform_drag_drop_area_' . $form_id . '_' . $id;

			$validation_message_id = 'gform_multifile_messages_' . $form_id . '_' . $id;

			$messages_id        = "gform_multifile_messages_{$form_id}_{$id}";
			if ( empty( $allowed_extensions ) ) {
				$allowed_extensions = '*';
			}
			$disallowed_extensions = GFCommon::get_disallowed_file_extensions();
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX && 'rg_change_input_type' === rgpost( 'action' ) ) {
				$plupload_init = array();
			} else {
				$plupload_init = array(
					'runtimes'            => 'html5,flash,html4',
					'browse_button'       => $browse_button_id,
					'container'           => $container_id,
					'drop_element'        => $drag_drop_id,
					'filelist'            => $file_list_id,
					'unique_names'        => true,
					'file_data_name'      => 'file',
					/*'chunk_size' => '10mb',*/ // chunking doesn't currently have very good cross-browser support
					'url'                 => $upload_action_url,
					'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
					'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
					'filters'             => array(
						'mime_types'    => array( array( 'title' => __( 'Allowed Files', 'gravityforms' ), 'extensions' => $allowed_extensions ) ),
						'max_file_size' => $max_upload_size . 'b',
					),
					'multipart'           => true,
					'urlstream_upload'    => false,
					'multipart_params'    => array(
						'form_id'  => $form_id,
						'field_id' => $id,
					),
					'gf_vars'             => array(
						'max_files'             => $max_files,
						'message_id'            => $messages_id,
						'disallowed_extensions' => $disallowed_extensions,
					),
				);

				if ( GFCommon::form_requires_login( $form ) ) {
					$plupload_init['multipart_params'][ '_gform_file_upload_nonce_' . $form_id ] = wp_create_nonce( 'gform_file_upload_' . $form_id, '_gform_file_upload_nonce_' . $form_id );
				}
			}

			$plupload_init = gf_apply_filters( array( 'gform_plupload_settings', $form_id ), $plupload_init, $form_id, $this );

			$drop_files_here_text = esc_html__( 'Drop files here or', 'gravityforms' );
			$select_files_text    = esc_attr__( 'Select files', 'gravityforms' );

			$plupload_init_json = htmlspecialchars( json_encode( $plupload_init ), ENT_QUOTES, 'UTF-8' );
			$upload             = "<div id='{$container_id}' data-settings='{$plupload_init_json}' class='gform_fileupload_multifile'>
										<div id='{$drag_drop_id}' class='gform_drop_area gform-theme-field-control'>
											<span class='gform_drop_instructions'>{$drop_files_here_text} </span>
											<button type='button' id='{$browse_button_id}' class='button gform_button_select_files gform-theme-button gform-theme-button--control' {$describedby} {$tabindex} {$disabled_text}>{$select_files_text}</button>
										</div>
									</div>";

			$upload .= $rules_messages ? "<span class='gfield_description gform_fileupload_rules' id='{$rules_messages_id}'>{$rules_messages}</span>" : '';

			$messages       = '';
			$messages_array = $this->get_context_property( 'multifile_messages' );
			if ( is_array( $messages_array ) ) {
				foreach ( $messages_array as $message_array ) {
					$messages .= sprintf( "<li id='error_%s' class='gfield_description gfield_validation_message'>%s</li>", esc_attr( rgar( $message_array, 'id' ) ), esc_html( rgar( $message_array, 'message' ) ) );
				}
			}

			// The JS will also populate this.
			$upload .= "<ul class='validation_message--hidden-on-empty gform-ul-reset' id='{$messages_id}'>{$messages}</ul>";

			if ( $is_entry_detail ) {
				$upload .= sprintf( '<input type="hidden" name="input_%d" value=\'%s\' />', $id, esc_attr( $value ) );
			}
		} else {
			$upload = '';
			if ( $max_upload_size <= 2047 * 1048576 ) {
				//  MAX_FILE_SIZE > 2048MB fails. The file size is checked anyway once uploaded, so it's not necessary.
				$upload = sprintf( "<input type='hidden' name='MAX_FILE_SIZE' value='%d' />", $max_upload_size );
			}

			$live_validation_message_id = 'live_validation_message_' . $form_id . '_' . $id;

			$upload .= sprintf( "<input name='input_%d' id='%s' type='file' class='%s' %s onchange='javascript:gformValidateFileSize( this, %s );' {$tabindex} %s/>", $id, $field_id, esc_attr( $class ), $describedby, esc_attr( $max_upload_size ), $disabled_text );

			$upload .= $rules_messages ? "<span class='gfield_description gform_fileupload_rules' id='{$rules_messages_id}'>{$rules_messages}</span>" : '';
			$upload .= "<div class='gfield_description validation_message gfield_validation_message validation_message--hidden-on-empty' id='{$live_validation_message_id}'></div>";
		}

		if ( $is_entry_detail && ! empty( $value ) ) { // edit entry
			if ( $multiple_files ) {
				$file_urls = json_decode( $value, true );
				if ( ! is_array( $file_urls ) ) {
					$file_urls = array();
				}
			} else {
				$file_urls = array( $value );
			}

			$upload_display = $multiple_files ? '' : "style='display:none'";
			$preview        = "<div id='upload_$id' {$upload_display}>$upload</div>";
			$preview .= sprintf( "<div id='%s' class='ginput_preview_list'></div>", $file_list_id );
			$preview .= sprintf( "<div id='preview_existing_files_%d'>", $id );

			foreach ( $file_urls as $file_index => $file_url ) {

				/**
				 * Allow for override of SSL replacement.
				 *
				 * By default Gravity Forms will attempt to determine if the schema of the URL should be overwritten for SSL.
				 * This is not ideal for all situations, particularly domain mapping. Setting $field_ssl to false will prevent
				 * the override.
				 *
				 * @since 2.1.1.23
				 *
				 * @param bool                $field_ssl True to allow override if needed or false if not.
				 * @param string              $file_url  The file URL in question.
				 * @param GF_Field_FileUpload $field     The field object for further context.
				 */
				$field_ssl = apply_filters( 'gform_secure_file_download_is_https', true, $file_url, $this );

				if ( $field_ssl === true && GFCommon::is_ssl() && str_contains( $file_url, 'http:' ) ) {
					$file_url = str_replace( 'http:', 'https:', $file_url );
				}

				$download_file_text = esc_attr__( 'Download file', 'gravityforms' );
				$delete_file_text   = esc_attr__( 'Delete file', 'gravityforms' );
				$view_file_text     = esc_attr__( 'View file', 'gravityforms' );
				$file_index         = intval( $file_index );
				$file_url           = esc_attr( $file_url );
				$display_file_url   = GFCommon::truncate_url( $file_url );
				$file_url           = $this->get_download_url( $file_url );

				$preview .= "<div id='preview_file_{$file_index}' class='ginput_preview'>
								<a href='{$file_url}' target='_blank' aria-label='{$view_file_text}'>{$display_file_url}</a>
								<a href='{$file_url}' target='_blank' aria-label='{$download_file_text}' class='ginput_preview_control gform-icon gform-icon--circle-arrow-down'></a>
								<a href='javascript:void(0);' aria-label='{$delete_file_text}' onclick='DeleteFile({$lead_id},{$id},this);' onkeypress='DeleteFile({$lead_id},{$id},this);' class='ginput_preview_control gform-icon gform-icon--circle-delete'></a>
							</div>";
			}

			$preview .= '</div>';

			return $preview;
		} else {
			$files = $this->get_submission_files_for_preview();

			if ( ! empty( $files ) ) {
				$preview   = sprintf( "<div id='%s' class='ginput_preview_list'>", $file_list_id );
				foreach ( $files as $file_info ) {

					if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
						$file_upload_markup = "<img alt='" . esc_attr__( 'Delete file', 'gravityforms' ) . "' class='gform_delete' src='" . GFCommon::get_base_url() . "/images/delete.png' onclick='gformDeleteUploadedFile({$form_id}, {$id}, this);' onkeypress='gformDeleteUploadedFile({$form_id}, {$id}, this);' /> <strong>" . esc_html( $file_info['uploaded_filename'] ) . '</strong>';
					} else {
						$file_upload_markup = sprintf( '<span class="gfield_fileupload_filename">%s</span>', esc_html( $file_info['uploaded_filename'] ) );
						// TODO: get file size $file_upload_markup .= sprintf( '<span class="gfield_fileupload_filesize">%s</span>', esc_html( $file_info['uploaded_filesize'] ) );
						$file_upload_markup .= '<span class="gfield_fileupload_progress gfield_fileupload_progress_complete"><span class="gfield_fileupload_progressbar"><span class="gfield_fileupload_progressbar_progress" style="width: 100%;"></span></span><span class="gfield_fileupload_percent">100%</span></span>';
						$file_upload_markup .= sprintf(
							'<button class="gform_delete_file gform-theme-button gform-theme-button--simple" onclick="gformDeleteUploadedFile( %d, %d, this );"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">%s: %s</span></button>',
							$form_id,
							$id,
							esc_html__( 'Delete this file', 'gravityforms' ),
							esc_html( $file_info['uploaded_filename'] )
						);
					}

					/**
					 * Modify the HTML for the Multi-File Upload "preview."
					 *
					 * @since Unknown
					 *
					 * @param string $file_upload_markup The current HTML for the field.
					 * @param array  $file_info          Details about the file uploaded.
					 * @param int    $form_id            The current Form ID.
					 * @param int    $id                 The current Field ID.
					 */
					$file_upload_markup = apply_filters( 'gform_file_upload_markup', $file_upload_markup, $file_info, $form_id, $id );
					$preview           .= sprintf( "<div id='%s' class='ginput_preview'>%s</div>", esc_attr( rgar( $file_info, 'id' ) ), $file_upload_markup );
				}
				$preview .= '</div>';
				if ( ! $multiple_files ) {
					$upload = str_replace( " class='", " class='gform_hidden ", $upload );
				}

				return "<div class='ginput_container ginput_container_fileupload'>" . $upload . " {$preview}</div>";
			} else {

				$preview = $multiple_files ? sprintf( "<div id='%s' class='ginput_preview_list'></div>", $file_list_id ) : '';

				return "<div class='ginput_container ginput_container_fileupload'>$upload $preview</div>";
			}
		}
	}

	/**
	 * Is the given value considered empty for this field.
	 *
	 * @since 2.9.18
	 *
	 * @param string|array $value The value to check.
	 *
	 * @return bool
	 */
	public function is_value_empty( $value ) {
		return $this->is_value_submission_empty( $this->formId );
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @since 1.9
	 * @since 2.7.1  Updated to validate multifile uploads exist in the tmp folder.
	 * @since 2.7.4  Added the gform_validate_required_file_exists filter.
	 * @since 2.9.2  Updated to use GFFormsModel::get_tmp_upload_location().
	 * @since 2.9.18 Updated to use $this->get_submission_files().
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		$files = $this->get_submission_files();
		if ( $this->is_submission_files_empty( $files ) ) {
			return true;
		}

		$input_name   = 'input_' . absint( $this->id );
		$tmp_path     = rgar( GFFormsModel::get_tmp_upload_location( $form_id ), 'path' );
		$file_removed = false;

		foreach ( $files['existing'] as $key => $file ) {
			if ( empty( $file['uploaded_filename'] ) ) {
				GFCommon::log_debug( __METHOD__ . "(): Removing invalid file for {$input_name} key {$key}." );
				unset( $files['existing'][ $key ] );
				$file_removed = true;
				continue;
			}

			// Skip dynamically populated file URLs.
			if ( ! empty( $file['url'] ) ) {
				continue;
			}

			/*
			 * Allow add-ons and custom code to skip the file validation.
			 *
			 * @since 2.7.4
			 *
			 * @param bool   $skip_validation Whether to skip the file validation.
			 * @param array  $file            The file information.
			 * @param object $field           The current field object.
			*/
			if ( ! gf_apply_filters(
				array( 'gform_validate_required_file_exists', $form_id, $this->id ),
				isset( $file['temp_filename'] ),
				$file,
				$this
			) ) {
				// Skipping existing file populated by an add-on or custom code.
				continue;
			}

			if ( empty( $file['temp_filename'] ) ) {
				GFCommon::log_debug( __METHOD__ . "(): Removing invalid file for {$input_name} key {$key}." );
				unset( $files['existing'][ $key ] );
				$file_removed = true;
				continue;
			}

			$tmp_file = $tmp_path . wp_basename( $file['temp_filename'] );
			if ( ! file_exists( $tmp_file ) ) {
				GFCommon::log_debug( __METHOD__ . "(): Removing invalid file for {$input_name} key {$key}." );
				unset( $files['existing'][ $key ] );
				$file_removed = true;
			}
		}

		if ( $file_removed ) {
			$files['existing'] = array_values( $files['existing'] );
			$this->set_submission_files( $files );
		}

		return $this->is_submission_files_empty( $files );
	}

	/**
	 * Remove invalid file from the uploaded files array.
	 *
	 * @since 2.7.4
	 * @deprecated 2.9.18
	 * @remove-in 3.1
	 *
	 * @param $input_name
	 * @param $key
	 *
	 * @return void
	 */
	public function unset_uploaded_file( $input_name, $key ) {
		GFCommon::log_debug( __METHOD__ . "(): Removing invalid file for {$input_name} key {$key}." );
		unset( GFFormsModel::$uploaded_files[ $this->formId ][ $input_name ][ $key ] );
	}

	/**
	 * Returns the value to be saved to the entry.
	 *
	 * @since 1.9
	 * @since 2.9.18 Updated to cache the entry and form in context properties.
	 *
	 * @param string $value      The value to be saved.
	 * @param array  $form       The form currently being processed.
	 * @param string $input_name The input name used when accessing the $_POST.
	 * @param int    $lead_id    The ID of the entry currently being saved.
	 * @param array  $lead       The entry properties and values that have already been saved for the current submission.
	 *
	 * @return string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		$this->set_context_property( 'entry', $lead );
		$this->set_context_property( 'form', $form );

		if ( ! $this->multipleFiles ) {
			return $this->get_single_file_value( $form['id'], $input_name );
		}

		if ( $this->is_entry_detail() && empty( $lead ) ) {
			// Deleted files remain in the $value from $_POST so use the updated entry value.
			$lead  = GFFormsModel::get_lead( $lead_id );
			$value = rgar( $lead, strval( $this->id ) );
		}

		return $this->get_multifile_value( $form['id'], $input_name, $value, $lead_id );
	}

	/**
	 * Gets the JSON encoded array of file URLs to be saved for the multifile enabled field.
	 *
	 * @since 1.9
	 * @since 2.6.8  Added $entry_id parameter.
	 * @since 2.9.18 Updated to use $this->get_submission_files() and $this->filter_submission_files_pre_save().
	 *
	 * @param int    $form_id    ID of the form.
	 * @param string $input_name Name of the input (e.g. input_1).
	 * @param string $value      Value of the input.
	 * @param int    $entry_id   ID of the entry.
	 *
	 * @return string
	 */
	public function get_multifile_value( $form_id, $input_name, $value, $entry_id = null ) {
		global $_gf_uploaded_files;

		if ( empty( $_gf_uploaded_files ) ) {
			$_gf_uploaded_files = array();
		} elseif ( isset( $_gf_uploaded_files[ $input_name ] ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Using $_gf_uploaded_files global for field #%d.', $this->id ) );
			$value = $_gf_uploaded_files[ $input_name ];

			if ( ! GFCommon::is_json( $value ) ) {
				$value = $this->get_parsed_list_of_files( $value );
			}

			return $this->sanitize_entry_value( $value, $form_id );
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Running for field #%d.', $this->id ) );
		$files = $this->filter_submission_files_pre_save( $this->get_submission_files() );

		if ( $this->is_submission_files_empty( $files ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting; no files.' );

			return '';
		}

		$uploaded_files = array();
		$tmp_location   = GFFormsModel::get_tmp_upload_location( $form_id );

		foreach ( $files['existing'] as $file ) {
			if ( ! isset( $file['temp_filename'] ) ) {
				// File was previously uploaded to form; do not process temp.
				$existing_file = $this->check_existing_entry( $entry_id, $input_name, $file );

				if ( isset( $file['url'] ) ) {
					// Saving dynamically populated file URLs that aren't already in the entry.
					if ( ! is_string( $existing_file ) && GFCommon::is_valid_url( $file['url'] ) ) {
						$uploaded_files[] = $file['url'];
					}
					continue;
				}

				// If existing file is an array, we need to get the filename to avoid a fatal.
				if ( rgar( $existing_file, 'uploaded_filename' ) ) {
					$existing_file = $existing_file['uploaded_filename'];
				}

				// We already have the file path in $existing_file, however it's good to check that the file path in the entry meta matches.
				$uploaded_path = gform_get_meta( $entry_id, self::get_file_upload_path_meta_key_hash( $existing_file ) );

				if ( $uploaded_path ) {
					$uploaded_files[] = $uploaded_path['url'] . $uploaded_path['file_name'];
				} else {
					// If there is no file path in the entry meta or we're not editing an existing entry, get the upload path.
					$uploaded_path = GFFormsModel::get_file_upload_path( $form_id, $existing_file, false );

					if ( $uploaded_path ) {
						$uploaded_files[] = $uploaded_path['url'];
					}
				}
			} else {
				$temp_filepath = $tmp_location['path'] . wp_basename( $file['temp_filename'] );
				if ( file_exists( $temp_filepath ) ) {
					$uploaded_files[] = $this->move_temp_file( $form_id, $file );
				}
			}
		}

		foreach ( $files['new'] as $file ) {
			if ( $file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file( $file['tmp_name'] ) ) {
				continue;
			}

			$uploaded_files[] = $this->upload_file( $form_id, $file );
		}

		if ( ! empty( $value ) ) {
			// Merge with existing files (entry detail edit page or an add-on edit entry page).
			if ( ! empty( $uploaded_files ) ) {
				$array = json_decode( $value, true );
				if ( empty( $array ) || ! is_array( $array ) ) {
					$value = $uploaded_files;
				} else {
					$value = array_unique( array_merge( $array, $uploaded_files ) );
				}
				$value = json_encode( $value );
			}
		} else {
			if ( empty( $uploaded_files ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Aborting; no valid file, name, or URL to save.' );

				return '';
			}
			$value = json_encode( $uploaded_files );
		}

		$_gf_uploaded_files[ $input_name ] = $value;

		return $this->sanitize_entry_value( $value, $form_id );
	}

	/**
	 * Check existing entry for the file to re-use its URL rather than recreating as the date may be different.
	 *
	 * @since 2.6.8
	 * @since 2.9.18 Updated to support dynamically populated file URLs.
	 *
	 * @param int    $entry_id   The id of the current entry
	 * @param string $input_name The name of the input field (input_1)
	 * @param array  $file_info  Array of file details
	 *
	 * @return mixed Array of file details or URL of existing file
	 */
	public function check_existing_entry( $entry_id, $input_name, $file_info ) {
		$existing_entry = $entry_id ? GFAPI::get_entry( $entry_id ) : null;

		if ( ! $existing_entry || is_wp_error( $existing_entry ) ) {
			return $file_info;
		}

		$input_id          = str_replace( 'input_', '', $input_name );
		$existing_files    = GFCommon::maybe_decode_json( rgar( $existing_entry, $input_id ) );
		$existing_file_url = null;

		if ( ! is_array( $existing_files ) ) {
			return $file_info;
		}

		foreach ( $existing_files as $existing_file ) {
			if ( isset( $file_info['url'] ) && $existing_file === $file_info['url'] ) {
				$existing_file_url = $existing_file;
				break;
			}

			$existing_file_pathinfo = pathinfo( $existing_file );

			if ( $file_info['uploaded_filename'] === $existing_file_pathinfo['basename'] ) {
				$existing_file_url = $existing_file;
				break;
			}
		}

		if ( $existing_file_url ) {
			$file_info = $existing_file_url;
		}

		return $file_info;
	}

	/**
	 * Given the comma-delimited string of file paths, get the JSON array representing
	 * any which still exist (i.e., haven't been deleted using the UI).
	 *
	 * @since 2.5.8
	 * @since 2.9.18 Updated to use $this->get_submission_files() and deprecated the $form_id and $input_name params.
	 *
	 * @param string $value      A comma-delimited list of file paths.
	 * @param int    $form_id    The form ID for this entry.
	 * @param string $input_name The input name holding the current list of files.
	 *
	 * @return false|string
	 */
	public function get_parsed_list_of_files( $value, $form_id = 0, $input_name = '' ) {
		if ( func_num_args() === 1 ) {
			$uploaded = rgar( $this->get_submission_files(), 'existing' );
		} else {
			_deprecated_argument( __METHOD__, '2.9.18', 'The $form_id and $input_name parameters are no longer required.' );
			$uploaded = rgar( GFFormsModel::$uploaded_files, $form_id . '/' . $input_name, array() );
		}

		$uploaded = wp_list_pluck( $uploaded, 'uploaded_filename' );

		$parts = explode( ',', $value );
		$parts = array_filter(
			$parts,
			function ( $part ) use ( $uploaded ) {
				$basename = wp_basename( trim( $part ) );

				return in_array( $basename, $uploaded, true );
			}
		);

		return wp_json_encode( $parts );
	}

	/**
	 * Gets the value to be saved for the single file input.
	 *
	 * @since 1.9
	 * @since 2.9.18 Updated to use $this->get_submission_files(), $this->filter_submission_files_pre_save(), & GFFormsModel::get_tmp_upload_location().
	 *
	 * @param int    $form_id    ID of the form.
	 * @param string $input_name Name of the input (e.g. input_1).
	 *
	 * @return string
	 */
	public function get_single_file_value( $form_id, $input_name ) {
		global $_gf_uploaded_files;

		if ( empty( $_gf_uploaded_files ) ) {
			$_gf_uploaded_files = array();
		} elseif ( isset( $_gf_uploaded_files[ $input_name ] ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Using $_gf_uploaded_files global for field #%d.', $this->id ) );

			return $this->sanitize_entry_value( $_gf_uploaded_files[ $input_name ], $form_id );
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Running for field #%d.', $this->id ) );
		$files = $this->filter_submission_files_pre_save( $this->get_submission_files() );

		if ( $this->is_submission_files_empty( $files ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting; no file.' );

			return '';
		}

		$count = ( count( $files['existing'] ) + count( $files['new'] ) );
		if ( $count > 1 ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): %d files uploaded, only saving one.', $count ) );
		}

		$value = '';

		if ( ! empty( $files['new'][0] ) ) {
			if ( rgar( $files['new'][0], 'error' ) === UPLOAD_ERR_OK ) {
				GFCommon::log_debug( __METHOD__ . '(): Calling upload_file.' );
				$value = $this->upload_file( $form_id, $files['new'][0] );
			}
		} elseif ( ! empty( $files['existing'][0]['url'] ) ) {
			if ( GFCommon::is_valid_url( $files['existing'][0]['url'] ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Saving provided URL, not uploading file.' );
				$value = $files['existing'][0]['url'];
			}
		} elseif ( ! empty( $files['existing'][0]['temp_filename'] ) ) {
			$tmp_path = rgar( GFFormsModel::get_tmp_upload_location( $form_id ), 'path' );
			if ( empty( $tmp_path ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Aborting; GFFormsModel::get_tmp_upload_location() returned an empty path.' );

				return '';
			}

			$temp_filename = $files['existing'][0]['temp_filename'];
			if ( ! file_exists( $tmp_path . $temp_filename ) ) {
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Temporary file %s does not exist.', $tmp_path . $temp_filename ) );

				return '';
			}

			GFCommon::log_debug( __METHOD__ . '(): File already uploaded to tmp folder, moving.' );
			$value = $this->move_temp_file( $form_id, $files['existing'][0] );
		} else {
			// Field was populated with only the name of an existing file.
			$file_name = rgars( $files['existing'], '0/uploaded_filename' );
			if ( ! empty( $file_name ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Saving provided filename, not uploading file.' );
				$value = $file_name;
			}
		}

		if ( empty( $value ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting; no valid file, name, or URL to save.' );

			return '';
		}

		$_gf_uploaded_files[ $input_name ] = $value;

		return $this->sanitize_entry_value( $value, $form_id );
	}

	public function upload_file( $form_id, $file ) {
		GFCommon::log_debug( __METHOD__ . '(): Uploading file: ' . $file['name'] );
		$target = GFFormsModel::get_file_upload_path( $form_id, $file['name'] );
		if ( ! $target ) {
			GFCommon::log_debug( __METHOD__ . '(): FAILED (Upload folder could not be created.)' );

			return 'FAILED (Upload folder could not be created.)';
		}
		GFCommon::log_debug( __METHOD__ . '(): Upload folder is ' . print_r( $target, true ) );

		if ( move_uploaded_file( $file['tmp_name'], $target['path'] ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): File (tmp_name: %s) successfully moved to %s.', $file['tmp_name'], $target['path'] ) );
			$this->set_permissions( $target['path'] );

			return $target['url'];
		} else {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; move_uploaded_file() failed for file (tmp_name: %s) to %s.', $file['tmp_name'], $target['path'] ) );

			return 'FAILED (Temporary file could not be copied.)';
		}
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		if ( $this->multipleFiles ) {
			if ( is_array( $value ) ) {
				$uploaded_files_arr = $value;
			} else {
				$uploaded_files_arr = json_decode( $value, true );
				if ( ! is_array( $uploaded_files_arr ) ) {
					return '';
				}
			}

			$file_count = count( $uploaded_files_arr );
			if ( $file_count > 1 ) {
				/* translators: %d: Number of files */
				return sprintf( esc_html__( '%d files', 'gravityforms' ), $file_count );
			} elseif ( $file_count === 1 ) {
				$value = current( $uploaded_files_arr );
			} else {
				return '';
			}
		}

		$file_path = $value;
		if ( ! empty( $file_path ) ) {
			//displaying thumbnail (if file is an image) or an icon based on the extension
			$thumb     = GFEntryList::get_icon_url( $file_path );
			$file_path = $this->get_download_url( $file_path );
			$file_path = esc_attr( $file_path );
			$value = "<a href='$file_path' target='_blank'><span class='screen-reader-text'>" . esc_html__( 'View the image', 'gravityforms' ) . "</span><span class='screen-reader-text'>" . esc_html__( '(opens in a new tab)', 'gravityforms' ) . "</span><img src='$thumb' alt='' /></a>";
		}
		return $value;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 2.9.18 Updated to use $this->get_file_name_from_url().
	 *
	 * @param string|array $value    The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text, or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string|false
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( empty( $value ) ) {
			return '';
		}

		$output     = '';
		$output_arr = array();

		$files = json_decode( $value, true );
		if ( ! is_array( $files ) ) {
			$files = array_filter( array( $value ) );
		}

		$force_download = in_array( 'download', $this->get_modifiers() );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file_path ) {
				if ( is_array( $file_path ) ) {
					$basename  = rgar( $file_path, 'uploaded_name' );
					$file_path = rgar( $file_path, 'tmp_url' );
				} else {
					$basename = rgar( $this->get_file_name_from_url( $file_path ), 'sanitized', $file_path );
				}

				$file_path = $this->get_download_url( $file_path, $force_download );

				/**
				 * Allow for override of SSL replacement
				 *
				 * By default Gravity Forms will attempt to determine if the schema of the URL should be overwritten for SSL.
				 * This is not ideal for all situations, particularly domain mapping. Setting $field_ssl to false will prevent
				 * the override.
				 *
				 * @since 2.1.1.23
				 *
				 * @param bool                $field_ssl True to allow override if needed or false if not.
				 * @param string              $file_path The file path of the download file.
				 * @param GF_Field_FileUpload $field     The field object for further context.
				 */
				$field_ssl = apply_filters( 'gform_secure_file_download_is_https', true, $file_path, $this );

				if ( $field_ssl === true && GFCommon::is_ssl() && str_starts_with( $file_path, 'http:' ) ) {
					$file_path = str_replace( 'http:', 'https:', $file_path );
				}

				/**
				 * Allows for the filtering of the file path before output.
				 *
				 * @since 2.1.1.23
				 *
				 * @param string              $file_path The file path of the download file.
				 * @param GF_Field_FileUpload $field     The field object for further context.
				 */
				$file_path    = str_replace( ' ', '%20', apply_filters( 'gform_fileupload_entry_value_file_path', $file_path, $this ) );
				$output_arr[] = $format === 'text' ? $file_path : sprintf( "<li><a href='%s' target='_blank' aria-label='%s'>%s</a></li>", esc_attr( $file_path ), esc_attr__( 'Click to view', 'gravityforms' ), esc_html( $basename ) );

			}
			$output = join( PHP_EOL, $output_arr );
		}

		return empty( $output ) || $format === 'text' ? $output : sprintf( '<ul>%s</ul>', $output );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GF_Field::get_modifiers()
	 * @uses GF_Field_FileUpload::get_download_url()
	 *
	 * @param array|string $value      The value of the input.
	 * @param string       $input_id   The input ID to use.
	 * @param array        $entry      The Entry Object.
	 * @param array        $form       The Form Object
	 * @param string       $modifier   The modifier passed.
	 * @param array|string $raw_value  The raw value of the input.
	 * @param bool         $url_encode If the result should be URL encoded.
	 * @param bool         $esc_html   If the HTML should be escaped.
	 * @param string       $format     The format that the value should be.
	 * @param bool         $nl2br      If the nl2br function should be used.
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {

		if ( empty( $raw_value ) ) {
			return '';
		}

		$force_download = in_array( 'download', $this->get_modifiers() );

		$files = json_decode( $raw_value, true );
		if ( ! is_array( $files ) ) {
			$files = array( $raw_value );
		}

		foreach ( $files as &$file ) {
			if ( is_array( $file ) ) {
				$file = rgar( $file, 'tmp_url' );
			}
			$file = str_replace( ' ', '%20', $this->get_download_url( $file, $force_download ) );
			if ( $esc_html ) {
				$file = esc_html( $file );
			}
		}

		$value = $format == 'html' ? join( '<br />', $files ) : join( ', ', $files );

		if ( $url_encode ) {
			$value = urlencode( $value );
		}

		return $value;
	}


	public function move_temp_file( $form_id, $tempfile_info ) {

		$target       = GFFormsModel::get_file_upload_path( $form_id, $tempfile_info['uploaded_filename'] );
		$tmp_location = GFFormsModel::get_tmp_upload_location( $form_id );
		$source       = $tmp_location['path'] . wp_basename( $tempfile_info['temp_filename'] );


		GFCommon::log_debug( __METHOD__ . '(): Moving temp file from: ' . $source );

		if ( rename( $source, $target['path'] ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): File (temp_filename: %s) successfully moved to %s.', $tempfile_info['temp_filename'], $target['path'] ) );
			$this->set_permissions( $target['path'] );

			return $target['url'];
		} else {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; rename() failed for file (temp_filename: %s) to %s.', $tempfile_info['temp_filename'], $target['path'] ) );

			return 'FAILED (Temporary file could not be moved.)';
		}
	}

	function set_permissions( $path ) {
		GFCommon::log_debug( __METHOD__ . '(): Setting permissions on: ' . $path );

		GFFormsModel::set_permissions( $path );
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( $this->maxFileSize ) {
			$this->maxFileSize = absint( $this->maxFileSize );
		}

		if ( $this->maxFiles ) {
			$this->maxFiles = preg_replace( '/[^0-9,.]/', '', $this->maxFiles );
		}

		$this->multipleFiles = (bool) $this->multipleFiles;

		$this->allowedExtensions = sanitize_text_field( $this->allowedExtensions );
	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );
		if ( $this->multipleFiles && ! empty( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( ! is_array( $decoded ) ) {
				return $value;
			}

			return implode( ' , ', $decoded );
		}

		return $value;
	}

	/**
	 * Returns the download URL for a file. The URL is not escaped for output.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param string $file           The complete file URL.
	 * @param bool   $force_download If the download should be forced. Defaults to false.
	 *
	 * @return string
	 */
	public function get_download_url( $file, $force_download = false ) {
		$download_url = $file;

		$secure_download_location = true;

		/**
		 * By default the real location of the uploaded file will be hidden and the download URL will be generated with
		 * a security token to prevent guessing or enumeration attacks to discover the location of other files.
		 *
		 * Return FALSE to display the real location.
		 *
		 * @param bool                $secure_download_location If the secure location should be used.  Defaults to true.
		 * @param string              $file                     The URL of the file.
		 * @param GF_Field_FileUpload $this                     The Field
		 */
		$secure_download_location = apply_filters( 'gform_secure_file_download_location', $secure_download_location, $file, $this );
		$secure_download_location = apply_filters( 'gform_secure_file_download_location_' . $this->formId, $secure_download_location, $file, $this );

		if ( ! $secure_download_location ) {

			/**
			 * Allow filtering of the download URL.
			 *
			 * Allows for manual filtering of the download URL to handle conditions such as
			 * unusual domain mapping and others.
			 *
			 * @since 2.1.1.1
			 *
			 * @param string              $download_url The URL from which to download the file.
			 * @param GF_Field_FileUpload $field        The field object for further context.
			 */
			return apply_filters( 'gform_secure_file_download_url', $download_url, $this );

		}

		$upload_root = GFFormsModel::get_upload_url( $this->formId );
		$upload_root = trailingslashit( $upload_root );

		// Only hide the real URL if the location of the file is in the upload root for the form.
		// The upload root is calculated using the WP Salts so if the WP Salts have changed then file can't be located during the download request.
		if ( str_contains( $file, $upload_root ) ) {
			$file         = str_replace( $upload_root, '', $file );
			$download_url = site_url( 'index.php' );
			$args         = array(
				'gf-download' => urlencode( $file ),
				'form-id'     => $this->formId,
				'field-id'    => $this->id,
				'hash'        => GFCommon::generate_download_hash( $this->formId, $this->id, $file ),
			);
			if ( $force_download ) {
				$args['dl'] = 1;
			}
			$download_url = add_query_arg( $args, $download_url );
		}

		/**
		 * Allow filtering of the download URL.
		 *
		 * Allows for manual filtering of the download URL to handle conditions such as
		 * unusual domain mapping and others.
		 *
		 * @param string              $download_url The URL from which to download the file.
		 * @param GF_Field_FileUpload $field        The field object for further context.
		 */
		return apply_filters( 'gform_secure_file_download_url', $download_url, $this );
	}


	/**
	 * Stores the physical file paths as extra entry meta data.
	 *
	 * @since 2.5.16
	 *
	 * @param array $form  The form object being saved.
	 * @param array $entry The entry object being saved.
	 *
	 * @return array The array that contains the file URLs and their corresponding physical paths.
	 */
	public function get_extra_entry_metadata( $form, $entry ) {
		$value = rgar( $entry, absint( $this->id ) );
		if ( empty( $value ) ) {
			return array();
		}

		$extra_meta = array();
		if ( $this->multipleFiles ) {
			$file_values = json_decode( $value, true );
			if ( empty( $file_values ) ) {
				return array();
			}
		} else {
			$file_values = array( $value );
		}

		$form_id  = absint( rgar( $form, 'id' ) );
		$entry_id = absint( rgar( $entry, 'id' ) );

		// Use the filtered path to get the actual file path.
		$upload_root_info = self::get_upload_root_info( $form_id );

		// Default upload path to fall back to.
		$default_upload_root_info = self::get_default_upload_roots( $form_id );

		$root_url  = rgar( $upload_root_info, 'url', rgar( $default_upload_root_info, 'url' ) );
		$root_path = rgar( $upload_root_info, 'path', rgar( $default_upload_root_info, 'path' ) );

		foreach ( $file_values as $file_value ) {
			if ( is_array( $file_value ) ) {
				continue;
			}

			// Skip URLs that don't start with the root URL (e.g those populated on form display, Dropbox etc.)
			if ( ! str_starts_with( $file_value, $root_url ) ) {
				continue;
			}

			// If file already has a stored path, skip it.
			$stored_path_info = gform_get_meta( $entry_id, self::get_file_upload_path_meta_key_hash( $file_value ) );
			if ( ! empty( $stored_path_info ) ) {
				continue;
			}

			$file_path_info = array(
				'path'      => $root_path,
				'url'       => $root_url,
				'file_name' => wp_basename( $file_value ),
			);

			$file_url_hash                = self::get_file_upload_path_meta_key_hash( $file_value );
			$extra_meta[ $file_url_hash ] = $file_path_info;
		}

		return $extra_meta;
	}

	/**
	 * Gets a hash of the file URL to be used as the meta key when saving the file physical path to the entry meta.
	 *
	 * @since 2.5.16
	 *
	 * @param string $file_url The file URL to generate the hash for.
	 *
	 * @return string
	 */
	public static function get_file_upload_path_meta_key_hash( $file_url ) {
		return substr( hash( 'sha512', $file_url ), 0, 254 );
	}

	/**
	 * Returns an array of files found in the submission for the current field.
	 *
	 * @since 2.9.18
	 *
	 * @return array[] {
	 *      @type array[] $new {
	 *          File details from $_FILES.
	 *
	 *          @type string $name      The original name of the file on the client machine.
	 *          @type string $type      The MIME type of the file provided by the browser.
	 *          @type int    $size      The size of the file in bytes.
	 *          @type string $tmp_name  The temporary filename of the file in which the uploaded file was stored on the server.
	 *          @type int    $error     The PHP error code for the file upload. See https://www.php.net/manual/en/filesystem.constants.php#constant.upload-err-cant-write
	 *          @type string $full_path The full path as submitted by the browser. Only populated with PHP 8.1+.
	 *      }
	 *      @type array[] $existing {
	 *          File details from GFFormsModel::$uploaded_files.
	 *
	 *          @type string      $uploaded_filename The name of the uploaded file, an existing file, or the name parsed from the populated URL.
	 *          @type string|null $temp_filename     The temporary name of the file. Only present if the file has been saved to the form tmp folder.
	 *          @type string|null $url               The file URL. Only present if the file has been dynamically populated on initial form display.
	 *      }
	 * }
	 */
	public function get_submission_files() {
		$files = $this->get_context_property( 'submission_files' );
		if ( is_array( $files ) ) {
			return $files;
		}

		$input_name = 'input_' . absint( $this->id );
		$value      = rgars( GFFormsModel::$uploaded_files, absint( $this->formId ) . '/' . $input_name, array() );

		// Backwards compatibility; for integrations that set the input value to a file/basename.
		if ( is_string( $value ) ) {
			$value = array( array( 'uploaded_filename' => sanitize_file_name( $value ) ) );
		}

		$files = array(
			'existing' => $value,
			'new'      => array(),
		);

		if ( rgpost( "is_submit_{$this->formId}" ) !== '1' ) {
			return $files;
		}

		$files_input = rgar( $_FILES, $input_name ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $files_input['error'] ) ) {
			$this->set_submission_files( $files );

			return $files;
		}

		if ( is_array( $files_input['error'] ) ) {
			foreach ( $files_input['error'] as $key => $error ) {
				if ( $error === UPLOAD_ERR_NO_FILE ) {
					continue;
				}

				$files['new'][] = array(
					'name'      => rgars( $files_input, "name/{$key}" ),
					'type'      => rgars( $files_input, "type/{$key}" ),
					'size'      => rgars( $files_input, "size/{$key}", 0 ),
					'tmp_name'  => rgars( $files_input, "tmp_name/{$key}" ),
					'error'     => $error,
					'full_path' => rgars( $files_input, "full_path/{$key}" ), // PHP 8.1+.
				);
			}
		} elseif ( $files_input['error'] !== UPLOAD_ERR_NO_FILE ) {
			$files['new'][] = $files_input;
		}

		$this->set_submission_files( $files );

		return $files;
	}

	/**
	 * Caches the submission files in a context property and the existing files in GFFormsModel::$uploaded_files.
	 *
	 * @since 2.9.18
	 *
	 * @param null|array[] $files Null to flush the submission_files property or the array to be cached. See get_submission_files() for format.
	 *
	 * @return void
	 */
	public function set_submission_files( $files ) {
		$this->set_context_property( 'submission_files', $files );
		if ( is_null( $files ) ) {
			return;
		}

		$form_id    = absint( $this->formId );
		$input_name = 'input_' . absint( $this->id );

		if ( empty( $files['existing'] ) ) {
			unset( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] );
		} else {
			GFFormsModel::$uploaded_files[ $form_id ] ??= array();

			GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] = $files['existing'];
		}
	}

	/**
	 * Allows filtering (e.g., renaming) of the submission files before they are saved to the form uploads folder and entry.
	 *
	 * @since 2.9.18
	 *
	 * @param array[] $files See get_submission_files() for format.
	 *
	 * @return array[]
	 */
	private function filter_submission_files_pre_save( $files ) {
		$field = $this;
		$entry = $this->get_context_property( 'entry' );
		$form  = $this->get_context_property( 'form' );

		/**
		 * Allows filtering (e.g., renaming) of the submission files before they are saved to the form uploads folder and entry.
		 *
		 * @since 2.9.18
		 *
		 * @param array[]             $files See GF_Field_FileUpload::get_submission_files() for format.
		 * @param GF_Field_FileUpload $field The field the files are for.
		 * @param array               $entry The entry currently being saved. Only fields located before the current field will be included.
		 * @param array               $form  The form currently being processed.
		 */
		$filtered = apply_filters( 'gform_submission_files_pre_save_field_value', $files, $field, $entry, $form );

		if ( $filtered !== $files ) {
			$this->set_submission_files( $filtered );
		}

		return $filtered;
	}

	/**
	 * Determines if the submission files array is empty.
	 *
	 * @since 2.9.18
	 *
	 * @param null|array[] $files See get_submission_files().
	 *
	 * @return bool
	 */
	public function is_submission_files_empty( $files = null ) {
		if ( is_null( $files ) ) {
			$files = $this->get_submission_files();
		}

		return empty( $files['existing'] ) && empty( $files['new'] );
	}

	/**
	 * Normalizes the submission files array, including the uploaded_filename for new files, so it can be used to generate the preview markup.
	 *
	 * @since 2.9.18
	 *
	 * @return array[]
	 */
	public function get_submission_files_for_preview() {
		$files = $this->get_submission_files();
		if ( $this->is_submission_files_empty( $files ) ) {
			return array();
		}

		$normalized = $files['existing'];

		foreach ( $files['new'] as $file ) {
			$normalized[] = $this->get_tmp_file_details( $file );
		}

		$normalized = array_values(
			array_filter(
				$normalized,
				fn( $file ) => ! empty( $file['uploaded_filename'] )
			)
		);

		if ( $this->multipleFiles || empty( $normalized ) ) {
			return $normalized;
		}

		return array( $normalized[0] );
	}

	/**
	 * Returns the file details from the existing submission files.
	 *
	 * @since 2.9.18
	 *
	 * @param string $name The original file name.
	 *
	 * @return array
	 */
	public function get_tmp_file_details_from_submission_files( $name ) {
		$files = $this->get_submission_files();
		if ( empty( $files['existing'] ) ) {
			return array();
		}

		$sanitized_name = sanitize_file_name( $name );

		foreach ( $files['existing'] as $existing_file ) {
			if ( rgar( $existing_file, 'uploaded_filename' ) === $sanitized_name || rgar( $existing_file, 'uploaded_filename' ) === $name ) {
				return $existing_file;
			}
		}

		return array();
	}

	/**
	 * Returns the file parsed from $_FILES by $this->get_submission_files() that matches the given name.
	 *
	 * @since 2.9.18
	 *
	 * @param string $name The original file name.
	 *
	 * @return array
	 */
	public function get_new_file_from_submission_files( $name ) {
		$files = $this->get_submission_files();
		if ( empty( $files['new'] ) ) {
			return array();
		}

		foreach ( $files['new'] as $new_file ) {
			if ( rgar( $new_file, 'name' ) === $name ) {
				return $new_file;
			}
		}

		return array();
	}

	/**
	 * Returns the temporary file details.
	 *
	 * @since 2.9.18
	 *
	 * @param array|string $file_or_name A file from $this->get_submission_files() or $_FILES, or a filename.
	 *
	 * @return array {
	 *      @type string $uploaded_filename Sanitized version of the original file name.
	 *      @type string $temp_filename     The unique name to be used when the file is stored in the form tmp folder.
	 *      @type string $id                The UUID to be used with the ID attributes of the file preview and error markup.
	 * }
	 */
	public function get_tmp_file_details( $file_or_name ) {
		if ( is_string( $file_or_name ) ) {
			$details = $this->get_tmp_file_details_from_submission_files( $file_or_name );
			if ( ! empty( $details ) ) {
				return $details;
			}

			$file = $this->get_new_file_from_submission_files( $file_or_name );
			if ( empty( $file ) ) {
				return array();
			}
		} else {
			$file = $file_or_name;
		}

		// Abort early if we've already generated a temp filename or if a saved filename has been populated.
		if ( isset( $file['details'] ) || isset( $file['temp_filename'] ) || isset( $file['uploaded_filename'] ) ) {
			return empty( $file['details'] ) ? $file : $file['details'];
		}

		$uploaded_filename = rgar( $file, 'error' ) === UPLOAD_ERR_OK ? sanitize_file_name( $file['name'] ) : '';
		if ( empty( $uploaded_filename ) ) {
			return array();
		}

		$extension = pathinfo( $uploaded_filename, PATHINFO_EXTENSION );
		$uuid      = GFFormsModel::get_uuid();

		// This is the approach used by upload.php. The only difference is the use of GFFormsModel::get_uuid() instead of a UUID provided by plupload.js.
		$tmp_file_name = GFFormsModel::get_form_unique_id( absint( $this->formId ) ) . '_input_' . absint( $this->id ) . '_' . GFCommon::random_str( 16 ) . '_' . $uuid . '.' . $extension;

		$tmp_file = array(
			'uploaded_filename' => $uploaded_filename,
			'temp_filename'     => sanitize_file_name( $tmp_file_name ),
			'id'                => $uuid,
		);

		GFCommon::log_debug( __METHOD__ . '(): Details to use for new temporary file are: ' . json_encode( $tmp_file ) );

		return $tmp_file;
	}

	/**
	 * Returns an array of temporary files uploaded to the form tmp folder for the current submission.
	 *
	 * @since 2.9.18
	 *
	 * @return array[]
	 */
	public function upload_submission_tmp_files() {
		$id = absint( $this->id );

		if ( $this->failed_validation ) {
			GFCommon::log_debug( __METHOD__ . "(): Skipping field because it failed validation: {$this->label}({$id} - {$this->type})." );

			return array();
		}

		$files = $this->get_submission_files();
		if ( empty( $files['new'] ) ) {
			GFCommon::log_debug( __METHOD__ . "(): Skipping field because there are no files to process: {$this->label}({$id} - {$this->type})." );

			return array();
		}

		GFCommon::log_debug( __METHOD__ . "(): Processing files for field: {$this->label}({$id} - {$this->type})." );

		$allowed_extensions = $this->get_clean_allowed_extensions();
		$uploaded_files     = array();

		foreach ( $files['new'] as $key => $file ) {
			if ( $file['error'] !== UPLOAD_ERR_OK ) {
				GFCommon::log_debug( __METHOD__ . '(): Skipping file because there was an error: ' . json_encode( $file ) );
				continue;
			}

			if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Skipping file because it was not uploaded via HTTP POST: ' . json_encode( $file ) );
				continue;
			}

			$file_name = $file['name'];
			if ( GFCommon::file_name_has_disallowed_extension( $file_name ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Skipping file because the file extension is disallowed: ' . $file_name );
				continue;
			}

			if ( ! empty( $allowed_extensions ) && ! GFCommon::match_file_extension( $file_name, $allowed_extensions ) ) {
				GFCommon::log_debug( __METHOD__ . '(): Skipping file because the file extension is not allowed: ' . $file_name );
				continue;
			}

			if ( empty( $allowed_extensions ) && ! $this->is_check_type_and_ext_disabled() ) {
				$valid_file_name = GFCommon::check_type_and_ext( $file, $file_name );

				if ( is_wp_error( $valid_file_name ) ) {
					GFCommon::log_debug( __METHOD__ . '(): Skipping file because the uploaded file type is not allowed: ' . $file_name );
					continue;
				}
			}

			$tmp_file = $this->upload_tmp_file( $file );
			if ( empty( $tmp_file ) ) {
				continue;
			}

			$uploaded_files[]    = $tmp_file;
			$files['existing'][] = $tmp_file;
			unset( $files['new'][ $key ] );

			if ( ! $this->multipleFiles ) {
				break;
			}
		}

		if ( ! empty( $uploaded_files ) ) {
			if ( ! empty( $files['new'] ) ) {
				$files['new'] = array_values( $files['new'] );
				GFCommon::log_debug( __METHOD__ . '(): The following files were not uploaded: ' . json_encode( $files['new'] ) );
			}
			$this->set_submission_files( $files );
		}

		GFCommon::log_debug( __METHOD__ . "(): Processing completed for field: {$this->label}({$id} - {$this->type})." );

		return $uploaded_files;
	}

	/**
	 * Moves the file from the PHP tmp folder to the form tmp folder.
	 *
	 * @since 2.9.18
	 *
	 * @param array $file The file details parsed by $this->get_submission_files() from $_FILES.
	 *
	 * @return array The temporary file details.
	 */
	public function upload_tmp_file( $file ) {
		$details = $this->get_tmp_file_details( $file );
		if ( empty( $details ) ) {
			return array();
		}

		$form_id  = absint( $this->formId );
		$location = GFFormsModel::get_tmp_upload_location( $form_id );
		if ( empty( $location['path'] ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Aborting; GFFormsModel::get_tmp_upload_location() returned an empty path.' );

			return array();
		}

		$tmp_file_path = $location['path'] . $details['temp_filename'];
		if ( file_exists( $tmp_file_path ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; File (tmp_name: %s) already moved to %s.', $file['tmp_name'], $location['path'] ) );

			return $details;
		}

		if ( ! move_uploaded_file( $file['tmp_name'], $tmp_file_path ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; move_uploaded_file() failed for file (tmp_name: %s) to %s.', $file['tmp_name'], $location['path'] ) );

			return array();
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): File (tmp_name: %s) successfully moved to %s.', $file['tmp_name'], $location['path'] ) );

		$this->set_permissions( $tmp_file_path );

		return $details;
	}

	/**
	 * Supports using the dynamic population feature to populate the field on initial form display with file URLs.
	 *
	 * @since 2.9.18
	 *
	 * @param string $standard_name            The input name used when accessing the $_POST.
	 * @param string $custom_name              The dynamic population parameter name.
	 * @param array  $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool   $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @return array|string|null
	 */
	public function get_input_value_submission( $standard_name, $custom_name = '', $field_values = array(), $get_from_post_global_var = true ) {
		$value = parent::get_input_value_submission( $standard_name, $custom_name, $field_values, $get_from_post_global_var );

		if ( ! $this->allowsPrepopulate ) {
			return $value;
		}

		$is_not_file_input = $standard_name !== 'input_' . absint( $this->id ); // Needed for Post Image field.
		$is_submission     = ! empty( $_POST[ 'is_submit_' . absint( $this->formId ) ] ) && $get_from_post_global_var; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $is_not_file_input || $is_submission ) {
			return $value;
		}

		$this->populate_file_urls_from_value( $value );

		return $value;
	}

	/**
	 * Triggers population of GFFormsModel::$uploaded_files with any file URLs found in the given value.
	 *
	 * @since 2.9.18
	 *
	 * @param string|array $value A single file URL, a comma-seperated list of file URLs, a JSON encoded array of file URLs, or an array of file URLs.
	 *
	 * @return void
	 */
	public function populate_file_urls_from_value( $value ) {
		if ( GFCommon::is_empty_array( $value ) ) {
			return;
		}

		if ( is_array( $value ) ) {
			$urls = $value;
		} else {
			$urls = json_decode( $value, true );
			if ( ! is_array( $urls ) ) {
				$urls = explode( ',', $value );
			}
		}

		foreach ( $urls as $url ) {
			if ( ! is_string( $url ) ) {
				continue;
			}

			$details = $this->populate_file_url( trim( $url ) );
			if ( ! empty( $details ) && ! $this->multipleFiles ) {
				break;
			}
		}
	}

	/**
	 * Populates the submission_files context property & GFFormsModel::$uploaded_files with the details array for the given file URL.
	 *
	 * @since 2.9.18
	 *
	 * @param string $url The file URL.
	 *
	 * @return false|array {
	 *      @type string $uploaded_filename Sanitized version of the file/basename parsed from the URL.
	 *      @type string $url               Sanitized version of the file URL.
	 * }
	 */
	public function populate_file_url( $url ) {
		$name = $this->get_file_name_from_url( $url );
		if ( empty( $name['sanitized'] ) ) {
			return false;
		}

		$files         = $this->get_submission_files();
		$sanitized_url = esc_url_raw( $url );
		$details       = null;

		// If this is not empty, it was populated before the gform_field_value filter (e.g., old version of UR).
		if ( ! empty( $files['existing'] ) ) {
			foreach ( $files['existing'] as &$existing_file ) {
				$existing_name = rgar( $existing_file, 'uploaded_filename' );
				if ( $existing_name !== $name['sanitized'] && $existing_name !== $name['original'] ) {
					continue;
				}

				// Abort early if the URL has already been populated.
				if ( isset( $existing_file['url'] ) && $existing_file['url'] === $sanitized_url ) {
					return $existing_file;
				}

				$existing_file['uploaded_filename'] = $name['sanitized'];
				if ( isset( $existing_file['tmp_filename'] ) ) {
					$details = $existing_file;
					break;
				}

				$existing_file['url'] = $sanitized_url;

				if ( empty( $existing_file['id'] ) ) {
					$existing_file['id'] = GFFormsModel::get_uuid();
				}

				$details = $existing_file;
				break;
			}
		}

		if ( empty( $details ) ) {
			$details             = array(
				'uploaded_filename' => $name['sanitized'],
				'url'               => $sanitized_url,
				'id'                => GFFormsModel::get_uuid(),
			);
			$files['existing'][] = $details;
		}

		$this->set_submission_files( $files );

		return $details;
	}

	/**
	 * Parses the file name/basename from the given URL.
	 *
	 * @since 2.9.18
	 *
	 * @param string $url The file URL.
	 *
	 * @return false|array {
	 *      @type string $original  The original file name.
	 *      @type string $sanitized The sanitized file name.
	 * }
	 */
	public function get_file_name_from_url( $url ) {
		if ( empty( $url ) || ! GFCommon::is_valid_url( $url ) ) {
			return false;
		}

		$components   = parse_url( $url );
		$query_string = rgars( $components, 'query' );

		// If the URL is one of our secure URLs, get the name from the appropriate query arg, with a fallback to the path.
		if ( $query_string ) {
			parse_str( $query_string, $query_args );
			$file_name = rgar( $query_args, 'gf-download' ) ?? rgar( $query_args, 'gf-signature' );
			$file_name = $file_name ? urldecode( $file_name ) : rgar( $components, 'path' );
		} else {
			$file_name = rgar( $components, 'path' );
		}

		$file_name = wp_basename( $file_name );

		return array(
			'original'  => $file_name,
			'sanitized' => sanitize_file_name( $file_name ),
		);
	}

}

GF_Fields::register( new GF_Field_FileUpload() );
