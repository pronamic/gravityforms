<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles entry ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Entries {

	/**
	 * Maximum number of entries processed per bulk-delete call.
	 *
	 * Bulk delete is intentionally capped per call to avoid PHP execution
	 * timeouts on large entry sets. Callers (typically AI agents via MCP) are
	 * expected to loop: call entries-count to obtain the current matching
	 * count, then call entries-delete with the matching confirmation phrase,
	 * and repeat until the returned `remaining` is 0.
	 *
	 * @since 3.1.0
	 *
	 * @var int
	 */
	const MAX_BULK_DELETE_PER_CALL = 100;

	/**
	 * Get an entry by ID.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_entry( $input ) {
		$entry = \GFAPI::get_entry( absint( $input['entry_id'] ) );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		return self::redact_entry( self::slim_entry( $entry ) );
	}

	/**
	 * Search entries.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function search_entries( $input ) {
		$paging = isset( $input['paging'] ) && is_array( $input['paging'] ) ? $input['paging'] : array();

		if ( isset( $paging['page_size'] ) ) {
			$paging['page_size'] = min( 100, max( 1, (int) $paging['page_size'] ) );
		}

		$total_count     = 0;
		$search_criteria = isset( $input['search_criteria'] ) && is_array( $input['search_criteria'] ) ? $input['search_criteria'] : array();
		$search_criteria = self::apply_entry_search_defaults( $search_criteria );
		$search_criteria = self::inject_field_filters_mode( $search_criteria );
		$sorting         = isset( $input['sorting'] ) && is_array( $input['sorting'] ) ? $input['sorting'] : null;

		$hidden_error = self::reject_hidden_field_criteria( $search_criteria, $sorting, (array) $input['form_ids'] );
		if ( $hidden_error ) {
			return $hidden_error;
		}

		$entries = \GFAPI::get_entries(
			$input['form_ids'],
			$search_criteria,
			$sorting,
			empty( $paging ) ? null : $paging,
			$total_count
		);

		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		return array(
			'total_count' => (int) $total_count,
			'entries'     => array_map(
				function ( $entry ) {
					return self::redact_entry( self::slim_entry( $entry ) );
				},
				$entries
			),
		);
	}

	/**
	 * Count entries.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function count_entries( $input ) {
		$search_criteria = isset( $input['search_criteria'] ) && is_array( $input['search_criteria'] ) ? $input['search_criteria'] : array();
		$search_criteria = self::apply_entry_search_defaults( $search_criteria );
		$search_criteria = self::inject_field_filters_mode( $search_criteria );
		$form_ids        = is_array( $input['form_ids'] ) ? $input['form_ids'] : array( $input['form_ids'] );

		$hidden_error = self::reject_hidden_field_criteria( $search_criteria, null, $form_ids );
		if ( $hidden_error ) {
			return $hidden_error;
		}

		// Single form — return flat count for backward compatibility.
		if ( count( $form_ids ) === 1 ) {
			$count = \GFAPI::count_entries( $form_ids[0], $search_criteria );

			return array( 'count' => (int) $count );
		}

		// Multiple forms — return per-form breakdown.
		$counts = array();
		$total  = 0;

		foreach ( $form_ids as $form_id ) {
			$count = \GFAPI::count_entries( (int) $form_id, $search_criteria );

			$counts[ (string) $form_id ] = (int) $count;
			$total                      += (int) $count;
		}

		return array(
			'total'  => $total,
			'counts' => $counts,
		);
	}

	/**
	 * Create an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function create_entry( $input ) {
		$entry = self::whitelist_entry_input( $input['entry'], array( 'form_id', 'is_read', 'is_starred' ) );

		if ( isset( $input['entry']['status'] ) ) {
			$status = (string) $input['entry']['status'];
			if ( ! in_array( $status, array( 'active', 'spam', 'trash' ), true ) ) {
				return new \WP_Error( 'gf_ability_invalid_status', __( 'Entry status must be one of: active, spam, trash.', 'gravityforms' ) );
			}

			if ( 'trash' === $status && ! \GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
				return new \WP_Error(
					'gf_ability_forbidden',
					__( 'Creating an entry in the trash requires the gravityforms_delete_entries capability.', 'gravityforms' )
				);
			}

			$entry['status'] = $status;
		}

		$entry = self::normalize_entry_values( $entry, $entry );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		$entry_id = \GFAPI::add_entry( $entry );

		if ( is_wp_error( $entry_id ) ) {
			return $entry_id;
		}

		return array( 'entry_id' => (int) $entry_id );
	}

	/**
	 * Keep only the entry keys a caller may write directly.
	 *
	 * Field keys and the listed metadata keys stay, and all other keys are dropped. A whitelist is
	 * used because GFAPI persists any registered entry-meta key it receives.
	 *
	 * @since 3.1.0
	 *
	 * @param array    $entry        The caller-supplied entry array.
	 * @param string[] $allowed_meta Non-field keys the caller may set.
	 *
	 * @return array
	 */
	private static function whitelist_entry_input( $entry, $allowed_meta ) {
		$kept = array();

		foreach ( (array) $entry as $key => $value ) {
			if ( preg_match( '/^\d+(\.\d+)?$/', (string) $key ) || in_array( (string) $key, $allowed_meta, true ) ) {
				$kept[ $key ] = $value;
			}
		}

		return $kept;
	}

	/**
	 * Apply default search criteria values that JSON Schema clients may not apply.
	 *
	 * @since 3.1.0
	 *
	 * @param array $search_criteria The caller-supplied search criteria.
	 *
	 * @return array
	 */
	private static function apply_entry_search_defaults( $search_criteria ) {
		if ( ! array_key_exists( 'status', $search_criteria ) || null === $search_criteria['status'] || '' === $search_criteria['status'] || array() === $search_criteria['status'] ) {
			$search_criteria['status'] = 'active';
		}

		return $search_criteria;
	}

	/**
	 * Update an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function update_entry( $input ) {
		$entry_id = absint( rgar( $input['entry'], 'id' ) );
		$stored   = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$changes = $input['entry'];

		$status_change = null;
		if ( isset( $changes['status'] ) ) {
			$new_status = (string) $changes['status'];
			$old_status = (string) rgar( $stored, 'status' );

			if ( ! in_array( $new_status, array( 'active', 'spam', 'trash' ), true ) ) {
				return new \WP_Error( 'gf_ability_invalid_status', __( 'Entry status must be one of: active, spam, trash.', 'gravityforms' ) );
			}

			if ( $new_status !== $old_status ) {
				$touches_trash = ( 'trash' === $new_status || 'trash' === $old_status );

				if ( $touches_trash && ! \GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
					return new \WP_Error(
						'gf_ability_forbidden',
						__( 'Trashing or restoring an entry requires the gravityforms_delete_entries capability.', 'gravityforms' )
					);
				}

				$status_change = $new_status;
			}
		}

		$changes = self::whitelist_entry_input( $changes, array( 'is_read', 'is_starred' ) );

		$entry = array_replace( $stored, $changes );
		$entry = self::normalize_entry_values( $entry, $changes );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		$result = \GFAPI::update_entry( $entry );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( null !== $status_change ) {
			\GFFormsModel::change_entry_status( $entry_id, $status_change );
		}

		return array( 'success' => (bool) $result );
	}

	/**
	 * Delete entries.
	 *
	 * Supports two modes:
	 * - Single: pass entry_id to delete one entry.
	 * - Bulk: pass form_id (+ optional search_criteria) to delete all matching entries server-side.
	 *
	 * By default, entries are moved to trash (soft delete). Pass force: true to permanently delete.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function delete_entry( $input ) {
		$force = ! empty( $input['force'] );

		// Single entry delete mode.
		if ( ! empty( $input['entry_id'] ) ) {
			$entry_id = absint( $input['entry_id'] );

			if ( $force ) {
				if ( ( $input['confirmation'] ?? '' ) !== (string) $entry_id ) {
					return new \WP_Error( 'gf_ability_confirmation_mismatch', __( 'Confirmation does not match the entry ID. Please provide the exact entry ID to confirm permanent deletion.', 'gravityforms' ) );
				}

				$result = \GFAPI::delete_entry( $entry_id );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				return array( 'success' => true, 'deleted_count' => 1, 'trashed' => false );
			}

			// Verify entry exists before trashing.
			$entry = \GFAPI::get_entry( $entry_id );
			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			$result = \GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
			if ( false === $result ) {
				return new \WP_Error( 'gf_ability_trash_failed', __( 'Failed to move entry to trash.', 'gravityforms' ) );
			}

			return array( 'success' => true, 'deleted_count' => 1, 'trashed' => true );
		}

		// Bulk delete mode — requires form_id.
		if ( empty( $input['form_id'] ) ) {
			return new \WP_Error(
				'gf_ability_missing_parameter',
				__( 'Either entry_id (single delete) or form_id (bulk delete) is required.', 'gravityforms' )
			);
		}

		$form_id         = absint( $input['form_id'] );
		$search_criteria = isset( $input['search_criteria'] ) && is_array( $input['search_criteria'] ) ? $input['search_criteria'] : array();
		$search_criteria = self::apply_entry_search_defaults( $search_criteria );
		$search_criteria = self::inject_field_filters_mode( $search_criteria );

		// Bulk delete reports matched counts, so hidden-field criteria leak values
		// here the same way they would through entries-count.
		$hidden_error = self::reject_hidden_field_criteria( $search_criteria, null, array( $form_id ) );
		if ( $hidden_error ) {
			return $hidden_error;
		}

		$affected_count = 0;
		$paging         = array( 'offset' => 0, 'page_size' => self::MAX_BULK_DELETE_PER_CALL );

		// Trash mode can never affect entries that are already in the trash, so
		// exclude them from the working query when the caller explicitly includes
		// trash. This keeps the capped, stateless per-call contract working: each
		// call's first page always contains not-yet-processed entries. Without this,
		// a query that includes trashed entries re-trashes the same first page on
		// every call and never reaches entries beyond the cap.
		if ( ! $force ) {
			$statuses = self::get_non_trash_statuses( rgar( $search_criteria, 'status' ) );

			if ( empty( $statuses ) ) {
				// The caller restricted the query to trashed entries only — nothing to trash.
				return array(
					'success'       => true,
					'deleted_count' => 0,
					'trashed'       => true,
					'remaining'     => 0,
					'capped'        => false,
					'cap'           => self::MAX_BULK_DELETE_PER_CALL,
				);
			}

			$search_criteria['status'] = count( $statuses ) === 1 ? $statuses[0] : $statuses;
		}

		// Snapshot the matching count BEFORE deletion so we can both validate the
		// confirmation phrase (force mode) and report `remaining` to the caller.
		// Callers are expected to recompute the count via entries-count between
		// bulk-delete calls and supply a fresh confirmation each time.
		$total_before = (int) \GFAPI::count_entries( $form_id, $search_criteria );

		if ( $force ) {
			$expected_confirmation = sprintf( 'DELETE %d ENTRIES FROM FORM %d', $total_before, $form_id );

			if ( ( $input['confirmation'] ?? '' ) !== $expected_confirmation ) {
				return new \WP_Error(
					'gf_ability_confirmation_mismatch',
					sprintf(
						/* translators: 1: expected confirmation phrase */
						__( 'Confirmation does not match the bulk deletion scope. Please provide the exact phrase "%s" to confirm permanent deletion. If a prior bulk-delete call already ran, recompute the count via entries-count and use the updated phrase.', 'gravityforms' ),
						$expected_confirmation
					)
				);
			}
		}

		// The caller confirmed a count of zero — deleting anything, including an
		// entry that arrived between the count and the fetch, would exceed the
		// confirmed scope. Nothing to do.
		if ( 0 === $total_before ) {
			return array(
				'success'       => true,
				'deleted_count' => 0,
				'trashed'       => ! $force,
				'remaining'     => 0,
				'capped'        => false,
				'cap'           => self::MAX_BULK_DELETE_PER_CALL,
			);
		}

		// Never process more entries than the count the caller confirmed — an
		// entry arriving between the count and the fetch must not ride along.
		$paging['page_size'] = min( $paging['page_size'], $total_before );

		// Process at most MAX_BULK_DELETE_PER_CALL entries in a single call to
		// avoid PHP execution timeouts on large entry sets. Callers loop until
		// `remaining` is 0. Both modes remove processed entries from the matched
		// set (force deletes rows; trash mode excludes trashed entries from the
		// query above), so the next call naturally picks up the new "first page"
		// from offset 0.
		// Fetch oldest-first: the default id-DESC order would put an entry that
		// arrived after the count FIRST in the page, displacing an older entry
		// while the deletion count stays within the confirmed cap. Ascending
		// order keeps post-count arrivals out of the confirmed window.
		$entries = \GFAPI::get_entries( $form_id, $search_criteria, array( 'key' => 'id', 'direction' => 'ASC', 'is_numeric' => true ), $paging );

		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		foreach ( $entries as $entry ) {
			$entry_id = absint( $entry['id'] );

			if ( $force ) {
				$result = \GFAPI::delete_entry( $entry_id );
			} else {
				$result = \GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
			}

			if ( is_wp_error( $result ) || ( ! $force && false === $result ) ) {
				$error_message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Failed to move entry to trash.', 'gravityforms' );

				return new \WP_Error(
					'partial_delete_failure',
					sprintf(
						/* translators: 1: count of affected entries, 2: entry ID that failed, 3: error message */
						__( 'Processed %1$d entries before failing on entry %2$d: %3$s', 'gravityforms' ),
						$affected_count,
						$entry_id,
						$error_message
					)
				);
			}

			++$affected_count;
		}

		$remaining = max( 0, $total_before - $affected_count );

		return array(
			'success'       => true,
			'deleted_count' => $affected_count,
			'trashed'       => ! $force,
			'remaining'     => $remaining,
			'capped'        => $remaining > 0,
			'cap'           => self::MAX_BULK_DELETE_PER_CALL,
		);
	}

	/**
	 * Strip null and empty metadata from an entry to reduce response size.
	 *
	 * GFAPI entry arrays include payment fields (payment_status, payment_date, etc.)
	 * and other metadata that are null for non-payment forms. This removes them.
	 *
	 * @since 3.1.0
	 *
	 * @param array $entry The entry array.
	 *
	 * @return array Slimmed entry data.
	 */
	private static function slim_entry( $entry ) {
		$stripped = array();

		foreach ( $entry as $key => $value ) {
			// Strip null values (payment fields, post_id, etc. when not applicable).
			if ( null === $value ) {
				continue;
			}

			$stripped[ $key ] = $value;
		}

		return $stripped;
	}

	/**
	 * Get the field IDs hidden from entry read abilities for a form.
	 *
	 * @since 3.1.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string[] Hidden field IDs as strings (e.g. '4', '7.1').
	 */
	private static function get_hidden_field_ids( $form_id ) {
		static $cache = array();

		$form_id = (int) $form_id;

		if ( ! isset( $cache[ $form_id ] ) ) {
			/**
			 * Filters the field IDs hidden from the entry read abilities.
			 *
			 * Hidden fields are redacted from entries-get and entries-search
			 * responses (including their sub-inputs), and search or sort criteria
			 * referencing them are rejected so values cannot be inferred.
			 *
			 * @since 3.1.0
			 *
			 * @param string[] $field_ids Field IDs to hide. A whole-field ID ('4') also hides its sub-inputs ('4.3'); an input ID ('7.1') hides only that input.
			 * @param int      $form_id   The form ID the entry or search targets.
			 *
			 * @example
			 * // Hide fields flagged for personal-data erasure from agents:
			 * add_filter( 'gform_ability_hidden_field_ids', function ( $ids, $form_id ) {
			 *     $form = GFAPI::get_form( $form_id );
			 *     foreach ( (array) rgar( $form, 'fields' ) as $field ) {
			 *         if ( ! empty( $field->personalDataErase ) ) {
			 *             $ids[] = (string) $field->id;
			 *         }
			 *     }
			 *     return $ids;
			 * }, 10, 2 );
			 */
			$ids = apply_filters( 'gform_ability_hidden_field_ids', array(), $form_id );

			$cache[ $form_id ] = is_array( $ids ) ? array_map( 'strval', $ids ) : array();
		}

		return $cache[ $form_id ];
	}

	/**
	 * Determine whether an entry key refers to a hidden field.
	 *
	 * @since 3.1.0
	 *
	 * @param string   $key    The entry key or filter key (field or input ID).
	 * @param string[] $hidden Hidden field IDs.
	 *
	 * @return bool
	 */
	private static function is_hidden_field_key( $key, $hidden ) {
		$key = (string) $key;

		if ( '' === $key || '0' === $key ) {
			return ! empty( $hidden );
		}

		foreach ( $hidden as $hidden_id ) {
			$hidden_id = (string) $hidden_id;

			if ( $key === $hidden_id
				|| 0 === strpos( $key, $hidden_id . '.' )
				|| 0 === strpos( $hidden_id, $key . '.' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove hidden field values from an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $entry The slimmed entry array.
	 *
	 * @return array The entry without hidden field values.
	 */
	private static function redact_entry( $entry ) {
		$hidden = self::get_hidden_field_ids( rgar( $entry, 'form_id' ) );

		if ( empty( $hidden ) ) {
			return $entry;
		}

		foreach ( array_keys( $entry ) as $key ) {
			if ( self::is_hidden_field_key( $key, $hidden ) ) {
				unset( $entry[ $key ] );
			}
		}

		return $entry;
	}

	/**
	 * Reject search or sort criteria that reference hidden fields.
	 *
	 * Redacting output alone is not enough: field_filters can binary-search a
	 * hidden field's values, and sorting by one leaks relative ordering. Reject
	 * with a clear error so agents self-correct instead of silently receiving
	 * differently-scoped results.
	 *
	 * @since 3.1.0
	 *
	 * @param array      $search_criteria The search criteria (after mode injection).
	 * @param array|null $sorting         The sorting input, if any.
	 * @param int[]      $form_ids        The form IDs the query targets.
	 *
	 * @return \WP_Error|null WP_Error when a hidden field is referenced, null otherwise.
	 */
	private static function reject_hidden_field_criteria( $search_criteria, $sorting, $form_ids ) {
		$hidden = array();

		foreach ( (array) $form_ids as $form_id ) {
			$hidden = array_merge( $hidden, self::get_hidden_field_ids( $form_id ) );
		}

		if ( empty( $hidden ) ) {
			return null;
		}

		$keys = array();

		if ( ! empty( $search_criteria['field_filters'] ) && is_array( $search_criteria['field_filters'] ) ) {
			foreach ( $search_criteria['field_filters'] as $filter_key => $filter ) {
				if ( 'mode' === $filter_key || ! is_array( $filter ) ) {
					continue;
				}

				$keys[] = (string) rgar( $filter, 'key' );
			}
		}

		if ( is_array( $sorting ) && isset( $sorting['key'] ) ) {
			$keys[] = (string) $sorting['key'];
		}

		foreach ( $keys as $key ) {
			if ( self::is_hidden_field_key( $key, $hidden ) ) {
				return new \WP_Error(
					'gf_ability_hidden_field',
					sprintf(
						/* translators: %s: field ID */
						__( 'Field %s is hidden from entry abilities on this site and cannot be used in search or sort criteria.', 'gravityforms' ),
						$key
					)
				);
			}
		}

		return null;
	}

	/**
	 * Inject field_filters_mode into field_filters array for GFAPI.
	 *
	 * The abilities schema exposes field_filters_mode as a top-level search_criteria
	 * property (values: 'all' or 'any'). GFAPI expects this as a mixed associative
	 * key inside the field_filters array: $search_criteria['field_filters']['mode'].
	 *
	 * @since 3.1.0
	 *
	 * @param array $search_criteria The search criteria array.
	 *
	 * @return array Modified search criteria with mode injected.
	 */
	private static function inject_field_filters_mode( $search_criteria ) {
		if ( empty( $search_criteria['field_filters_mode'] ) || empty( $search_criteria['field_filters'] ) ) {
			return $search_criteria;
		}

		$search_criteria['field_filters']['mode'] = $search_criteria['field_filters_mode'];
		unset( $search_criteria['field_filters_mode'] );

		return $search_criteria;
	}

	/**
	 * Normalize entry values before create/update operations.
	 *
	 * @since 3.1.0
	 *
	 * @param array $entry          The entry payload to normalize.
	 * @param array $provided_entry The original entry payload provided by the caller.
	 *
	 * @return array|\WP_Error
	 */
	private static function normalize_entry_values( $entry, $provided_entry ) {
		$form_id = absint( rgar( $entry, 'form_id' ) );

		if ( empty( $form_id ) ) {
			return $entry;
		}

		$form = \GFAPI::get_form( $form_id );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		foreach ( rgar( $form, 'fields', array() ) as $field ) {
			if ( 'date' !== rgar( $field, 'type' ) ) {
				continue;
			}

			$field_key = self::get_entry_field_key( $provided_entry, $field->id );

			if ( null === $field_key ) {
				continue;
			}

			$normalized_value = self::normalize_date_field_value( $field, rgar( $entry, $field_key ) );

			if ( is_wp_error( $normalized_value ) ) {
				return $normalized_value;
			}

			$entry[ $field_key ] = $normalized_value;
		}

		return $entry;
	}

	/**
	 * Get the requested entry statuses with 'trash' removed.
	 *
	 * Used by bulk trash mode to constrain the working query to entries that
	 * can actually be moved to trash.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array|null $status The status value from the search criteria, if any.
	 *
	 * @return string[] Statuses to query, excluding 'trash'. Empty if the caller requested trash only.
	 */
	private static function get_non_trash_statuses( $status ) {
		if ( empty( $status ) ) {
			$status = array( 'active', 'spam' );
		}

		return array_values( array_diff( (array) $status, array( 'trash' ) ) );
	}

	/**
	 * Determine the key used for a field value in the entry payload.
	 *
	 * @since 3.1.0
	 *
	 * @param array $entry    The entry payload.
	 * @param int   $field_id The field ID.
	 *
	 * @return int|string|null
	 */
	private static function get_entry_field_key( $entry, $field_id ) {
		if ( array_key_exists( (string) $field_id, $entry ) ) {
			return (string) $field_id;
		}

		if ( array_key_exists( (int) $field_id, $entry ) ) {
			return (int) $field_id;
		}

		return null;
	}

	/**
	 * Normalize date field values to the format configured on the form.
	 *
	 * @since 3.1.0
	 *
	 * @param \GF_Field $field The date field.
	 * @param mixed      $value The submitted value.
	 *
	 * @return mixed|\WP_Error
	 */
	private static function normalize_date_field_value( $field, $value ) {
		if ( '' === $value || null === $value || is_array( $value ) ) {
			return $value;
		}

		$date_format = rgar( $field, 'dateFormat', 'mdy' );
		$date_info   = \GFCommon::parse_date( $value, $date_format );

		if ( empty( $date_info ) || ! checkdate( (int) $date_info['month'], (int) $date_info['day'], (int) $date_info['year'] ) ) {
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', trim( (string) $value ), $matches ) && checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
				$date_info = array(
					'year'  => (int) $matches[1],
					'month' => (int) $matches[2],
					'day'   => (int) $matches[3],
				);
			} else {
				return new \WP_Error(
					'gf_ability_invalid_date_value',
					sprintf(
						/* translators: 1: field label, 2: expected date format */
						__( 'Invalid date value for "%1$s". Use the form\'s configured format (%2$s) or ISO YYYY-MM-DD.', 'gravityforms' ),
						rgar( $field, 'label', __( 'Date', 'gravityforms' ) ),
						self::get_date_format_example( $date_format )
					)
				);
			}
		}

		return self::format_date_value( $date_info, $date_format );
	}

	/**
	 * Format a parsed date value for storage consistency.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $date_info   Parsed date info.
	 * @param string $date_format The Gravity Forms date format slug.
	 *
	 * @return string
	 */
	private static function format_date_value( $date_info, $date_format ) {
		switch ( $date_format ) {
			case 'dmy':
				return sprintf( '%02d/%02d/%04d', $date_info['day'], $date_info['month'], $date_info['year'] );
			case 'dmy_dash':
				return sprintf( '%02d-%02d-%04d', $date_info['day'], $date_info['month'], $date_info['year'] );
			case 'dmy_dot':
				return sprintf( '%02d.%02d.%04d', $date_info['day'], $date_info['month'], $date_info['year'] );
			case 'ymd_slash':
				return sprintf( '%04d/%02d/%02d', $date_info['year'], $date_info['month'], $date_info['day'] );
			case 'ymd_dot':
				return sprintf( '%04d.%02d.%02d', $date_info['year'], $date_info['month'], $date_info['day'] );
			case 'ymd_dash':
				return sprintf( '%04d-%02d-%02d', $date_info['year'], $date_info['month'], $date_info['day'] );
			case 'mdy':
			default:
				return sprintf( '%02d/%02d/%04d', $date_info['month'], $date_info['day'], $date_info['year'] );
		}
	}

	/**
	 * Get a human-readable date format example for error messages.
	 *
	 * @since 3.1.0
	 *
	 * @param string $date_format The Gravity Forms date format slug.
	 *
	 * @return string
	 */
	private static function get_date_format_example( $date_format ) {
		$examples = array(
			'mdy'       => 'MM/DD/YYYY',
			'dmy'       => 'DD/MM/YYYY',
			'dmy_dash'  => 'DD-MM-YYYY',
			'dmy_dot'   => 'DD.MM.YYYY',
			'ymd_slash' => 'YYYY/MM/DD',
			'ymd_dash'  => 'YYYY-MM-DD',
			'ymd_dot'   => 'YYYY.MM.DD',
		);

		return rgar( $examples, $date_format, 'MM/DD/YYYY' );
	}
}
