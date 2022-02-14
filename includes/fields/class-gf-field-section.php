<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_Section extends GF_Field {

	public $type = 'section';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Section', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Adds a content separator to your form to help organize groups of fields. This is a visual element and does not collect any data.', 'gravityforms' );
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
		return 'gform-icon--section';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'label_setting',
			'description_setting',
			'visibility_setting',
			'css_class_setting',
		);
	}

	/**
	 * Retrieve the field label.
	 *
	 * @since 2.5.2   Don't force this field to have a label.
	 *
	 * @param bool   $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param string $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 *
	 * @return string
	 */
	public function get_field_label( $force_frontend_label, $value ) {
		$label = $force_frontend_label ? $this->label : GFCommon::get_label( $this );

		return $label;
	}

	public function get_field_content( $value, $force_frontend_label, $form ) {

		$field_label = $this->get_field_label( $force_frontend_label, $value );

		$admin_buttons = $this->get_admin_buttons();

		$admin_hidden_markup = ( $this->visibility == 'hidden' ) ? $this->get_hidden_admin_markup() : '';

		$description = $this->get_description( $this->description, 'gsection_description' );
		$tag         = GFCommon::is_legacy_markup_enabled( $form ) ? 'h2' : 'h3';
		/* translators: 1. Admin buttons markup 2. Heading tag 3. The field label 4. The description */
		$field_content = sprintf( '%1$s%2$s<%3$s class="gsection_title">%4$s</%3$s>%5$s', $admin_buttons, $admin_hidden_markup, $tag, esc_html( $field_label ), $description );

		return $field_content;
	}

	/**
	 * Actions to be performed once the field has been converted to an object.
	 *
	 * @since 2.5
	 */
	public function post_convert_field() {
		parent::post_convert_field();

		// Section fields are not currently supported in columns.
		unset( $this->layoutGridColumnSpan );
	}

}

GF_Fields::register( new GF_Field_Section() );