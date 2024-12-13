<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Settings I18N
 *
 * @since 2.6
 */
class GF_Settings_Config_I18N extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';


	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_settings' => array(
				'loader' => array(
					'i18n' => array(
						'loaderText' => __( 'Loading', 'gravityforms' ),
					),
				),
			),
			'field_map' => array(
				'i18n' => array(
					'add'                 => __( 'Add', 'gravityforms' ),
					'add_custom_key'      => __( 'Add Custom Key', 'gravityforms' ),
					'add_custom_value'    => __( 'Add Custom Value', 'gravityforms' ),
					'delete'              => __( 'Delete', 'gravityforms' ),
					'remove_custom_value' => __( 'Remove Custom Value', 'gravityforms' ),
					'select_a_field'      => __( 'Select a Field', 'gravityforms' ),
				),
			),
			'components' => array(
				'color_picker' => array(
					'i18n' => array(
						'apply' => __( 'Apply', 'gravityforms' ),
						'hex'   => __( 'Hex', 'gravityforms' ),
					),
				),
				'swatch' => array(
					'i18n' => array(
						'swatch' => __( 'swatch', 'gravityforms' ),
					),
				),
				'file_upload' => array(
					'i18n' => array(
						'click_to_upload' => __( 'Click to upload', 'gravityforms' ),
						'drag_n_drop'     => __( 'or drag and drop', 'gravityforms' ),
						'max'             => __( 'max.', 'gravityforms' ),
						'or'              => __( 'or', 'gravityforms' ),
						'replace'         => __( 'Replace', 'gravityforms' ),
						'delete'          => __( 'Delete', 'gravityforms' ),
					),
				),
			),
		);
	}
}
