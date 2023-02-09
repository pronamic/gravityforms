<?php

namespace Gravity_Forms\Gravity_Forms\Honeypot\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFForms;

/**
 * Config items for the Honeypot Field
 *
 * @since 2.6
 */
class GF_Honeypot_Config extends GF_Config {

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
					'honeypot' => array(
						'version_hash' => wp_hash( GFForms::$version ),
					),
				),
			),
		);
	}
}
