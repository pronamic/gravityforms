<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Fields\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

class GF_Form_Editor_Fields_Config extends GF_Config {

	protected $name = 'gform_admin_config';

	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	public function data() {
		$field_groups = \GFFormDetail::get_field_groups();
		$field_types = array();
		foreach( $field_groups as $group => $data ) {
			$field_types = array_merge( $field_types, $data['fields'] );
		}
		return array(
			'form_editor_fields' => array(
				'data' => array(
					'field_types' => $field_types,
					'repeater_empty_wrapper' => \GF_Field_Repeater::get_empty_wrapper(),
				),
				'endpoints' => array(
					'rg_add_field' => array(
						'action' => 'rg_add_field',
						'nonce' => wp_create_nonce( 'rg_add_field' ),
					),
				)
			),
		);
	}

}
