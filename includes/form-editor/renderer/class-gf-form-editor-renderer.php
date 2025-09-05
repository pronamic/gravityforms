<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Renderer;

class GF_Form_Editor_Renderer {

	/**
	 * Generates the form editor markup by calling the forms_page which runs on page load.
	 *
	 * @since 2.6
	 *
	 * @param string        $form_id     The ID of the form to generate form editor markup for.
	 * @param \GFFormDetail $form_detail An instance of the FormDetail class
	 * @param boolean       $echo        Whether to echo the form contents or not. Default false.
	 *
	 * @return string
	 */
	public static function render_form_editor( $form_id, $form_detail, $echo = false ) {
		\ob_start();
		$form_detail::forms_page( $form_id );
		$editor = \ob_get_clean();

		if ( $echo ) {
			echo $editor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return \mb_convert_encoding( $editor, 'UTF-8', 'ISO-8859-1' );
	}
}
