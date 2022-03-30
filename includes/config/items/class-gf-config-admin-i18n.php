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
class GF_Config_Admin_I18n extends GF_Config {

	protected $name               = 'gform_admin_i18n';
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
			// named sub objects that match the admin js file name (camelCased) they are localizing
			'formAdmin'   => array(
				'toggleFeedInactive' => esc_html__( 'Inactive', 'gravityforms' ),
				'toggleFeedActive'   => esc_html__( 'Active', 'gravityforms' ),
			),
			'shortcodeUi' => array(
				'editForm'   => esc_html__( 'Edit Form', 'gravityforms' ),
				'insertForm' => esc_html__( 'Insert Form', 'gravityforms' ),
			),
		);
	}
}
