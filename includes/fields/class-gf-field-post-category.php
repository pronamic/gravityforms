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

	function get_form_editor_field_settings() {
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

}

GF_Fields::register( new GF_Field_Post_Category() );