<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Submit extends GF_Field {

	public $type = 'submit';

	public $position = 'last';

	public function __construct( $data = array() ) {
		add_filter( 'gform_pre_render', array( $this, 'inject_inline_button' ), 100 );

		parent::__construct( $data );
	}

	/**
	 * Returns the field title.
	 *
	 * The submit button editor field title is declared in gf_vars['button'] instead of here.
	 *
	 * @since 2.6
	 */
	public function get_form_editor_field_title() {
		return;
	}

	/**
	 * Returns the field description.
	 *
	 * The submit button editor field description is declared in gf_vars['buttonDescription'] instead of here.
	 *
	 * @since 2.6
	 */
	public function get_form_editor_field_description() {
		return;
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--smart-button';
	}

	/**
	 * Returns the class names of the settings which should be available on the field in the form editor.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_submit_setting',
			'submit_text_setting',
			'submit_type_setting',
			'submit_image_setting',
			'submit_width_setting',
			'submit_location_setting',
		);
	}

	/**
	 * Returns the field's form editor button.
	 *
	 * This field is automatically added to the form, so it doesn't have a button.
	 *
	 * @since 2.6
	 */
	public function get_form_editor_button() {
		return;
	}

	/**
	 * This field supports conditional logic.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Return the result (bool) by setting $this->failed_validation.
	 * Return the validation message (string) by setting $this->validation_message.
	 *
	 * @since 2.4.11
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 */
	public function validate( $value, $form ) {

		return true;

	}

	/**
	 * Decides if the field markup should not be reloaded after AJAX save.
	 *
	 * @since 2.6
	 *
	 * @return boolean
	 */
	public function disable_ajax_reload() {
		return true;
	}

	/**
	 * Returns the HTML markup for the field's containing element.
	 *
	 * @since 2.6
	 *
	 * @param array $atts Container attributes.
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container( $atts, $form ) {

		// Add a data attribute to the container div so that we can target it in the layout editor.
		$atts['data-field-class']    = 'gform_editor_submit_container';
		$atts['data-field-position'] = rgar( $form['button'], 'location' ) ? $form['button']['location'] : 'bottom';
		$atts['id']                  = 'field_submit';

		return parent::get_field_container( $atts, $form );

	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since 2.6
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id        = absint( $form['id'] );
		$is_form_editor = $this->is_form_editor();

		$class        = esc_attr( 'gform-button gform-button--white ' );
		$default_text = __( 'Submit', 'gravityforms' );
		$button       = rgar( $form, 'button', array( 'type' => 'link' ) );

		$inline = rgar( $form['button'], 'location', 'bottom' );

		// If we're in the editor or the button is inline, display the button.  Otherwise, the button will be added to the footer in form_display.php.
		if ( $is_form_editor || 'inline' == $inline ) {
			$submit = GFFormDisplay::get_form_button( $form_id, "gform_submit_button_{$form_id}", $button, $default_text, $class, $default_text, 0 );
			return gf_apply_filters( array( 'gform_submit_button', $form_id ), $submit, $form );
		}
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @since 2.6
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {

		$admin_buttons = $this->get_admin_buttons();

		$admin_hidden_markup = ( $this->visibility == 'hidden' ) ? $this->get_hidden_admin_markup() : '';

		$field_content = sprintf( "%s%s{FIELD}", $admin_buttons, $admin_hidden_markup );

		return $field_content;
	}

	/**
	 * Add the submit button as a field if it is inline. Target of the gform_pre_render filter.
	 *
	 * @since 2.6
	 *
	 * @param array $form The form object.
	 *
	 * @return array Returns the new form object
	 */
	public function inject_inline_button( $form ) {

		if ( empty( $form ) || rgars( $form, 'button/location' ) !== 'inline' || $this->is_form_editor() || $this->is_entry_detail_edit() ) {
			return $form;
		}

		$is_injected = (bool) GFFormsModel::get_fields_by_type( $form, array( $this->type ) );
		if ( ! $is_injected ) {
			$form['fields'][] = $this;
		}

		return $form;
	}

	/**
	 * Get the appropriate CSS Grid class for the column span of the field.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_css_grid_class( $form = '' ) {
		$span = rgar( $form['button'], 'layoutGridColumnSpan', '12' );
		switch ( $span ) {
			case 12:
				$class = 'gfield--width-full';
				break;
			case 11:
				$class = 'gfield--width-eleven-twelfths';
				break;
			case 10:
				$class = 'gfield--width-five-sixths';
				break;
			case 9:
				$class = 'gfield--width-three-quarter';
				break;
			case 8:
				$class = 'gfield--width-two-thirds';
				break;
			case 7:
				$class = 'gfield--width-seven-twelfths';
				break;
			case 6:
				$class = 'gfield--width-half';
				break;
			case 5:
				$class = 'gfield--width-five-twelfths';
				break;
			case 4:
				$class = 'gfield--width-third';
				break;
			case 3:
				$class = 'gfield--width-quarter';
				break;
			case 2:
				$class = 'gfield--width-one-sixth';
				break;
			case 1:
				$class = 'gfield--width-one-twelfth';
				break;
			default:
				$class = '';
				break;
		}

		return $class;
	}


}

GF_Fields::register( new GF_Field_Submit() );
