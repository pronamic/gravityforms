<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_Post_Tags extends GF_Field {

	public $type = 'post_tags';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Tags', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit the tags for a post.', 'gravityforms' );
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
		return 'gform-icon--tags';
	}

	function get_form_editor_field_settings() {
		return array(
			'post_tag_type_setting',
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'default_value_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'placeholder_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = (int) $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$value        = esc_attr( $value );
		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;
		$class        = esc_attr( $class );

		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$tabindex              = $this->get_tabindex();
		$placeholder_attribute = $this->get_field_placeholder_attribute();
		$required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby      = $this->get_aria_describedby();

		// Use the WordPress built-in class "howto" in the form editor.
		$text_hint = '<p class="gfield_post_tags_hint gfield_description" id="' . $field_id . '_desc">' . gf_apply_filters( array(
				'gform_post_tags_hint',
				$form_id,
				$this->id,
			), esc_html__( 'Separate tags with commas', 'gravityforms' ), $form_id ) . '</p>';

		return "<div class='ginput_container ginput_container_post_tags'>
					<input name='input_{$id}' id='{$field_id}' type='text' value='{$value}' class='{$class}' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$aria_describedby} {$disabled_text}/>{$text_hint}
				</div>";
	}

	public function allow_html() {
		return true;
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
}

GF_Fields::register( new GF_Field_Post_Tags() );
