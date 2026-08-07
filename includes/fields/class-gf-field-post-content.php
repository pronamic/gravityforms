<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

require_once( plugin_dir_path( __FILE__ ) . 'class-gf-field-textarea.php' );

class GF_Field_Post_Content extends GF_Field_Textarea {

	public $type = 'post_content';

	/**
	 * Whether there can be more than one of this field type per form.
	 *
	 * @since 3.0
	 *
	 * @var bool
	 */
	public $duplicatable = false;

	/**
	 * Whether the field can be used in a repeater.
	 *
	 * @since 3.0
	 *
	 * @var bool
	 */
	public $repeatable = false;

	public function get_form_editor_field_title() {
		return esc_attr__( 'Body', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit the body content for a post.', 'gravityforms' );
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
		return 'gform-icon--body';
	}

	function get_form_editor_field_settings() {
		return array(
			'post_content_template_setting',
			'post_status_setting',
			'post_category_setting',
			'post_author_setting',
			'post_format_setting',
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'textarea_height_setting',
			'maxlen_setting',
			'rules_setting',
			'visibility_setting',
			'default_value_textarea_setting',
			'placeholder_textarea_setting',
			'description_setting',
			'css_class_setting',
			'rich_text_editor_setting',
		);
	}

	public function allow_html() {
		return true;
	}

	/**
	 * Format the entry value for display on the entries list page.
	 *
	 * @since 3.0.0
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The Entry Object currently being processed.
	 * @param string       $field_id The field or input ID currently being processed.
	 * @param array        $columns  The properties for the columns being displayed on the entry list page.
	 * @param array        $form     The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		if ( is_array( $value ) ) {
			return '';
		}

		return esc_html( $value );
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
		return is_user_logged_in() ? $value : 'true';
	}

}

GF_Fields::register( new GF_Field_Post_Content() );
