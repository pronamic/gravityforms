<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Theme Legacy Checks.
 *
 * @since 2.6
 */
class GF_Config_Legacy_Check extends GF_Config {

	protected $name               = 'gf_legacy';
	protected $script_to_localize = 'gform_layout_editor';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$form = \RGFormsModel::get_form_meta( rgget( 'id' ) );

		return array(
			'is_legacy' => array(
				'value'   => \GFCommon::is_legacy_markup_enabled( $form ),
				'default' => 0,
			),
		);
	}
}