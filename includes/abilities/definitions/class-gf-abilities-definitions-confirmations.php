<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines confirmation abilities.
 *
 * Targeted, delete-capable editing of a form's confirmations (the response a
 * submitter sees: a message, a page, or a redirect). The canonical path for
 * confirmation edits — forms-update can also carry confirmations for atomic
 * form creation, but round-trips the whole form and cannot delete.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Confirmations {

	/**
	 * Get confirmation ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/confirmations-list',
				'args' => array(
					'label'            => __( 'List Confirmations', 'gravityforms' ),
					'description'      => __( 'Returns all confirmations for a form — the message, page, or redirect a submitter sees after submitting — including type, content, and conditional logic.', 'gravityforms' ),
					'summary'          => __( 'View a form\'s confirmation settings.', 'gravityforms' ),
					'category'         => 'gravityforms-confirmations',
					'execute_callback' => array( GF_Abilities_Handler_Confirmations::class, 'list_confirmations' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'form_id', __( 'The ID of the form to list confirmations for.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => self::confirmation_object_schema(),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/confirmations-create',
				'args' => array(
					'label'            => __( 'Create Confirmation', 'gravityforms' ),
					'description'      => __( 'Adds a confirmation to a form. type is message | page | redirect: message needs message; page needs pageId; redirect needs url. Add conditionalLogic to show it only on matching submissions (the default confirmation shows when none match). Returns the generated confirmation_id.', 'gravityforms' ),
					'summary'          => __( 'Add a confirmation to a form.', 'gravityforms' ),
					'category'         => 'gravityforms-confirmations',
					'execute_callback' => array( GF_Abilities_Handler_Confirmations::class, 'create_confirmation' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'confirmation' ),
						'properties'           => array(
							'form_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the form to add the confirmation to.', 'gravityforms' ) ),
							'confirmation' => self::confirmation_input_schema(),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => self::mutation_output_schema(),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/confirmations-update',
				'args' => array(
					'label'            => __( 'Update Confirmation', 'gravityforms' ),
					'description'      => __( 'Partially updates one confirmation by ID (only supplied keys change; the id is immutable). The resulting confirmation must still satisfy its type\'s required fields. Call gravityforms/confirmations-list first to get the confirmation_id.', 'gravityforms' ),
					'summary'          => __( 'Modify an existing confirmation.', 'gravityforms' ),
					'category'         => 'gravityforms-confirmations',
					'execute_callback' => array( GF_Abilities_Handler_Confirmations::class, 'update_confirmation' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'confirmation_id', 'confirmation' ),
						'properties'           => array(
							'form_id'         => array( 'type' => 'integer', 'description' => __( 'The ID of the form.', 'gravityforms' ) ),
							'confirmation_id' => array( 'type' => 'string', 'description' => __( 'The ID of the confirmation to update.', 'gravityforms' ) ),
							'confirmation'    => self::confirmation_input_schema(),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => self::mutation_output_schema(),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/confirmations-delete',
				'args' => array(
					'label'            => __( 'Delete Confirmation', 'gravityforms' ),
					'description'      => __( 'Permanently removes one confirmation by ID. The form\'s default confirmation cannot be deleted (edit it instead). Call gravityforms/confirmations-list first to get the confirmation_id.', 'gravityforms' ),
					'summary'          => __( 'Delete a confirmation from a form.', 'gravityforms' ),
					'category'         => 'gravityforms-confirmations',
					'execute_callback' => array( GF_Abilities_Handler_Confirmations::class, 'delete_confirmation' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'confirmation_id' ),
						'properties'           => array(
							'form_id'         => array( 'type' => 'integer', 'description' => __( 'The ID of the form.', 'gravityforms' ) ),
							'confirmation_id' => array( 'type' => 'string', 'description' => __( 'The ID of the confirmation to delete.', 'gravityforms' ) ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'success'                 => array( 'type' => 'boolean' ),
							'form_id'                 => array( 'type' => 'integer' ),
							'deleted_confirmation_id' => array( 'type' => 'string' ),
						),
					),
					'destructive'      => true,
				),
			),
		);
	}

	/**
	 * Schema for a confirmation object (output/read shape).
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private static function confirmation_object_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'               => array( 'type' => 'string' ),
				'name'             => array( 'type' => 'string' ),
				'isDefault'        => array( 'type' => 'boolean', 'description' => __( 'The fallback confirmation shown when no conditional confirmation matches. Cannot be deleted.', 'gravityforms' ) ),
				'type'             => array( 'type' => 'string', 'description' => __( 'message, page, or redirect.', 'gravityforms' ) ),
				'message'          => array( 'type' => 'string' ),
				'pageId'           => array( 'type' => array( 'string', 'integer' ) ),
				'url'              => array( 'type' => 'string' ),
				'queryString'      => array( 'type' => 'string' ),
				'conditionalLogic' => array( 'type' => array( 'object', 'null' ) ),
			),
		);
	}

	/**
	 * Schema for the confirmation settings a write accepts.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private static function confirmation_input_schema() {
		return array(
			'type'        => 'object',
			'description' => __( 'Confirmation settings. type is message | page | redirect.', 'gravityforms' ),
			'properties'  => array(
				'name'             => array( 'type' => 'string', 'description' => __( 'Admin-facing name.', 'gravityforms' ) ),
				'type'             => array( 'type' => 'string', 'enum' => array( 'message', 'page', 'redirect' ) ),
				'message'          => array( 'type' => 'string', 'description' => __( 'For type=message. Supports merge tags and HTML.', 'gravityforms' ) ),
				'pageId'           => array( 'type' => array( 'string', 'integer' ), 'description' => __( 'For type=page. The WordPress page ID to show.', 'gravityforms' ) ),
				'url'              => array( 'type' => 'string', 'description' => __( 'For type=redirect. The URL to redirect to.', 'gravityforms' ) ),
				'queryString'      => array( 'type' => 'string', 'description' => __( 'For type=redirect. Optional query string (supports merge tags).', 'gravityforms' ) ),
				'conditionalLogic' => array( 'type' => array( 'object', 'null' ), 'description' => __( 'Optional GF conditional-logic object ({actionType, logicType, rules}) gating when this confirmation applies.', 'gravityforms' ) ),
			),
		);
	}

	/**
	 * Shared write output schema (success + the saved confirmation).
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private static function mutation_output_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'         => array( 'type' => 'boolean' ),
				'form_id'         => array( 'type' => 'integer' ),
				'confirmation_id' => array( 'type' => 'string' ),
				'confirmation'    => array( 'type' => 'object' ),
			),
		);
	}
}
