<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Select extends GF_Field {

	public $type = 'select';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	public function get_form_editor_field_title() {
		return esc_attr__( 'Drop Down', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to select one option from a list.', 'gravityforms' );
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
		return 'gform-icon--dropdown';
	}


	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'enable_enhanced_ui_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'choices_setting',
			'rules_setting',
			'placeholder_setting',
			'default_value_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Indicates if state validation should be skipped if the submitted value is blank.
	 *
	 * Value will be blank when the placeholder is selected.
	 *
	 * @since 3.0
	 *
	 * @param string|int $key The field or input ID.
	 *
	 * @return bool
	 */
	public function skip_state_validation_if_blank( $key ) {
		return ! rgblank( $this->placeholder );
	}

	/**
	 * Prepares the value that will be hashed on form display as part of the state.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The default value.
	 *
	 * @return null|array
	 */
	public function get_values_for_state_hash( $value ) {
		$id = $this->id;

		return array(
			$id => $this->get_choices_for_state_hash(),
		);
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size                   = $this->size;
		$class_suffix           = $is_entry_detail ? '_admin' : '';
		$class                  = $size . $class_suffix;
		$css_class              = trim( esc_attr( $class ) . ' gfield_select' );
		$tabindex               = $this->get_tabindex();
		$disabled_text          = $is_form_editor ? 'disabled="disabled"' : '';
		$required_attribute     = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$describedby_attribute = $this->get_aria_describedby();
		$autocomplete_attribute = $this->enableAutocomplete ? $this->get_field_autocomplete_attribute() : '';

		return sprintf( "<div class='ginput_container ginput_container_select'><select name='input_%d' id='%s' class='%s' $tabindex $describedby_attribute %s %s %s %s>%s</select></div>", $id, $field_id, $css_class, $disabled_text, $required_attribute, $invalid_attribute, $autocomplete_attribute, $this->get_choices( $value ) );

	}

	public function get_choices( $value ) {
		return GFCommon::get_select_choices( $this, $value );
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return esc_html( $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ) ) );
	}


	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::to_money()
	 * @uses GFCommon::format_post_category()
	 * @uses GFFormsModel::is_field_hidden()
	 * @uses GFFormsModel::get_choice_text()
	 * @uses GFCommon::format_variable_value()
	 * @uses GFCommon::implode_non_blank()
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
		$modifiers       = $this->get_modifiers();
		$use_value       = in_array( 'value', $modifiers );
		$format_currency = ! $use_value && in_array( 'currency', $modifiers );
		$use_price       = $format_currency || ( ! $use_value && in_array( 'price', $modifiers ) );

		if ( is_array( $raw_value ) && (string) intval( $input_id ) != $input_id ) {
			$items = array( $input_id => $value ); // Float input Ids. (i.e. 4.1 ). Used when targeting specific checkbox items.
		} elseif ( is_array( $raw_value ) ) {
			$items = $raw_value;
		} else {
			$items = array( $input_id => $raw_value );
		}

		$ary = array();

		foreach ( $items as $input_id => $item ) {
			if ( $use_value ) {
				list( $val, $price ) = rgexplode( '|', $item, 2, true );
			} elseif ( $use_price ) {
				list( $name, $val ) = rgexplode( '|', $item, 2, true );
				if ( $format_currency ) {
					$val = GFCommon::to_money( $val, rgar( $entry, 'currency' ) );
				}
			} elseif ( $this->type == 'post_category' ) {
				$use_id     = strtolower( $modifier ) == 'id';
				$item_value = GFCommon::format_post_category( $item, $use_id );

				$val = RGFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : $item_value;
			} else {
				$val = RGFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : RGFormsModel::get_choice_text( $this, $raw_value, $input_id );
			}

			$ary[] = GFCommon::format_variable_value( $val, $url_encode, $esc_html, $format );
		}

		return GFCommon::implode_non_blank( ', ', $ary );
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
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
		if ( $this->type === 'post_category' ) {
			$value = GFCommon::prepare_post_category_value( $value, $this, 'entry_detail' );
		}

		return esc_html( $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ), $use_text ) );
	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );

		return $is_csv ? $value : GFCommon::selection_display( $value, $this, rgar( $entry, 'currency' ), $use_text );
	}

	/**
	 * Strips all tags from the input value.
	 *
	 * @since 1.9
	 * @since 2.9.18 Added check for state validation.
	 *
	 * @param string $value The field value to be processed.
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return string
	 */
	public function sanitize_entry_value( $value, $form_id ) {
		if ( $this->is_state_validation_supported() ) {
			return parent::sanitize_entry_value( $value, $form_id );
		}

		$sanitized = wp_strip_all_tags( $value );
		$this->post_entry_value_sanitization( $value, $sanitized, 'wp_strip_all_tags' );

		return $sanitized;
	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value          The value to be saved.
	 * @param array  $form           The Form object currently being processed.
	 * @param string $input_name     The input name used when accessing the $_POST.
	 * @param int    $entry_id       The ID of the entry currently being processed.
	 * @param array  $entry          The entry currently being processed.
	 * @param string $repeater_index The repeater index if the field is inside a repeater.
	 *
	 * @return array|string The sanitized and formatted input value to be saved.
	 */
	public function get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index = '' ) {
		$value = parent::get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index );

		return $this->clear_blank_price_value( $value );
	}

	/**
	 * Forces settings into expected values while saving the form object.
	 *
	 * @since 2.9.16
	 * @access public
	 *
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();
		$this->enableEnhancedUI = (bool) $this->enableEnhancedUI;
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
		$operators = $this->type == 'product' ? array( 'is' ) : array( 'is', 'isnot', '>', '<' );

		return $operators;
	}

}

GF_Fields::register( new GF_Field_Select() );
