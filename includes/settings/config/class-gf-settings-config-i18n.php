<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Settings I18N
 *
 * @since 2.6
 */
class GF_Settings_Config_I18N extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_settings' => array(
				'loader' => array(
					'i18n' => array(
						'loaderText' => __( 'Loading', 'gravityforms' ),
					),
				),
			),
		);
	}
}
