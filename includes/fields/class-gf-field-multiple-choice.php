<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Multiple_Choice extends GF_Field {

	public $type = 'multi_choice';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Multiple Choice', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allow users to choose from a list of options.', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--choice';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'choices_setting',
			'rules_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'select_all_text_setting',
			'choice_min_max_setting',
			'horizontal_vertical_setting',
		);
	}

	/**
	 * Generate the "select all" choice markup for the choice field.
	 *
	 * @since 2.9.0
	 *
	 * @param $value
	 * @param $tabindex
	 * @param $selected_choices_count
	 * @return string The select all choice markup.
	 */
	public function get_choice_field_select_all_markup( $value, $tabindex, $selected_choices_count ) {
		if ( isset( $this->choiceLimit ) && $this->choiceLimit !== 'unlimited' && $this->choiceLimit !== 'range' ) {
			return '';
		}
		$select_label  = isset( $this->selectAllText ) ? esc_html( $this->selectAllText ) : esc_html__( 'Select All', 'gravityforms' );
		$disabled_text = $this->is_form_editor() ? 'disabled="disabled"' : '';

		// Prepare choice ID.
		$id = 'choice_' . $this->id . '_select_all';

		// Determine if all checkboxes are selected.
		if ( $selected_choices_count === count( $this->choices ) ) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}

		$checkbox = new GF_Field_Checkbox( $this );
		$aria_describedby = $checkbox->get_choice_aria_describedby( $this->formId );

		// Prepare choice markup.
		$choice_markup = "<div class='gchoice gchoice_select_all'>
						<input class='gfield-choice-input gfield_choice_all_toggle' type='checkbox' id='{$id}' {$tabindex} {$aria_describedby} onclick='gformToggleCheckboxes( this )' onkeypress='gformToggleCheckboxes( this )'{$checked} {$disabled_text} />
						<label for='{$id}' id='label_" . $this->id . "_select_all' class='gform-field-label gform-field-label--type-inline' data-label-select='{$select_label}''>{$select_label}</label>
					</div>";

		/**
		 * Override the default choice markup used when rendering radio button, checkbox and drop down type fields.
		 *
		 * @since 1.9.6
		 *
		 * @param string $choice_markup The string containing the choice markup to be filtered.
		 * @param array  $choice        An associative array containing the choice properties.
		 * @param object $field         The field currently being processed.
		 * @param string $value         The value to be selected if the field is being populated.
		 */
		$select_all = gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, array(), $this, $value );

		return $select_all;
	}

	/**
	 * Get the choice alignment for the given field.
	 *
	 * @since 2.9.0
	 *
	 * @param object $field The field object.
	 * @return string
	 */
	public static function get_field_choice_alignment( $field ) {

		return rgempty( 'choiceAlignment', $field ) ? self::get_default_choice_alignment( $field ) : $field->choiceAlignment;
	}

	/**
	 * Get the default choice alignment for the multi_choice field.
	 *
	 * @since 2.9.0
	 *
	 * @param object $field The field object.
	 * @return string
	 */
	public static function get_default_choice_alignment( $field) {
		/*
		 * Filter the default choice alignment.  Default is vertical.  Options are 'vertical' and 'horizontal'.
		 *
		 * @since 2.9.0
		 *
		 * @param string $default_choice_alignment The default choice alignment.
		 * @param object $field                    The field.
		 *
		 * @return string
		 */
		return gf_apply_filters( array( 'gform_default_choice_alignment', $field->formId ), 'vertical', $field );
	}

	public function get_form_editor_inline_script_on_page_render() {
		$alignment = self::get_default_choice_alignment( $this );
		return "gform.addAction( 'gform_post_load_field_settings', function( [ field, form ] ) { if( '" . $alignment . "' == 'horizontal' ) { jQuery('#choice_alignment_horizontal').prop('checked', true); } else { jQuery('#choice_alignment_vertical').prop('checked', true); } } );";
	}

}

GF_Fields::register( new GF_Field_Multiple_Choice() );
