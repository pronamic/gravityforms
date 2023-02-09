<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles\Views;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Text;
use Gravity_Forms\Gravity_Forms\Theme_Layers\API\View;
use \GFFormDisplay;

class Confirmation_View extends Form_View {

	protected $string_search = "class='gform_confirmation_wrapper";
	protected $retain_original = true;

	public function get_markup( $content, $form, $value, $lead_id, $form_id ) {
		$content = $this->add_wrapper_class( $content, $form );
		$content = $this->add_form_theme_data_attr( $content, $form );
		return $content;
	}

	protected function add_wrapper_class( $content, $form ) {
		$theme_slug = GFFormDisplay::get_form_theme_slug( $form );
		$classes    = '';

		switch ( $theme_slug ) {
			case 'orbital':
				$classes = 'gform_confirmation_wrapper gform_wrapper gform-theme gform-theme--foundation gform-theme--framework gform-theme--' . $theme_slug;
				break;
			case 'gravity-theme':
			default:
				$classes = 'gform_confirmation_wrapper gravity-theme gform-theme--no-framework';
				break;
			case 'legacy':
				$classes = 'gform_confirmation_wrapper gform_legacy_markup_wrapper gform_legacy_confirmation_markup_wrapper gform-theme--no-framework';
				break;
		}
		$classes = sprintf( "class='%s", $classes );

		return str_replace( $this->string_search, $classes, $content );
	}

	/**
	 * Add the form theme data attribute to the confirmation wrapper class.
	 *
	 * @since 2.7
	 *
	 * @param $content
	 * @param $form
	 *
	 * @return array|string|string[]
	 */
	protected function add_form_theme_data_attr( $content, $form ) {
		$theme_slug = GFFormDisplay::get_form_theme_slug( $form );
		$theme_attr = sprintf( "data-form-theme='%s' class='gform_confirmation_wrapper", $theme_slug );

		return str_replace( $this->string_search, $theme_attr, $content );
	}

}
