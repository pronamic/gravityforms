<?php

namespace Gravity_Forms\Gravity_Forms\Logging\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for client side logger
 *
 * @since 2.9.26
 */
class GF_Logging_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'common' => array(
				'form' => array(
					'logging' => array(
						'is_enabled' => \GFLogging::is_enabled( 'gravityforms-browser' ),
					),
				),
			),
		);
	}
}
