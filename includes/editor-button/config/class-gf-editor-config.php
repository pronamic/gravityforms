<?php

namespace Gravity_Forms\Gravity_Forms\Editor_Button\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Editor_Button\GF_Editor_Service_Provider;
use Gravity_Forms\Gravity_Forms\Editor_Button\Endpoints\GF_Editor_Save_Editor_Settings;

/**
 * Config items for the Editor Settings Button
 *
 * @since 2.8
 */
class GF_Editor_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.8
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFCommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'editor_button' => array(
					'i18n' => array(
						'title'   	   	       => esc_html__( 'Editor Preferences', 'gravityforms' ),
						'closeButtonAriaLabel' => esc_html__( 'Close button', 'gravityforms' ),
						'description'   	   => esc_html__( 'Change options related to the form editor.', 'gravityforms' ),
						'compactToggleLabel'   => esc_html__( 'Compact View', 'gravityforms' ),
						'compactToggleText'    => esc_html__( 'Simplify the preview of form fields for a more streamlined editing experience.', 'gravityforms' ),
						'idToggleLabel' 	   => esc_html__( 'Show Field IDs', 'gravityforms' ),
						'idToggleText'         => esc_html__( 'Show the ID of each field in Compact View.', 'gravityforms' ),
					),
					'endpoints' => $this->get_endpoints(),
					'compactViewEnabled' => GF_Editor_Service_Provider::is_compact_view_enabled( get_current_user_id(), rgget( 'id' ) ),
					'fieldIdEnabled' => GF_Editor_Service_Provider::is_field_id_enabled( get_current_user_id(), rgget( 'id' ) ),
					'form' => rgget( 'id' ),
				),
				'dropdown_menu' => array(
					'i18n' => array(
						'duplicateButtonLabel' => esc_html__( 'Duplicate', 'gravityforms' ),
						'deleteButtonLabel'    => esc_html__( 'Delete', 'gravityforms' ),
						'dropdownButtonLabel'  => esc_html__( 'Dropdown menu button', 'gravityforms' ),
					),
				),
			),
		);
	}

	/**
	 * Gets the endpoints for saving the compact view settings.
	 *
	 * @since 2.8
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'save_editor_settings' => array(
				'action' => array(
					'value'   => GF_Editor_Save_Editor_Settings::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Editor_Save_Editor_Settings::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}
}
