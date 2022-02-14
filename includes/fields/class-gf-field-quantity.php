<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Quantity extends GF_Field {

	public $type = 'quantity';

	function get_form_editor_field_settings() {
		return array(
			'product_field_setting',
			'quantity_field_type_setting',
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
		return esc_attr__( 'Quantity', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows a quantity to be specified for product field.', 'gravityforms' );
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
		return 'gform-icon--quantity';
	}

}

GF_Fields::register( new GF_Field_Quantity() );