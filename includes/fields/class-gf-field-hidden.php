<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Hidden extends GF_Field {

	public $type = 'hidden';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 3.0
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	public function get_form_editor_field_title() {
		return esc_attr__( 'Hidden', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Stores information that should not be visible to the user but can be processed and saved with the user submission.', 'gravityforms' );
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
		return 'gform-icon--hidden';
	}

	public function is_conditional_logic_supported(){
		return true;
	}

	function get_form_editor_field_settings() {
		return array(
			'prepopulate_field_setting',
			'label_setting',
			'default_value_setting',
		);
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = (int) $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$field_type         = $is_entry_detail || $is_form_editor ? 'text' : 'hidden';
		$class_attribute    = $is_entry_detail || $is_form_editor ? '' : "class='gform_hidden'";
		$required_attribute = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

		$input = sprintf( "<input name='input_%d' id='%s' type='$field_type' {$class_attribute} {$required_attribute} {$invalid_attribute} value='%s' %s/>", $id, $field_id, esc_attr( $value ), $disabled_text );

		return sprintf( "<div class='ginput_container ginput_container_text'>%s</div>", $input );
	}

	/**
	 * Returns the field markup.
	 *
	 * @since 1.9
	 * @since 3.0 Using the parent method on validation failure, so the error message will be displayed.
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		if ( $this->failed_validation ) {
			return parent::get_field_content( $value, $force_frontend_label, $form );
		}

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		if ( ! $is_admin ) {
			return '{FIELD}';
		}

		$admin_buttons = $this->get_admin_buttons();
		$form_id       = absint( rgar( $form, 'id' ) );
		$field_id      = $form_id === 0 ? "input_{$this->id}" : 'input_' . $form_id . "_{$this->id}";
		$field_label   = $this->get_field_label( $force_frontend_label, $value );

		return sprintf( "%s<label class='gfield_label gform-field-label' for='%s'>%s</label>{FIELD}", $admin_buttons, $field_id, esc_html( $field_label ) );
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

	/**
	 * Returns the validation message to be applied when the field has failed state validation.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_state_validation_message() {
		return esc_html__( 'The value of this hidden field has been reset to default because the submitted value does not match the expected value.', 'gravityforms' );
	}

}

GF_Fields::register( new GF_Field_Hidden() );
