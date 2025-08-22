<?php

use Gravity_Forms\Gravity_Forms\Honeypot;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * The honeypot field used to capture spam.
 *
 * @since 2.9.16
 */
class GF_Field_Honeypot extends GF_Field {

	/**
	 * The field type.
	 *
	 * @since 2.9.16
	 *
	 * @var string
	 */
	public $type = 'honeypot';

	/**
	 * Prevent the field type button appearing in the form editor.
	 *
	 * @since 2.9.16
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array();
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since 2.9.16
	 *
	 * @param array      $form  The form the field is to be output for.
	 * @param string     $value The field value.
	 * @param null|array $entry Null or the current entry.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		/** @var Honeypot\GF_Honeypot_Handler $handler */
		$handler = GFForms::get_service_container()->get( Honeypot\GF_Honeypot_Service_Provider::GF_HONEYPOT_HANDLER );

		return sprintf(
			"<div class='ginput_container'><input name='%s' id='input_%d_%d' type='text' value='' autocomplete='new-password'/></div>",
			esc_attr( $handler->get_input_name( $form, $this->id ) ),
			absint( rgar( $form, 'id', $this->formId ) ),
			absint( $this->id )
		);
	}

}

GF_Fields::register( new GF_Field_Honeypot() );
