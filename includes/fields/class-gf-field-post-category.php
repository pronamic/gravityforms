<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_Post_Category extends GF_Field {

	public $type = 'post_category';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Category', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows the user to select a category for the post they are creating.', 'gravityforms' );
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
		return 'gform-icon--category';
	}

	public function get_form_editor_field_settings() {
		return array(
			'post_category_checkbox_setting',
			'post_category_initial_item_setting',
			'post_category_field_type_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'conditional_logic_field_setting',
		);
	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object. Formats category IDs to include the category name.
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
	 * @return string The sanitized and formatted input value in the format "Category Name:ID".
	 */
	public function get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index = '' ) {
		$value = parent::get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index );

		$full_values = array();

		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		foreach ( $value as $cat_id ) {
			$cat           = get_term( $cat_id, 'category' );
			$full_values[] = ! is_wp_error( $cat ) && is_object( $cat ) ? $cat->name . ':' . $cat_id : '';
		}

		$value = implode( ',', $full_values );

		return $value;
	}
}

GF_Fields::register( new GF_Field_Post_Category() );
