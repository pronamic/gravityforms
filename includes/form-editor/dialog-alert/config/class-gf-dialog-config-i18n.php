<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Choices_UI\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * I18N items for the Choices UI.
 *
 * @since 2.6
 */
class GF_Dialog_Config_I18N extends GF_Config {

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
			'components' => array(
				'dialog' => array(
					'i18n' => array(
						'cancel' => esc_html__( 'Cancel', 'gravityforms' ),
						'close'  => esc_html__( 'Close', 'gravityforms' ),
						'ok'     => esc_html__( 'OK', 'gravityforms' ),
					),
				),
			),
		);
	}

}
