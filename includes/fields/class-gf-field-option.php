<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Option extends GF_Field {

	public $type = 'option';

	function get_form_editor_field_settings() {
		return array(
			'product_field_setting',
			'option_field_type_setting',
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'label_setting',
			'admin_label_setting',
			'label_placement_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Option', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to select options for products created by a product field.', 'gravityforms' );
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
		return 'gform-icon--misc';
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

}

GF_Fields::register( new GF_Field_Option() );