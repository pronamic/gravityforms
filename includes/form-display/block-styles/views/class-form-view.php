<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles\Views;

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\View;
use \GFFormDisplay;

class Form_View extends View {

	protected $string_search = ' gform_wrapper';

	public function should_override( $form, $form_id, $block_settings = array() ) {
		return true;
	}

	public function get_markup( $content, $form, $value, $lead_id, $form_id ) {
		$content = $this->add_wrapper_class( $content, $form );
		return $content;
	}

	protected function add_wrapper_class( $content, $form ) {
		$theme_slug = GFFormDisplay::get_form_theme_slug( $form );
		$classes    = '';

		switch ( $theme_slug ) {
			case 'orbital':
				$classes = ' gform_wrapper gform-theme gform-theme--foundation gform-theme--framework gform-theme--' . $theme_slug;
				break;
			case 'gravity-theme':
			default:
				$classes = ' gform_wrapper gravity-theme gform-theme--no-framework';
				break;
			case 'legacy':
				$classes = ' gform_wrapper gform_legacy_markup_wrapper gform-theme--no-framework';
				break;
		}

		return str_replace( $this->string_search, $classes, $content );
	}

}
