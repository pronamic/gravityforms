<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for the Block Editor.
 *
 * @since 2.6
 */
class GF_Config_Block_Editor extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$is_editor = function_exists( 'get_current_screen' ) && null !== get_current_screen() ? get_current_screen()->is_block_editor() : false;

		return array(
			'block_editor' => array(
				'data' => array(
					'is_block_editor' => $is_editor
				),
				'i18n' => array(
					'insert_gform_block_title'   => __( 'Add Block To Page', 'gravityforms' ),
					'insert_gform_block_content' => __( 'Click or drag the Gravity Forms Block into the page to insert the form you selected. %1$sLearn More.%2$s', 'gravityforms' ),
				),
				'urls' => array(
					'block_docs' => 'https://docs.gravityforms.com/gravity-forms-gutenberg-block/',
				),
			)
		);
	}
}
