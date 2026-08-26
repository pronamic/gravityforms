<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines form abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Forms {

	/**
	 * Get form ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/forms-get',
				'args' => array(
					'label'            => __( 'Get Form', 'gravityforms' ),
					'description'      => __( 'Retrieves a single form by its ID, including fields, confirmations, notifications, and settings.', 'gravityforms' ),
					'summary'          => __( 'Read one form\'s fields, settings, and notifications.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'get_form' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'form_id', __( 'The ID of the form to retrieve.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array( 'type' => 'integer', 'description' => __( 'Form ID.', 'gravityforms' ) ),
							'title'         => array( 'type' => 'string', 'description' => __( 'Form title.', 'gravityforms' ) ),
							'description'   => array( 'type' => 'string', 'description' => __( 'Form description.', 'gravityforms' ) ),
							'is_active'     => array( 'type' => 'string', 'description' => __( 'Whether the form is active (1) or inactive (0).', 'gravityforms' ) ),
							'date_created'  => array( 'type' => 'string', 'description' => __( 'Date the form was created (Y-m-d H:i:s).', 'gravityforms' ) ),
							'is_trash'      => array( 'type' => 'string', 'description' => __( 'Whether the form is in trash (1 or 0).', 'gravityforms' ) ),
							'fields'        => array(
								'type'        => 'array',
								'description' => __( 'Array of field objects defining the form structure.', 'gravityforms' ),
								'items'       => array( 'type' => 'object' ),
							),
							'confirmations' => array( 'type' => 'object', 'description' => __( 'Confirmation settings keyed by confirmation ID.', 'gravityforms' ) ),
							'notifications' => array( 'type' => 'object', 'description' => __( 'Notification settings keyed by notification ID.', 'gravityforms' ) ),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/forms-list',
				'args' => array(
					'label'            => __( 'List Forms', 'gravityforms' ),
					'description'      => __( 'Returns all forms, active and inactive (trashed forms excluded unless trash is true), optionally filtered by active status, trash status, or title search. Useful for discovering available forms before performing other operations.', 'gravityforms' ),
					'summary'          => __( 'See a list of your forms.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'list_forms' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'default'              => (object) array(),
						'properties'           => array(
							'active'      => array(
								'type'        => 'boolean',
								'description' => __( 'Filter by active status: true returns only active forms, false returns only inactive forms. Omit to return both.', 'gravityforms' ),
							),
							'trash'       => array(
								'type'        => 'boolean',
								'description' => __( 'Return trashed forms. Defaults to false.', 'gravityforms' ),
								'default'     => false,
							),
							'sort_column' => array(
								'type'        => 'string',
								'enum'        => array( 'id', 'title', 'date_created', 'is_active' ),
								'description' => __( 'Column to sort results by. Defaults to \'id\'.', 'gravityforms' ),
								'default'     => 'id',
							),
							'sort_dir'    => array(
								'type'        => 'string',
								'enum'        => array( 'ASC', 'DESC' ),
								'description' => __( 'Sort direction. Defaults to \'ASC\'.', 'gravityforms' ),
								'default'     => 'ASC',
							),
							'search'      => array(
								'type'        => 'string',
								'description' => __( 'Filter forms by title (case-insensitive partial match).', 'gravityforms' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => GF_Ability_Schemas::form_summary_properties(),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/forms-create',
				'args' => array(
					'label'            => __( 'Create Form', 'gravityforms' ),
					'description'      => __( 'Creates a new form from a form definition object. Returns the new form ID and admin edit URL on success. The form object must include a title and fields array. IMPORTANT: Call gravityforms/system-field-types first to discover available field types and their configuration options (supports_choices, has_inputs, etc.) before building the fields array. LAYOUT: Use layoutGroupId (any string) and layoutGridColumnSpan (1-12) on fields to control layout — fields sharing the same layoutGroupId render side-by-side. PITFALLS: For "first name / last name side by side" use two text fields with layout grid, NOT a single compound name field (which renders its own sub-inputs internally). The defaultValue property must be a string, never an array.', 'gravityforms' ),
					'summary'          => __( 'Create new forms.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'create_form' ),
					'capability'       => 'gravityforms_create_form',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form' ),
						'properties'           => array(
							'form' => array(
								'type'        => 'object',
								'required'    => array( 'title', 'fields' ),
								'description' => __( 'The form definition object.', 'gravityforms' ),
								'properties'  => array_merge(
									array(
										'title'         => array( 'type' => 'string', 'description' => __( 'The form title.', 'gravityforms' ) ),
										'description'   => array( 'type' => 'string', 'description' => __( 'The form description displayed to users.', 'gravityforms' ) ),
										'fields'        => array(
											'type'        => 'array',
											'description' => __( 'Array of field definition objects. Each field must have a \'type\' property.', 'gravityforms' ),
											'items'       => GF_Ability_Schemas::form_field_item_schema(),
										),
										'confirmations' => array( 'type' => 'object', 'description' => __( 'Optional confirmation settings.', 'gravityforms' ) ),
										'notifications' => array( 'type' => 'object', 'description' => __( 'Optional notification settings.', 'gravityforms' ) ),
									),
									GF_Ability_Schemas::form_settings_properties()
								),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => GF_Ability_Schemas::form_mutation_output_schema(),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/forms-update',
				'args' => array(
					'label'            => __( 'Update Form', 'gravityforms' ),
					'description'      => __( 'Partially updates an existing form. Only provided properties are changed; omitted properties are preserved. The \'fields\' array, if provided, replaces ALL existing fields — always include every field you want to keep. Notifications and confirmations passed here are merged by key (convenient for atomic form setup); for targeted edits to a single notification or confirmation — including deletion — prefer the dedicated gravityforms/notifications-* and gravityforms/confirmations-* tools, which don\'t require round-tripping the whole form. TIP: Call gravityforms/forms-get first to retrieve the current form structure, then modify and pass back the full fields array. To restore a trashed form, pass is_trash: false.', 'gravityforms' ),
					'summary'          => __( 'Change a form\'s fields and settings.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'update_form' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form' ),
						'properties'           => array(
							'form' => array(
								'type'        => 'object',
								'required'    => array( 'id' ),
								'description' => __( 'The form object with updated properties. Must include \'id\'.', 'gravityforms' ),
								'properties'  => array_merge(
									array(
										'id'            => array( 'type' => 'integer', 'description' => __( 'The form ID to update.', 'gravityforms' ) ),
										'title'         => array( 'type' => 'string', 'description' => __( 'The form title.', 'gravityforms' ) ),
										'description'   => array( 'type' => 'string', 'description' => __( 'The form description displayed to users.', 'gravityforms' ) ),
										'is_trash'      => array( 'type' => 'boolean', 'description' => __( 'Set to false to restore a trashed form. Set to true to move an active form to trash (prefer forms-delete for trashing).', 'gravityforms' ) ),
										'fields'        => array(
											'type'        => 'array',
											'description' => __( 'Complete array of field definitions. WARNING: This replaces ALL existing fields — include every field you want to keep. Call gravityforms/forms-get first to get the current fields.', 'gravityforms' ),
											'items'       => GF_Ability_Schemas::form_field_item_schema( true ),
										),
										'confirmations' => array( 'type' => 'object', 'description' => __( 'Confirmation settings. Merged by key with existing confirmations.', 'gravityforms' ) ),
										'notifications' => array( 'type' => 'object', 'description' => __( 'Notification settings. Merged by key with existing notifications.', 'gravityforms' ) ),
										'is_active'     => array( 'type' => 'string', 'enum' => array( '0', '1' ), 'description' => __( 'Whether the form is active (\'1\') or inactive (\'0\'). Inactive forms do not render at all — use schedule settings to pause a form while showing a message.', 'gravityforms' ) ),
									),
									GF_Ability_Schemas::form_settings_properties()
								),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'success'  => array( 'type' => 'boolean', 'description' => __( 'Whether the operation succeeded.', 'gravityforms' ) ),
							'trashed'  => array( 'type' => 'boolean', 'description' => __( 'Present and true when the update moved the form to the trash.', 'gravityforms' ) ),
							'restored' => array( 'type' => 'boolean', 'description' => __( 'Present and true when the update restored the form from the trash.', 'gravityforms' ) ),
						),
					),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/forms-delete',
				'args' => array(
					'label'            => __( 'Delete Form', 'gravityforms' ),
					'description'      => __( 'Deletes a form. By default, moves the form to trash (soft delete) — the form can be restored from the GF admin. Pass force: true to permanently delete the form and all its associated entries, notifications, and feeds. Permanent deletion cannot be undone.', 'gravityforms' ),
					'summary'          => __( 'Trash or permanently delete forms.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'delete_form' ),
					'capability'       => 'gravityforms_delete_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id' ),
						'properties'           => array(
							'form_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the form to delete.', 'gravityforms' ) ),
							'force'        => array( 'type' => 'boolean', 'description' => __( 'Set to true to permanently delete the form and all associated data. Default (false) moves the form to trash.', 'gravityforms' ) ),
							'confirmation' => array(
								'type'        => 'string',
								'description' => __( 'Echo back the form title to confirm permanent deletion.', 'gravityforms' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'success' => array( 'type' => 'boolean' ),
							'trashed' => array( 'type' => 'boolean', 'description' => __( 'True if the form was moved to trash. False if permanently deleted.', 'gravityforms' ) ),
						),
					),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/forms-analyze-logic',
				'args' => array(
					'label'            => __( 'Analyze Form Logic', 'gravityforms' ),
					'description'      => __( 'Analyzes all conditional logic on a form — fields, notifications, confirmations, submit button, and page navigation buttons. Returns a structured dependency map showing which fields control which elements, with all field IDs resolved to labels. Use this to understand form logic flow, audit conditional rules, and debug visibility issues. More useful than parsing raw forms-get output because it aggregates logic from all locations and cross-references field references.', 'gravityforms' ),
					'summary'          => __( 'Review a form\'s conditional show/hide logic.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'analyze_form_logic' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'form_id', __( 'The ID of the form to analyze.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'form_id'             => array( 'type' => 'integer', 'description' => __( 'Form ID.', 'gravityforms' ) ),
							'form_title'          => array( 'type' => 'string', 'description' => __( 'Form title.', 'gravityforms' ) ),
							'summary'             => array(
								'type'        => 'object',
								'description' => __( 'Quick overview of logic counts across all locations.', 'gravityforms' ),
								'properties'  => array(
									'total_rules'       => array( 'type' => 'integer', 'description' => __( 'Total conditional logic rules across all locations.', 'gravityforms' ) ),
									'fields_with_logic' => array( 'type' => 'integer', 'description' => __( 'Number of fields that have conditional logic.', 'gravityforms' ) ),
									'notifications_with_logic' => array( 'type' => 'integer', 'description' => __( 'Number of notifications with conditional logic.', 'gravityforms' ) ),
									'confirmations_with_logic' => array( 'type' => 'integer', 'description' => __( 'Number of confirmations with conditional logic.', 'gravityforms' ) ),
									'has_submit_button_logic' => array( 'type' => 'boolean' ),
									'has_page_button_logic' => array( 'type' => 'boolean' ),
								),
							),
							'field_logic'         => array(
								'type'        => 'array',
								'description' => __( 'Conditional logic on form fields. Each entry shows which field is controlled, the show/hide action, and rules with resolved source field labels.', 'gravityforms' ),
								'items'       => array( 'type' => 'object' ),
							),
							'notification_logic'  => array(
								'type'        => 'array',
								'description' => __( 'Conditional logic on notifications.', 'gravityforms' ),
								'items'       => array( 'type' => 'object' ),
							),
							'confirmation_logic'  => array(
								'type'        => 'array',
								'description' => __( 'Conditional logic on confirmations.', 'gravityforms' ),
								'items'       => array( 'type' => 'object' ),
							),
							'submit_button_logic' => array(
								'type'        => array( 'object', 'null' ),
								'description' => __( 'Conditional logic on the submit button, or null if none.', 'gravityforms' ),
							),
							'page_logic'          => array(
								'type'        => 'array',
								'description' => __( 'Conditional logic on page navigation buttons (multi-page forms).', 'gravityforms' ),
								'items'       => array( 'type' => 'object' ),
							),
							'dependency_map'      => array(
								'type'        => 'object',
								'description' => __( 'Reverse dependency map keyed by source field ID. Shows what each field controls — e.g., "field 1 controls field 3 (hide when all)". Each control entry appears once per distinct target; rule_count says how many of that target\'s conditions reference the source field. This is the most useful view for understanding logic flow.', 'gravityforms' ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/forms-duplicate',
				'args' => array(
					'label'            => __( 'Duplicate Form', 'gravityforms' ),
					'description'      => __( 'Creates a copy of an existing form including all fields, confirmations, and notifications. Returns the new form\'s ID. TIP: After duplicating, call forms-get on the new form to review fields, confirmations, and notifications for content that may need updating (e.g., dates, event names, or references to the original form).', 'gravityforms' ),
					'summary'          => __( 'Make a copy of a form.', 'gravityforms' ),
					'category'         => 'gravityforms-forms',
					'execute_callback' => array( GF_Abilities_Handler_Forms::class, 'duplicate_form' ),
					'capability'       => 'gravityforms_create_form',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'form_id', __( 'The ID of the form to duplicate.', 'gravityforms' ) ),
					'output_schema'    => GF_Ability_Schemas::form_mutation_output_schema(),
					'destructive'      => true,
				),
			),
		);
	}
}
