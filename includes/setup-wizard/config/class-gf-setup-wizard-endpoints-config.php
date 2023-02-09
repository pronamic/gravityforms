<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Create_With_Block;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Get_Posts;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Save_Prefs;
use Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Validate_License;

/**
 * Config items for the Embed Forms REST Endpoints.
 *
 * @since 2.6
 */
class GF_Setup_Wizard_Endpoints_Config extends GF_Config {

	protected $script_to_localize = 'gform_gravityforms_admin_vendors';
	protected $name               = 'gform_admin_config';
	protected $overwrite          = false;

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.6.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFForms::is_gravity_page();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'setup_wizard' => array(
					'endpoints' => $this->get_endpoints(),
				),
			),
		);
	}

	/**
	 * Get the various endpoints for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_endpoints() {
		return array(

			// Endpoint to validate a license value.
			'validate_license' => array(
				'action' => array(
					'value'   => GF_Setup_Wizard_Endpoint_Validate_License::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Setup_Wizard_Endpoint_Validate_License::ACTION_NAME ),
					'default' => 'nonce',
				),
			),

			// Endpoint to save a series of preferences from the Wizard.
			'save_prefs'       => array(
				'action' => array(
					'value'   => GF_Setup_Wizard_Endpoint_Save_Prefs::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Setup_Wizard_Endpoint_Save_Prefs::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}

}
