<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Text extends GF_Field {

	public $type = 'text';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Single Line Text', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit a single line of text.', 'gravityforms' );
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
		return 'gform-icon--single-line-text';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'input_mask_setting',
			'maxlen_setting',
			'password_field_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Returns the placeholder attribute for the field.
	 *
	 * If no custom placeholder is set and an input mask is enabled, uses the mask as placeholder.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_field_placeholder_attribute() {
		if ( empty( $this->placeholder ) && $this->inputMask && $this->inputMaskValue ) {
			return sprintf( "placeholder='%s'", esc_attr( $this->inputMaskValue ) );
		}

		return parent::get_field_placeholder_attribute();
	}

	/**
	 * Return the result (bool) by setting $this->failed_validation.
	 * Return the validation message (string) by setting $this->validation_message.
	 *
	 * @since 2.4.11
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 */
	public function validate( $value, $form ) {

		if ( $this->maxLength && is_numeric( $this->maxLength ) ) {
			if ( GFCommon::safe_strlen( $value ) > $this->maxLength ) {
				$this->failed_validation  = true;
				$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'The text entered exceeds the maximum number of characters.', 'gravityforms' ) : $this->errorMessage;
				return;
			}
		}

		// Validate input mask if enabled and value is not empty.
		if ( $this->inputMask && $this->inputMaskValue && $value !== '' ) {
			$regex = $this->mask_to_regex( $this->inputMaskValue );
			if ( ! preg_match( $regex, $value ) ) {
				$this->failed_validation  = true;
				$this->validation_message = empty( $this->errorMessage )
					? sprintf( esc_html__( 'Required format: %s', 'gravityforms' ), $this->inputMaskValue )
					: $this->errorMessage;
			}
		}

	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$html_input_type = 'text';

		if ( $this->enablePasswordInput && ! $is_entry_detail ) {
			$html_input_type = 'password';
		}

		$id          = (int) $this->id;
		$field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$value        = esc_attr( $value );
		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;
		$class        = esc_attr( $class );

		$max_length            = is_numeric( $this->maxLength ) ? "maxlength='{$this->maxLength}'" : '';
		$tabindex              = $this->get_tabindex();
		$disabled_text         = $is_form_editor ? 'disabled="disabled"' : '';
		$placeholder_attribute = $this->get_field_placeholder_attribute();
		$required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby      = $this->get_aria_describedby();
		$autocomplete          = $this->enableAutocomplete ? $this->get_field_autocomplete_attribute() : '';
		$mask_attribute        = '';
		if ( $this->inputMask && $this->inputMaskValue && ! $this->enablePasswordInput ) {
			$mask_attribute = sprintf( 'data-mask="%s"', esc_attr( $this->inputMaskValue ) );
		}

		// For Post Tags, Use the WordPress built-in class "howto" in the form editor.
		$text_hint = '';
		if ( $this->type === 'post_tags' ) {
			$text_hint = '<p class="gfield_post_tags_hint gfield_description" id="' . $field_id . '_desc">' . gf_apply_filters( array(
					'gform_post_tags_hint',
					$form_id,
					$this->id,
				), esc_html__( 'Separate tags with commas', 'gravityforms' ), $form_id ) . '</p>';
		}

		$input = "<input name='input_{$id}' id='{$field_id}' type='{$html_input_type}' value='{$value}' class='{$class}' {$max_length} {$aria_describedby} {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text} {$autocomplete} {$mask_attribute}/>{$text_hint}";

		return sprintf( "<div class='ginput_container ginput_container_text'%s>%s</div>", self::get_text_counter_attrs( $this ), $input );
	}

	/**
	 * Returns the text counter attributes if maxLength is set.
	 *
	 * @since 2.9.20
	 *
	 * @param GF_Field $field The field object.
	 *
	 * @return string The text counter attributes or an empty string if text counter is not enabled.
	 */
	public static function get_text_counter_attrs( $field ) {

		// Abort if maxLength is not set.
		if ( ! is_numeric( $field->maxLength ) ) {
			return '';
		}

		// Translators: Do not translate {current} and {max}. They will be replaced by the actual values later.
		$default_template = __( '{current} of {max} max characters', 'gravityforms' );

		/**
		 * Filters the text counter template.
		 * The template supports the following variables: {current} and {max} and {remaining}
		 * {current} - The current number of characters entered.
		 * {max} - The maximum number of characters allowed.
		 * {remaining} - The number of characters remaining.
		 *
		 * @since 2.9.20
		 *
		 * @param string   $default_template The text counter template.
		 * @param int      $form_id          The current Form ID.
		 * @param GF_Field $field            The current Field object.
		 *
		 */
		$text_counter_template = esc_attr( gf_apply_filters( array( 'gform_text_counter_template', $field->formId, $field->id ), $default_template, $field->formId, $field ) );

		return " data-text-counter-max='{$field->maxLength}' data-text-counter-template='{$text_counter_template}'";
	}

	public function allow_html() {
		return in_array( $this->type, array( 'post_custom_field', 'post_tags' ) ) ? true : false;
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GF_Field::get_allowable_tags()
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

		if ( $format === 'html' ) {
			$value = $raw_value;
			if ( $nl2br ) {
				$value = nl2br( $value );
			}

			$form_id        = absint( $form['id'] );
			$allowable_tags = $this->get_allowable_tags( $form_id );

			if ( $allowable_tags === false ) {
				// The value is unsafe so encode the value.
				$return = esc_html( $value );
			} else {
				// The value contains HTML but the value was sanitized before saving.
				$return = $value;
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value safe for displaying on the entry list page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GF_Field::get_allowable_tags()
	 *
	 * @param string $value    The field value.
	 * @param array  $entry    The Entry Object currently being processed.
	 * @param string $field_id The field or input ID currently being processed.
	 * @param array  $columns  The properties for the columns being displayed on the entry list page.
	 * @param array  $form     The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {

		if ( is_array( $value ) ) {
			return '';
		}

		$form_id = absint( $form['id'] );
		$allowable_tags = $this->get_allowable_tags( $form_id );

		if ( $allowable_tags === false ) {
			// The value is unsafe so encode the value.
			$return = esc_html( $value );
		} else {
			// The value contains HTML but the value was sanitized before saving.
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value safe for displaying on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 1.9
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The entry.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {

		if ( is_array( $value ) ) {
			return '';
		}

		if ( $format === 'html' ) {
			$value = nl2br( (string) $value );

			$allowable_tags = $this->get_allowable_tags();

			if ( $allowable_tags === false ) {
				// The value is unsafe so encode the value.
				$return = esc_html( $value );
			} else {
				// The value contains HTML but the value was sanitized before saving.
				$return = $value;
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Add the hint text to aria-describedby.
	 *
	 * @param array $extra_ids any extra ids that should be added to the describedby attribute.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_aria_describedby( $extra_ids = array() ) {
		if ( $this->type === 'text' || $this->type === 'post_custom_field' || $this->type === 'survey' ) {
			return parent::get_aria_describedby( $extra_ids );
		}

		$id              = (int) $this->id;
		$form_id         = (int) $this->formId;
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$describedby = '';
		if ( $this->inputType === 'text' || empty( $this->inputType ) ) {
			$describedby .= "{$field_id}_desc";
		}

		if ( ! empty( $this->description ) ) {
			$describedby .= " gfield_description_{$form_id}_{$id}";
		}

		if ( $this->failed_validation ) {
			$describedby .= " validation_message_{$this->formId}_{$this->id}";
		}

		if ( ! empty( $extra_ids ) ) {
			$describedby .= implode( ' ', $extra_ids );
		}

		return empty( $describedby ) ? '' : 'aria-describedby="' . $describedby . '"';
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

GF_Fields::register( new GF_Field_Text() );
