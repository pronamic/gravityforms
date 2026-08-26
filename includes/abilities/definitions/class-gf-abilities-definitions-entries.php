<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines entry abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Entries {

	/**
	 * Get entry ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/entries-get',
				'args' => array(
					'label'            => __( 'Get Entry', 'gravityforms' ),
					'description'      => __( 'Retrieves a single entry by its ID, including all field values and metadata (date, IP, source URL, etc.).', 'gravityforms' ),
					'summary'          => __( 'Read a single submission.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'get_entry' ),
					'capability'       => 'gravityforms_view_entries',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'entry_id', __( 'The ID of the entry to retrieve.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'                 => 'object',
						'properties'           => GF_Ability_Schemas::entry_summary_properties(),
						'additionalProperties' => true,
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/entries-search',
				'args' => array(
					'label'            => __( 'Search Entries', 'gravityforms' ),
					'description'      => __( 'Searches entries across one or more forms using flexible criteria. Supports field value filtering, date ranges, status filters, pagination, and sorting. Returns matching entries and total count.', 'gravityforms' ),
					'summary'          => __( 'Search and read submissions.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'search_entries' ),
					'capability'       => 'gravityforms_view_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_ids' ),
						'properties'           => array(
							'form_ids'        => array_merge(
								GF_Ability_Schemas::form_ids_property(),
								array( 'description' => __( 'The form ID(s) to search entries for. Pass a single integer or an array of form IDs.', 'gravityforms' ) )
							),
							'search_criteria' => array_merge(
								GF_Ability_Schemas::entry_search_criteria_schema(),
								array( 'description' => __( 'Search filter criteria.', 'gravityforms' ) )
							),
							'sorting'         => array_merge(
								GF_Ability_Schemas::sorting_schema(),
								array( 'description' => __( 'Sorting configuration.', 'gravityforms' ) )
							),
							'paging'          => array_merge(
								GF_Ability_Schemas::paging_schema(),
								array( 'description' => __( 'Pagination configuration.', 'gravityforms' ) )
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'total_count' => array( 'type' => 'integer', 'description' => __( 'Total entries matching criteria (before paging).', 'gravityforms' ) ),
							'entries'     => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'object' ),
								'description' => __( 'Array of entry objects.', 'gravityforms' ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/entries-count',
				'args' => array(
					'label'            => __( 'Count Entries', 'gravityforms' ),
					'description'      => __( 'Returns the total count of entries matching the given criteria without returning entry data. Efficient for dashboards and statistics. When multiple form IDs are provided, returns a per-form breakdown (counts keyed by form ID) plus a combined total — one call instead of N.', 'gravityforms' ),
					'summary'          => __( 'Count how many submissions match a search.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'count_entries' ),
					'capability'       => 'gravityforms_view_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_ids' ),
						'properties'           => array(
							'form_ids'        => array_merge(
								GF_Ability_Schemas::form_ids_property(),
								array( 'description' => __( 'The form ID(s) to count entries for. Pass a single integer or an array of form IDs.', 'gravityforms' ) )
							),
							'search_criteria' => array_merge(
								GF_Ability_Schemas::entry_search_criteria_schema(),
								array( 'description' => __( 'Same locked search criteria schema as gravityforms/entries-search.', 'gravityforms' ) )
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'count'  => array( 'type' => 'integer', 'description' => __( 'Total matching entries (single form ID).', 'gravityforms' ) ),
							'total'  => array( 'type' => 'integer', 'description' => __( 'Combined total across all forms (multiple form IDs).', 'gravityforms' ) ),
							'counts' => array(
								'type'                 => 'object',
								'description'          => __( 'Per-form counts keyed by form ID (multiple form IDs only).', 'gravityforms' ),
								'additionalProperties' => array( 'type' => 'integer' ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/entries-create',
				'args' => array(
					'label'            => __( 'Create Entry', 'gravityforms' ),
					'description'      => __( 'Creates a raw entry record for a form. Unlike form submission, this bypasses validation, notifications, and feed processing. Useful for programmatic entry creation. The entry is stamped with the current date and acting user.', 'gravityforms' ),
					'summary'          => __( 'Add submission records directly, skipping validation and emails.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'create_entry' ),
					'capability'       => 'gravityforms_edit_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'entry' ),
						'properties'           => array(
							'entry' => array(
								'type'                 => 'object',
								'required'             => array( 'form_id' ),
								'description'          => __( 'The entry object. Must include form_id. Field values are keyed by field ID (e.g., \'1\': \'John\', \'2\': \'john@example.com\').', 'gravityforms' ),
								'properties'           => array(
									'form_id' => array( 'type' => 'integer', 'description' => __( 'The ID of the form this entry belongs to.', 'gravityforms' ) ),
									'status'  => array( 'type' => 'string', 'enum' => array( 'active', 'spam', 'trash' ), 'default' => 'active' ),
								),
								'additionalProperties' => true,
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'entry_id' => array( 'type' => 'integer', 'description' => __( 'The ID of the newly created entry.', 'gravityforms' ) ),
						),
					),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/entries-update',
				'args' => array(
					'label'            => __( 'Update Entry', 'gravityforms' ),
					'description'      => __( 'Updates an existing entry\'s field values and/or metadata.', 'gravityforms' ),
					'summary'          => __( 'Change the data saved in a submission.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'update_entry' ),
					'capability'       => 'gravityforms_edit_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'entry' ),
						'properties'           => array(
							'entry' => array(
								'type'                 => 'object',
								'required'             => array( 'id' ),
								'description'          => __( 'The entry object with the ID and fields to update.', 'gravityforms' ),
								'properties'           => array(
									'id' => array( 'type' => 'integer', 'description' => __( 'The entry ID to update.', 'gravityforms' ) ),
								),
								'additionalProperties' => true,
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => GF_Ability_Schemas::success_output_schema(),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/entries-delete',
				'args' => array(
					'label'            => __( 'Delete Entries', 'gravityforms' ),
					'description'      => __( 'Deletes entries. By default, moves entries to trash (soft delete). Pass force: true to permanently delete entries and all associated data (notes, meta). Two modes: (1) Single — pass entry_id. (2) Bulk — pass form_id to delete all entries for that form, optionally filtered by search_criteria. IMPORTANT: For bulk deletion, always call entries-count first with the same form_id and search_criteria to confirm the scope, and verify with the user before proceeding. Bulk permanent deletion requires confirmation in the exact format: DELETE {count} ENTRIES FROM FORM {form_id}. Bulk calls are capped at 100 entries per call to avoid timeouts; if the response includes capped: true and remaining > 0, call entries-count again to obtain the updated count, then call entries-delete again with the new DELETE {count} ENTRIES FROM FORM {form_id} confirmation. Repeat until remaining is 0. The cap applies to both trash and permanent (force) modes.', 'gravityforms' ),
					'summary'          => __( 'Trash or permanently delete submissions.', 'gravityforms' ),
					'category'         => 'gravityforms-entries',
					'execute_callback' => array( GF_Abilities_Handler_Entries::class, 'delete_entry' ),
					'capability'       => 'gravityforms_delete_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'default'              => (object) array(),
						'properties'           => array(
							'entry_id'        => array( 'type' => 'integer', 'description' => __( 'Delete a single entry by ID.', 'gravityforms' ) ),
							'form_id'         => array( 'type' => 'integer', 'description' => __( 'Delete all entries for this form. Combine with search_criteria to filter which entries are deleted.', 'gravityforms' ) ),
							'search_criteria' => array_merge(
								GF_Ability_Schemas::entry_search_criteria_schema(),
								array( 'description' => __( 'Optional filter for bulk delete. Same format as entries-search. Only used with form_id.', 'gravityforms' ) )
							),
							'force'           => array( 'type' => 'boolean', 'description' => __( 'Set to true to permanently delete entries and all associated data. Default (false) moves entries to trash.', 'gravityforms' ) ),
							'confirmation'    => array(
								'type'        => 'string',
								'description' => __( 'For single permanent delete, echo back the entry ID. For bulk permanent delete, echo back the exact phrase: DELETE {count} ENTRIES FROM FORM {form_id}.', 'gravityforms' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'success'       => array( 'type' => 'boolean' ),
							'deleted_count' => array( 'type' => 'integer', 'description' => __( 'Number of entries affected in this call.', 'gravityforms' ) ),
							'trashed'       => array( 'type' => 'boolean', 'description' => __( 'True if entries were moved to trash. False if permanently deleted.', 'gravityforms' ) ),
							'remaining'     => array( 'type' => 'integer', 'description' => __( 'Estimated number of entries still matching the search criteria after this call. Only meaningful for bulk mode. When greater than 0, call entries-count then entries-delete again with the updated confirmation phrase to continue processing.', 'gravityforms' ) ),
							'capped'        => array( 'type' => 'boolean', 'description' => __( 'True if this call hit the per-call processing cap and more matching entries remain. The caller should loop until this is false.', 'gravityforms' ) ),
							'cap'           => array( 'type' => 'integer', 'description' => __( 'The maximum number of entries processed per bulk-delete call.', 'gravityforms' ) ),
						),
					),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
		);
	}
}
