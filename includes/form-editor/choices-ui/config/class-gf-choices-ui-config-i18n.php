<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Choices_UI\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * I18N items for the Choices UI.
 *
 * @since 2.6
 */
class GF_Choices_UI_Config_I18N extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';


	public function should_enqueue() {
		return \GFCommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_editor' => array(
				'choices_ui' => array(
					'i18n' => array(
						'description'     => __( 'Define the choices for this field. If the field type supports it you will also be able to select the default choice(s) to the left of the choice.', 'gravityforms' ),
						'title'           => __( 'Choices', 'gravityforms' ),
						'expandableTitle' => __( 'Expand the Choices window', 'gravityforms' ),
					),
				),
			),
		);
	}

}
