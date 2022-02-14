<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Textarea extends GF_Field {

	public $type = 'textarea';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Paragraph Text', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit multiple lines of text.', 'gravityforms' );
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
		return 'gform-icon--paragraph-text';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'maxlen_setting',
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_textarea_setting',
			'placeholder_textarea_setting',
			'description_setting',
			'css_class_setting',
			'rich_text_editor_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function allow_html() {
		return empty( $this->useRichTextEditor ) ? false : true;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		global $current_screen;

		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$is_admin = $is_entry_detail || $is_form_editor;

		$id            = intval( $this->id );
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$size          = $this->size;
		$class_suffix  = $is_entry_detail ? '_admin' : '';
		$class         = $size . $class_suffix;
		$class         = esc_attr( $class );
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$maxlength_attribute   = is_numeric( $this->maxLength ) ? "maxlength='{$this->maxLength}'" : '';
		$placeholder_attribute = $this->get_field_placeholder_attribute();
		$required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby      = $this->get_aria_describedby();

		$tabindex = $this->get_tabindex();

		if ( $this->get_allowable_tags() === false ) {
			$value = esc_textarea( $value );
		} else {
			$value = wp_kses_post( $value );
		}

		//see if the field is set to use the rich text editor
		if ( ! $is_admin && $this->is_rich_edit_enabled() && ( ! $current_screen || ( $current_screen && ! rgobj( $current_screen, 'is_block_editor' ) ) ) ) {
			//placeholders cannot be used with the rte; message displayed in admin when this occurs
			//field cannot be used in conditional logic by another field; message displayed in admin and field removed from conditional logic drop down
			$tabindex = GFCommon::$tab_index > 0 ? GFCommon::$tab_index ++ : '';

			add_filter( 'mce_buttons', array( $this, 'filter_mce_buttons' ), 10, 2 );
			add_filter( 'mce_buttons_2', array( $this, 'filter_mce_buttons' ), 10, 2 );
			add_filter( 'mce_buttons_3', array( $this, 'filter_mce_buttons' ), 10, 2 );
			add_filter( 'mce_buttons_4', array( $this, 'filter_mce_buttons' ), 10, 2 );

			/**
			 * Filters the field options for the rich text editor.
			 *
			 * @since 2.0.0
			 *
			 * @param array  $editor_settings Array of settings that can be changed.
			 * @param object $this            The field object
			 * @param array  $form            Current form object
			 * @param array  $entry           Current entry object, if available
			 *
			 * Additional filters for specific form and fields IDs.
			 */
			$editor_settings = apply_filters( 'gform_rich_text_editor_options', array(
				'textarea_name' => 'input_' . $id,
				'wpautop' 		=> true,
				'editor_class' 	=> $class,
				'editor_height' => rgar( array( 'small' => 110, 'medium' => 180, 'large' => 280 ), $this->size ? $this->size : 'medium' ),
				'tabindex' 		=> $tabindex,
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'		=> array( 'init_instance_callback' =>  "function (editor) {
												editor.on( 'keyup paste mouseover', function (e) {
													var content = editor.getContent( { format: 'text' } ).trim();													
													var textarea = jQuery( '#' + editor.id ); 
													textarea.val( content ).trigger( 'keyup' ).trigger( 'paste' ).trigger( 'mouseover' );													
												
													
												});}" ),
			), $this, $form, $entry );

			$editor_settings = apply_filters( sprintf( 'gform_rich_text_editor_options_%d', $form['id'] ),               $editor_settings, $this, $form, $entry );
			$editor_settings = apply_filters( sprintf( 'gform_rich_text_editor_options_%d_%d', $form['id'], $this->id ), $editor_settings, $this, $form, $entry );

			if ( ! has_action( 'wp_tiny_mce_init', array( __class__, 'start_wp_tiny_mce_init_buffer' ) ) ) {
				add_action( 'wp_tiny_mce_init', array( __class__, 'start_wp_tiny_mce_init_buffer' ) );
			}

			ob_start();
			wp_editor( $value, $field_id, $editor_settings );
			$input = ob_get_clean();

			remove_filter( 'mce_buttons', array( $this, 'filter_mce_buttons' ), 10 );
			remove_filter( 'mce_buttons_2', array( $this, 'filter_mce_buttons' ), 10 );
			remove_filter( 'mce_buttons_3', array( $this, 'filter_mce_buttons' ), 10 );
			remove_filter( 'mce_buttons_4', array( $this, 'filter_mce_buttons' ), 10 );
		} else {

			$input       = '';
			$input_style = '';

			// RTE preview
			if ( $this->is_form_editor() ) {
				$display     = $this->useRichTextEditor ? 'block' : 'none';
				$input_style = $this->useRichTextEditor ? 'style="display:none;"' : '';
				$size        = $this->size ? $this->size : 'medium';
				$input       = "<div id='{$field_id}_rte_preview' class='gform-rte-preview {$size}' style='display:{$display}'>
						<ul class='rte_preview_header'>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M7.761 19c-.253 0-.44-.06-.56-.18-.12-.12-.18-.307-.18-.56l-.02-12.52c0-.267.08-.457.2-.57.12-.113.307-.17.56-.17h5.019c1.56 0 2.723.317 3.49.95.767.633 1.15 1.497 1.15 2.59 0 .667-.21 1.283-.63 1.85-.42.567-.937.963-1.55 1.19l.02.08c.387.067.793.247 1.22.54.427.293.787.677 1.08 1.15.293.473.44 1.01.44 1.61 0 1.32-.427 2.323-1.28 3.01-.853.687-2.12 1.03-3.8 1.03H7.761zm4.969-8.26c.687 0 1.237-.17 1.65-.51.414-.34.621-.77.621-1.29 0-1.147-.757-1.72-2.271-1.72H9.28v3.52h3.45zm.59 6.02c.725 0 1.283-.17 1.674-.51.39-.34.586-.823.586-1.45 0-.587-.223-1.037-.67-1.35-.446-.313-1.088-.47-1.925-.47H9.28v3.78h4.04z' fill='#555d66' stroke='none' stroke-width='1' fill-rule='evenodd'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' ersion='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M16.746 4.723l-.176.84h-.263c-.638 0-1.097.12-1.377.36-.28.242-.47.6-.567 1.075l-2.07 9.805c-.059.28-.088.465-.088.556 0 .534.482.801 1.445.801h.254l-.175.84h-5.82l.155-.84h.264c1.107 0 1.761-.478 1.963-1.435l2.08-9.805c.052-.247.078-.433.078-.557 0-.534-.482-.8-1.445-.8h-.254l.176-.84h5.82z' fill='#555d66' stroke='none' stroke-width='1' fill-rule='evenodd'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 48 48' version='1.1' xmlns='http://www.w3.org/2000/svg'><g stroke='none' stroke-width='1' fill='#555d66' fill-rule='evenodd'><circle cx='11' cy='14' r='3'/><circle cx='11' cy='24' r='3'/><circle cx='11' cy='34' r='3'/><path opacity='.2' d='M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z'/></g></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 48 48' version='1.1' xmlns='http://www.w3.org/2000/svg'><g stroke='none' stroke-width='1' fill='#555d66' fill-rule='evenodd'><path opacity='.2' d='M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z'/><path d='M10.054 17v-4.769H9.98l-1.47 1.013v-1.166l1.549-1.067h1.249V17h-1.254zm3.557.112c-.444 0-.734-.303-.734-.722 0-.42.29-.718.734-.718.449 0 .735.299.735.718 0 .42-.286.722-.735.722zm-5.076 5.692c0-1.158.889-1.947 2.184-1.947 1.249 0 2.12.718 2.12 1.748 0 .651-.352 1.212-1.39 2.187l-1.146 1.092v.074h2.624V27H8.606v-.876l1.955-1.913c.842-.822 1.054-1.133 1.054-1.523 0-.482-.39-.822-.938-.822-.581 0-.98.382-.98.934v.025H8.536v-.021zm6.35 4.308c-.444 0-.734-.303-.734-.722 0-.42.29-.718.734-.718.449 0 .735.299.735.718 0 .42-.286.722-.735.722zm-4.818 7.29v-.934h.73c.569 0 .955-.332.955-.822 0-.481-.374-.788-.959-.788-.58 0-.967.328-1 .846H8.635c.042-1.133.884-1.847 2.191-1.847 1.229 0 2.113.673 2.113 1.615 0 .693-.436 1.233-1.104 1.37v.074c.822.092 1.336.64 1.336 1.428 0 1.05-.987 1.81-2.353 1.81-1.336 0-2.241-.74-2.295-1.868h1.2c.037.506.464.826 1.108.826.626 0 1.062-.353 1.062-.863 0-.523-.41-.847-1.083-.847h-.743zm4.765 2.71c-.445 0-.735-.303-.735-.722 0-.42.29-.718.735-.718.448 0 .734.299.734.718 0 .42-.286.722-.734.722z'/></g></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M7.5 17h.882a1 1 0 00.894-.553L11 13V8a1 1 0 00-1-1H6a1 1 0 00-1 1v4a1 1 0 001 1h2l-1.33 2.658A.927.927 0 007.5 17zm8 0h.882a1 1 0 00.894-.553L19 13V8a1 1 0 00-1-1h-4a1 1 0 00-1 1v4a1 1 0 001 1h2l-1.33 2.658A.927.927 0 0015.5 17z' fill='#555d66' fill-rule='nonzero' stroke='none' stroke-width='1'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M5 5h14a1 1 0 010 2H5a1 1 0 110-2zm0 4h8a1 1 0 010 2H5a1 1 0 110-2zm0 8h8a1 1 0 010 2H5a1 1 0 010-2zm0-4h14a1 1 0 010 2H5a1 1 0 010-2z' fill='#555d66' stroke='none' stroke-width='1' fill-rule='evenodd'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M5 5h14a1 1 0 010 2H5a1 1 0 110-2zm3 4h8a1 1 0 010 2H8a1 1 0 110-2zm0 8h8a1 1 0 010 2H8a1 1 0 010-2zm-3-4h14a1 1 0 010 2H5a1 1 0 010-2z' fill='#555d66' stroke='none' stroke-width='1' fill-rule='evenodd'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M5 5h14a1 1 0 010 2H5a1 1 0 110-2zm6 4h8a1 1 0 010 2h-8a1 1 0 010-2zm0 8h8a1 1 0 010 2h-8a1 1 0 010-2zm-6-4h14a1 1 0 010 2H5a1 1 0 010-2z' fill='#555d66' stroke='none' stroke-width='1' fill-rule='evenodd'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M6.19 12.345a.97.97 0 011.653.683.966.966 0 01-.283.684l-2.055 2.052a1.932 1.932 0 000 2.735 1.94 1.94 0 002.74 0l4.794-4.787a.966.966 0 000-1.367.966.966 0 010-1.368.97.97 0 011.37 0 2.898 2.898 0 010 4.103l-4.795 4.787a3.879 3.879 0 01-5.48 0 3.864 3.864 0 010-5.47l2.056-2.053v.001zm11.62-.69a.97.97 0 01-1.653-.684c0-.256.102-.502.283-.683l2.055-2.052a1.932 1.932 0 000-2.735 1.94 1.94 0 00-2.74 0l-4.793 4.787a.966.966 0 000 1.367.966.966 0 010 1.368.97.97 0 01-1.37 0 2.898 2.898 0 010-4.103l4.795-4.787a3.879 3.879 0 015.48 0 3.864 3.864 0 010 5.47l-2.057 2.053v-.001z' fill='#555d66' fill-rule='nonzero' stroke='none' stroke-width='1'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M15.32 9.937L14.063 8.68l2.902-2.902h-2.298a.889.889 0 110-1.778h4.444c.491 0 .889.398.889.889v4.444a.889.889 0 11-1.778 0V7.035L15.32 9.937zm0 4.126l2.902 2.902v-2.298a.889.889 0 111.778 0v4.444c0 .491-.398.889-.889.889h-4.444a.889.889 0 110-1.778h2.298l-2.902-2.902 1.257-1.257zM9.937 15.32l-2.902 2.902h2.298a.889.889 0 110 1.778H4.89A.889.889 0 014 19.111v-4.444a.889.889 0 111.778 0v2.298l2.902-2.902 1.257 1.257zM8.68 9.937L5.778 7.035v2.298a.889.889 0 01-1.778 0V4.89C4 4.398 4.398 4 4.889 4h4.444a.889.889 0 010 1.778H7.035L9.937 8.68 8.68 9.937z' fill='#555d66' fill-rule='nonzero' stroke='none' stroke-width='1'/></svg></li>
							<li class='icon'><svg width='24' height='24' viewBox='0 0 24 24' version='1.1' xmlns='http://www.w3.org/2000/svg'><path d='M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z' fill='#555d66' fill-rule='nonzero' stroke='none' stroke-width='1'/></svg></li>
						</ul>
					</div>";
			}

			$input .= "<textarea name='input_{$id}' id='{$field_id}' class='textarea {$class}' {$tabindex} {$aria_describedby} {$maxlength_attribute} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text} {$input_style} rows='10' cols='50'>{$value}</textarea>";

		}

		return sprintf( "<div class='ginput_container ginput_container_textarea'>%s</div>", $input );
	}

	public function validate( $value, $form ) {
		if ( ! is_numeric( $this->maxLength ) ) {
			return;
		}

		if ( $this->useRichTextEditor ) {
			$value = wp_specialchars_decode( $value );
		}

		// Clean the string of characters not counted by the textareaCounter plugin.
		$value = strip_tags( $value );
		$value = str_replace( "\r", '', $value );
		$value = trim( $value );

		if ( GFCommon::safe_strlen( $value ) > $this->maxLength ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'The text entered exceeds the maximum number of characters.', 'gravityforms' ) : $this->errorMessage;
		}
	}

	public static function start_wp_tiny_mce_init_buffer() {
		ob_start();
		add_action( 'after_wp_tiny_mce', array( __class__, 'end_wp_tiny_mce_init_buffer' ), 1 );
	}

	public static function end_wp_tiny_mce_init_buffer() {

		$script = ob_get_clean();
		$pattern = '/(<script.*>)([\s\S]+)(<\/script>)/';

		preg_match_all( $pattern, $script, $matches, PREG_SET_ORDER );

		// Fix editor height issue: https://core.trac.wordpress.org/ticket/45461.
		$wp_version       = get_bloginfo( 'version' );
		$height_issue_fix = version_compare( $wp_version, '5.0', '>=' ) && version_compare( $wp_version, '5.2', '<' ) ? ' gform_post_conditional_logic' : '';

		foreach ( $matches as $match ) {

			list( $search, $open_tag, $guts, $close_tag ) = $match;

			$custom  = "\tif ( typeof current_page === 'undefined' ) { return; }\n\twindow.gformInitTinymce = function(){\n\tfor( var id in tinymce.editors ) { tinymce.EditorManager.remove( tinymce.editors[id] ); }";
			$replace = sprintf(
				"%s\nfunction gformInitMCEInstances() { jQuery( document ).on( 'gform_post_render%s', function( event, form_id, current_page ) { \n%s\n%s\n\t}\n\tgformInitTinymce();\n} );}; gform.initializeOnLoaded( gformInitMCEInstances );\n%s",
				$open_tag,
				$height_issue_fix,
				$custom,
				$guts,
				$close_tag
			);
			$script  = str_replace( $search, $replace, $script );

		}

		echo $script;

	}

	public function filter_mce_buttons( $mce_buttons, $editor_id ) {

		$remove_key = array_search( 'wp_more', $mce_buttons );
		if ( $remove_key !== false ) {
			unset( $mce_buttons[ $remove_key ] );
		}

		// Get current filter to detect which mce_buttons core filter is running.
		$current_filter = current_filter();

		// Depending on the current mce_buttons filter, set variable to support filtering all potential rows.
		switch ( $current_filter ) {

			case 'mce_buttons_2':
				$mce_filter = '_row_two';
				break;

			case 'mce_buttons_3':
				$mce_filter = '_row_three';
				break;

			case 'mce_buttons_4':
				$mce_filter = '_row_four';
				break;

			default:
				$mce_filter = '';
				break;

		}

		/**
		 * Filters the buttons within the TinyMCE editor
		 *
		 * @since 2.0.0
		 *
		 * @param array  $mce_buttons Buttons to be included.
		 * @param string $editor_id   HTML ID of the field.
		 * @param object $this        The field object
		 *
		 * Additional filters for specific form and fields IDs.
		 */
		$mce_buttons = gf_apply_filters( array( 'gform_rich_text_editor_buttons' . $mce_filter, $this->formId, $this->id ), $mce_buttons, $editor_id, $this );

		return $mce_buttons;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 * Return a value that's safe to display for the context of the given $format.
	 *
	 * @param string|array $value The field value.
	 * @param string $currency The entry currency code.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string $format The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string $media The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		if ( $format === 'html' ) {

			$allowable_tags = $this->get_allowable_tags();

			if ( $allowable_tags === false ) {
				// The value is unsafe so encode the value.
				$value = esc_html( $value );
				$return = nl2br( $value );

			} else {
				// The value contains HTML but the value was sanitized before saving.
				$return = wpautop( $value );
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value for when the field/input merge tag is processed. Not called for the {all_fields} merge tag.
	 *
	 * Return a value that is safe for the context specified by $format.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $value      The field value. Depending on the location the merge tag is being used the following functions may have already been applied to the value: esc_html, nl2br, and urlencode.
	 * @param string       $input_id   The field or input ID from the merge tag currently being processed.
	 * @param array        $entry      The Entry Object currently being processed.
	 * @param array        $form       The Form Object currently being processed.
	 * @param string       $modifier   The merge tag modifier. e.g. value
	 * @param string|array $raw_value  The raw field value from before any formatting was applied to $value.
	 * @param bool         $url_encode Indicates if the urlencode function may have been applied to the $value.
	 * @param bool         $esc_html   Indicates if the esc_html function may have been applied to the $value.
	 * @param string       $format     The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param bool         $nl2br      Indicates if the nl2br function may have been applied to the $value.
	 *
	 * @return string
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {

		if ( $format === 'html' ) {
			$form_id        = absint( $form['id'] );
			$allowable_tags = $this->get_allowable_tags( $form_id );

			if ( $allowable_tags === false ) {
				// The raw value is unsafe so escape it.
				$return = esc_html( $raw_value );
				// Run nl2br() to preserve line breaks when auto-formatting is disabled on notifications/confirmations.
				$return = nl2br( $return );
			} else {
				// The value contains HTML but the value was sanitized before saving.
				$return = wpautop( $raw_value );
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Determines if the RTE can be enabled for the current field and user.
	 *
	 * @since 2.2.5.14
	 *
	 * @return bool
	 */
	public function is_rich_edit_enabled() {
		if ( ! $this->useRichTextEditor ) {
			return false;
		}

		global $wp_rich_edit;
		$wp_rich_edit = null;

		add_filter( 'get_user_option_rich_editing', array( $this, 'filter_user_option_rich_editing' ) );
		$user_can_rich_edit = user_can_richedit();
		remove_filter( 'get_user_option_rich_editing', array( $this, 'filter_user_option_rich_editing' ) );

		return $user_can_rich_edit;
	}

	/**
	 * Filter the rich_editing option for the current user.
	 *
	 * @since 2.2.5.14
	 *
	 * @param string $value The value of the rich_editing option for the current user.
	 *
	 * @return string
	 */
	public function filter_user_option_rich_editing( $value ) {
		return 'true';
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		$operators   = parent::get_filter_operators();
		$operators[] = 'contains';

		return $operators;
	}

}

GF_Fields::register( new GF_Field_Textarea() );
