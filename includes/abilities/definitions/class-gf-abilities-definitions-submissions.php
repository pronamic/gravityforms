<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines submission abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Submissions {

	/**
	 * Get submission ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/submissions-submit',
				'args' => array(
					'label'            => __( 'Submit Form', 'gravityforms' ),
					'description'      => __( 'Submits a form through the full Gravity Forms processing pipeline — validation, entry creation, notifications, confirmations, and feed processing. This is the equivalent of a user filling out and submitting a form.', 'gravityforms' ),
					'summary'          => __( 'Submit a form as if a visitor did — saves an entry and sends emails.', 'gravityforms' ),
					'category'         => 'gravityforms-submissions',
					'execute_callback' => array( GF_Abilities_Handler_Submissions::class, 'submit_form' ),
					'capability'       => 'gravityforms_edit_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'input_values' ),
						'properties'           => array(
							'form_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the form to submit.', 'gravityforms' ) ),
							'input_values' => GF_Ability_Schemas::input_values_schema(),
							'field_values' => array(
								'type'                 => 'object',
								'description'          => __( 'Optional. Parameter pre-population values.', 'gravityforms' ),
								'additionalProperties' => array( 'type' => 'string' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'is_valid'             => array( 'type' => 'boolean', 'description' => __( 'Whether form validation passed.', 'gravityforms' ) ),
							'entry_id'             => array( 'type' => 'integer', 'description' => __( 'The created entry ID (only if is_valid is true).', 'gravityforms' ) ),
							'confirmation_message' => array( 'type' => 'string', 'description' => __( 'The confirmation message or redirect URL.', 'gravityforms' ) ),
							'confirmation_type'    => array( 'type' => 'string', 'enum' => array( 'message', 'redirect', 'page' ) ),
							'validation_messages'  => GF_Ability_Schemas::validation_messages_schema(),
						),
					),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/submissions-validate',
				'args' => array(
					'label'            => __( 'Validate Submission', 'gravityforms' ),
					'description'      => __( 'Validates form input values without creating an entry or triggering any processing. Useful for pre-flight validation before actual submission. Returns validation status and any field errors.', 'gravityforms' ),
					'summary'          => __( 'Check whether form input is valid, without saving anything.', 'gravityforms' ),
					'category'         => 'gravityforms-submissions',
					'execute_callback' => array( GF_Abilities_Handler_Submissions::class, 'validate_submission' ),
					'capability'       => 'gravityforms_edit_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'input_values' ),
						'properties'           => array(
							'form_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the form to validate the input values against.', 'gravityforms' ) ),
							'input_values' => array_merge(
								GF_Ability_Schemas::input_values_schema(),
								array( 'description' => __( 'Field values to validate, same format as submissions/submit.', 'gravityforms' ) )
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'is_valid'            => array( 'type' => 'boolean' ),
							'validation_messages' => GF_Ability_Schemas::validation_messages_schema(),
						),
					),
					'idempotent'       => true,
				),
			),
		);
	}
}
