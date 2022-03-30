<?php

namespace Gravity_Forms\Gravity_Forms\Merge_Tags\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Merge Tags I18N
 *
 * @since 2.6
 */
class GF_Merge_Tags_Config_I18N extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'merge_tags' => array(
					'i18n' => array(
						'insert_merge_tags' => __( 'Insert Merge Tags', 'gravityforms' ),
						'search_merge_tags' => __( 'Search Merge Tags', 'gravityforms' ),
					),
				),
			),
		);
	}
}
