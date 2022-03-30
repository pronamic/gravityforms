<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Multi Legacy Check (mostly just data from a filter).
 *
 * @since 2.6
 */
class GF_Config_Legacy_Check_Multi extends GF_Config {

	protected $name               = 'gf_legacy_multi';
	protected $script_to_localize = 'gform_gravityforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		/**
		 * Allows users to filter the legacy checks for any form on the page.
		 *
		 * @since 2.5
		 *
		 * @param array
		 */
		return apply_filters( 'gform_gf_legacy_multi', array() );
	}
}