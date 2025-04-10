<?php

namespace Gravity_Forms\Gravity_Forms\Form_Switcher\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFForms;

/**
 * Config items for Form_Switcher.
 *
 * @since 2.9.6
 */
class GF_Form_Switcher_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Only enqueue in the admin.
	 *
	 * @since 2.9.6
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return GFForms::is_gravity_page();
	}

	/**
	 * Config data.
	 *
	 * @since 2.9.6
	 *
	 * @return array[]
	 */
	public function data() {
		return [
			'components' => [
				'form_switcher' => [
					'endpoints' => $this->get_endpoints(),
				],
			],
		];
	}

	/**
	 * Get the endpoints for the Form Switcher.
	 *
	 * @since 2.9.6
	 *
	 * @return array
	 */
	public function get_endpoints() {
		return [
			'get_forms' => [
				'action' => [
					'value'   => 'gf_form_switcher_get_forms',
					'default' => 'mock_endpoint',
				],
				'nonce' => [
					'value'   => wp_create_nonce( 'gf_form_switcher_get_forms' ),
					'default' => 'nonce',
				],
			],

		];
	}

}
