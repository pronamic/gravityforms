<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Choices_UI\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Data config items for the Choices UI.
 *
 * @since 2.6
 */
class GF_Choices_UI_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_editor' => array(
				'choices_ui' => array(
					'data' => array(),
				),
			),
		);
	}

}
