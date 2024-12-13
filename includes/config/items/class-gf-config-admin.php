<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Admin I18N
 *
 * @since 2.6
 */
class GF_Config_Admin extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Whether we should enqueue this data.
	 *
	 * @since 2.6
	 *
	 * @return bool|mixed
	 */
	public function should_enqueue() {
		return is_admin();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'data' => array(
				'is_block_editor' => \GFCommon::is_block_editor_page(),
			),
			'i18n' => array(
				'form_admin'   => array(
					'toggle_feed_inactive' => esc_html__( 'Inactive', 'gravityforms' ),
					'toggle_feed_active'   => esc_html__( 'Active', 'gravityforms' ),
				),
				'shortcode_ui' => array(
					'edit_form'   => esc_html__( 'Edit Form', 'gravityforms' ),
					'insert_form' => esc_html__( 'Insert Form', 'gravityforms' ),
				),
			),
		);
	}
}
