<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines note abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Notes {

	/**
	 * Get note ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/notes-list',
				'args' => array(
					'label'            => __( 'List Entry Notes', 'gravityforms' ),
					'description'      => __( 'Returns all notes attached to a specific entry. Notes are used for internal team communication and audit trails on submissions.', 'gravityforms' ),
					'summary'          => __( 'Read the internal notes on a submission.', 'gravityforms' ),
					'category'         => 'gravityforms-notes',
					'execute_callback' => array( GF_Abilities_Handler_Notes::class, 'list_notes' ),
					'capability'       => 'gravityforms_view_entry_notes',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'entry_id', __( 'The ID of the entry to list notes for.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'           => array( 'type' => 'integer' ),
								'user_id'      => array( 'type' => 'integer' ),
								'user_name'    => array( 'type' => 'string' ),
								'date_created' => array( 'type' => 'string' ),
								'value'        => array( 'type' => 'string', 'description' => __( 'The note text.', 'gravityforms' ) ),
								'note_type'    => array( 'type' => 'string' ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/notes-add',
				'args' => array(
					'label'            => __( 'Add Entry Note', 'gravityforms' ),
					'description'      => __( 'Adds a note to an entry. AI agents and integrations can use this to annotate submissions with processing results, audit information, or follow-up actions.', 'gravityforms' ),
					'summary'          => __( 'Add an internal note to a submission.', 'gravityforms' ),
					'category'         => 'gravityforms-notes',
					'execute_callback' => array( GF_Abilities_Handler_Notes::class, 'add_note' ),
					'capability'       => 'gravityforms_edit_entry_notes',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'entry_id', 'note' ),
						'properties'           => array(
							'entry_id'  => array( 'type' => 'integer', 'description' => __( 'The ID of the entry to add the note to.', 'gravityforms' ) ),
							'note'      => array( 'type' => 'string', 'description' => __( 'The note text to add.', 'gravityforms' ) ),
							'note_type' => array( 'type' => 'string', 'description' => __( 'Optional note type classification.', 'gravityforms' ), 'default' => 'note' ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'note_id' => array( 'type' => 'integer' ),
						),
					),
					'destructive'      => true,
				),
			),
		);
	}
}
