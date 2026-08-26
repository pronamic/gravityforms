<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines system abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_System {

	/**
	 * Get system ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/system-info',
				'args' => array(
					'label'            => __( 'Get Gravity Forms Info', 'gravityforms' ),
					'description'      => __( 'Returns WordPress site identity (URL, name), Gravity Forms plugin version, license status, active add-ons, and system compatibility information. Useful for diagnostics, AI agent context-setting, and multi-site identification. The active_addons, total_forms, and total_entries fields are only present when the caller holds the matching capability; treat them as optional.', 'gravityforms' ),
					'summary'          => __( 'Read your Gravity Forms version, license, and active add-ons.', 'gravityforms' ),
					'category'         => 'gravityforms-system',
					'execute_callback' => array( GF_Abilities_Handler_System::class, 'get_system_info' ),
					'capability'       => 'gravityforms_view_settings',
					'input_schema'     => array(
						'type'                 => 'object',
						'default'              => (object) array(),
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'site_url'      => array( 'type' => 'string', 'description' => __( 'The WordPress site URL.', 'gravityforms' ) ),
							'site_name'     => array( 'type' => 'string', 'description' => __( 'The WordPress site name.', 'gravityforms' ) ),
							'version'       => array( 'type' => 'string', 'description' => __( 'Gravity Forms version.', 'gravityforms' ) ),
							'is_licensed'   => array( 'type' => 'boolean' ),
							'license_type'  => array( 'type' => 'string', 'enum' => array( 'basic', 'pro', 'elite', 'expired', 'none' ) ),
							'active_addons' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'slug'    => array( 'type' => 'string' ),
										'name'    => array( 'type' => 'string' ),
										'version' => array( 'type' => 'string' ),
									),
								),
							),
							'total_forms'   => array( 'type' => 'integer', 'description' => __( 'Number of forms excluding trash (active and inactive). May be lower than counts that include trashed forms.', 'gravityforms' ) ),
							'total_entries' => array( 'type' => 'integer', 'description' => __( 'Total entries across all forms.', 'gravityforms' ) ),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/system-field-types',
				'args' => array(
					'label'            => __( 'List Field Types', 'gravityforms' ),
					'description'      => __( 'Returns all registered form field types with their labels and configuration options. Essential for AI agents building forms — they need to know what field types are available and how to configure them.', 'gravityforms' ),
					'summary'          => __( 'See which field types are available for building forms.', 'gravityforms' ),
					'category'         => 'gravityforms-system',
					'execute_callback' => array( GF_Abilities_Handler_System::class, 'list_field_types' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'default'              => (object) array(),
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'type'                   => array( 'type' => 'string', 'description' => __( 'Field type slug.', 'gravityforms' ) ),
								'label'                  => array( 'type' => 'string', 'description' => __( 'Human-readable field type name.', 'gravityforms' ) ),
								'description'            => array( 'type' => 'string', 'description' => __( 'Brief description of the field type purpose.', 'gravityforms' ) ),
								'supports_choices'       => array( 'type' => 'boolean', 'description' => __( 'Whether the field uses a choices array (select, radio, checkbox, etc.).', 'gravityforms' ) ),
								'supports_conditional_logic' => array( 'type' => 'boolean' ),
								'has_inputs'             => array( 'type' => 'boolean', 'description' => __( 'Whether this is a compound field with sub-inputs (e.g., name, address). When true, submission values use input IDs like input_1.3, input_1.6.', 'gravityforms' ) ),
								'default_inputs'         => array(
									'type'        => 'array',
									'description' => __( 'Sub-input definitions for compound fields. Each input has an ID suffix (e.g., "1.3" for First Name on a Name field with ID 1).', 'gravityforms' ),
									'items'       => array(
										'type'       => 'object',
										'properties' => array(
											'id'    => array( 'type' => 'string', 'description' => __( 'Input ID suffix (e.g., ".3" becomes "input_{field_id}.3" for submissions).', 'gravityforms' ) ),
											'label' => array( 'type' => 'string' ),
											'hidden_by_default' => array( 'type' => 'boolean' ),
										),
									),
								),
								'format_options'         => array(
									'type'        => 'array',
									'description' => __( 'Phone fields only — the valid phoneFormat values for this site. Use one of these exact values when creating phone fields; other values are rejected.', 'gravityforms' ),
									'items'       => array( 'type' => 'string' ),
								),
								'supports_placeholder'   => array( 'type' => 'boolean' ),
								'supports_default_value' => array( 'type' => 'boolean' ),
								'supports_description'   => array( 'type' => 'boolean' ),
								'supports_required'      => array( 'type' => 'boolean' ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
		);
	}
}
