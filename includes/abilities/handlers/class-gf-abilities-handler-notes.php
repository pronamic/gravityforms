<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles note ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Notes {

	/**
	 * Require the entry-view capability that the admin notes UI sits behind.
	 *
	 * Notes carry entry data, so the note capabilities alone must not grant access to entries the
	 * role cannot open in wp-admin.
	 *
	 * @since 3.1.0
	 *
	 * @return \WP_Error|null
	 */
	private static function require_entry_access() {
		if ( ! \GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return new \WP_Error(
				'gf_ability_forbidden',
				__( 'Accessing entry notes requires the gravityforms_view_entries capability.', 'gravityforms' )
			);
		}

		return null;
	}

	/**
	 * List notes for an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array
	 */
	public static function list_notes( $input ) {
		$denied = self::require_entry_access();
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$notes = \GFAPI::get_notes( array( 'entry_id' => absint( $input['entry_id'] ) ) );

		if ( false === $notes ) {
			return array();
		}

		// Normalize raw DB rows to the declared output schema — IDs come back from
		// the DB as strings, but MCP clients strictly validate structuredContent
		// against the integer schema types.
		return array_map(
			function ( $note ) {
				return array(
					'id'           => (int) rgobj( $note, 'id' ),
					'user_id'      => (int) rgobj( $note, 'user_id' ),
					'user_name'    => (string) rgobj( $note, 'user_name' ),
					'date_created' => (string) rgobj( $note, 'date_created' ),
					'value'        => (string) rgobj( $note, 'value' ),
					'note_type'    => (string) rgobj( $note, 'note_type' ),
				);
			},
			array_values( $notes )
		);
	}

	/**
	 * Add a note to an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function add_note( $input ) {
		$denied = self::require_entry_access();
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$entry = \GFAPI::get_entry( absint( $input['entry_id'] ) );
		if ( is_wp_error( $entry ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Entry not found.', 'gravityforms' ) );
		}

		$current_user = wp_get_current_user();
		$note_id      = \GFAPI::add_note(
			absint( $input['entry_id'] ),
			(int) $current_user->ID,
			$current_user->exists() ? $current_user->display_name : '',
			$input['note'],
			isset( $input['note_type'] ) ? $input['note_type'] : 'note'
		);

		if ( is_wp_error( $note_id ) ) {
			return $note_id;
		}

		return array( 'note_id' => (int) $note_id );
	}
}
