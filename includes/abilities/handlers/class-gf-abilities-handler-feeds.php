<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles feed ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Feeds {

	/**
	 * Check that the current user holds the add-on's own form-settings capability.
	 *
	 * The admin gates every feed operation behind this capability. Mirror it here so edit_forms alone
	 * cannot manage another add-on's feed.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug The add-on slug.
	 *
	 * @return \WP_Error|null WP_Error when the user is not entitled, null otherwise.
	 */
	private static function require_addon_capability( $slug ) {
		if ( ! self::user_can_manage_addon_feeds( $slug ) ) {
			return new \WP_Error(
				'gf_ability_forbidden',
				/* translators: %s: add-on slug */
				sprintf( __( 'You do not have permission to manage %s feeds.', 'gravityforms' ), (string) $slug )
			);
		}

		return null;
	}

	/**
	 * Whether the current user may manage feeds for the given add-on slug.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug The add-on slug.
	 *
	 * @return bool
	 */
	private static function user_can_manage_addon_feeds( $slug ) {
		$addon = \GFAddOn::get_addon_by_slug( $slug );

		$capabilities = $addon ? $addon->get_form_settings_capabilities() : array();

		return (bool) \GFCommon::current_user_can_any( $capabilities );
	}

	/**
	 * Sanitize feed meta fields that reach an admin-rendered sink.
	 *
	 * The feed name is shown in the add-on feed list table, so sanitize it to prevent stored admin
	 * XSS.
	 *
	 * @since 3.1.0
	 *
	 * @param array $feed_meta The caller-supplied feed meta.
	 *
	 * @return array
	 */
	private static function sanitize_feed_meta( $feed_meta ) {
		if ( is_array( $feed_meta ) && isset( $feed_meta['feedName'] ) && is_string( $feed_meta['feedName'] ) ) {
			$feed_meta['feedName'] = sanitize_text_field( $feed_meta['feedName'] );
		}

		return $feed_meta;
	}

	/**
	 * List feeds.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function list_feeds( $input ) {
		// An empty list for a missing form looks like a valid answer; say not found instead.
		if ( ! empty( $input['form_id'] ) && ! \GFAPI::get_form( absint( $input['form_id'] ) ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$feeds = \GFAPI::get_feeds(
			null,
			isset( $input['form_id'] ) ? absint( $input['form_id'] ) : null,
			isset( $input['addon_slug'] ) ? $input['addon_slug'] : null,
			array_key_exists( 'is_active', $input ) ? (bool) $input['is_active'] : true
		);

		// GFAPI::get_feeds() returns a WP_Error when no feeds match; for a list
		// operation that is an empty result, not a failure.
		if ( is_wp_error( $feeds ) || false === $feeds ) {
			return array();
		}

		$entitled = array();
		$feeds    = array_filter(
			$feeds,
			function ( $feed ) use ( &$entitled ) {
				$slug = rgar( $feed, 'addon_slug' );

				if ( ! isset( $entitled[ $slug ] ) ) {
					$entitled[ $slug ] = self::user_can_manage_addon_feeds( $slug );
				}

				return $entitled[ $slug ];
			}
		);

		// Feed meta is object-typed in the output schema; an empty PHP array
		// encodes as [] and fails strict MCP client validation.
		return array_values(
			array_map(
				function ( $feed ) {
					if ( empty( $feed['meta'] ) || ! is_array( $feed['meta'] ) ) {
						$feed['meta'] = (object) array();
					}

					return $feed;
				},
				$feeds
			)
		);
	}

	/**
	 * Create a feed.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function create_feed( $input ) {
		$addon = \GFAddOn::get_addon_by_slug( $input['addon_slug'] );

		// An active add-on without feed support (theme layers, Signature, ...)
		// has no feed table handling; GFAPI::add_feed() would fatal on it.
		if ( ! $addon instanceof \GFFeedAddOn ) {
			return new \WP_Error(
				'gf_ability_unknown_addon',
				/* translators: %s: add-on slug */
				sprintf( __( 'No active feed add-on is registered for the slug "%s". Call gravityforms/system-info for the active add-ons; only add-ons with feeds accept this call.', 'gravityforms' ), (string) $input['addon_slug'] )
			);
		}

		$denied = self::require_addon_capability( $input['addon_slug'] );
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$form_id   = absint( $input['form_id'] );
		$feed_meta = self::sanitize_feed_meta( $input['feed_meta'] );
		$is_active = ! empty( $input['is_active'] );

		if ( $is_active ) {
			$feed_id = \GFAPI::add_feed( $form_id, $feed_meta, $input['addon_slug'] );

			if ( is_wp_error( $feed_id ) ) {
				return $feed_id;
			}

			return array( 'feed_id' => (int) $feed_id, 'is_active' => true );
		}

		// A feed built through the API starts paused unless the caller asks
		// otherwise, so an incomplete setup never processes real submissions.
		// GFAPI::add_feed() can only insert live feeds, so mirror its checks and
		// insert through the add-on, which writes the paused state atomically.
		if ( gf_upgrade()->get_submissions_block() ) {
			return new \WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! \GFAPI::get_form( $form_id ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$feed_id = $addon->insert_feed( $form_id, 0, $feed_meta );

		if ( ! $feed_id ) {
			return new \WP_Error( 'gf_ability_feed_insert_failed', __( 'There was an error while inserting a feed', 'gravityforms' ) );
		}

		/** This action is documented in includes/api.php */
		do_action( 'gform_post_add_feed', $feed_id, $form_id, $feed_meta, $input['addon_slug'] );

		return array( 'feed_id' => (int) $feed_id, 'is_active' => false );
	}

	/**
	 * Update a feed.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function update_feed( $input ) {
		$existing = \GFAPI::get_feed( absint( $input['feed_id'] ) );
		if ( is_wp_error( $existing ) ) {
			// GFAPI answers a missing feed with its own not_found code; other errors (a missing table) pass through.
			return 'not_found' === $existing->get_error_code() ? new \WP_Error( 'gf_ability_not_found', __( 'Feed not found.', 'gravityforms' ) ) : $existing;
		}

		$denied = self::require_addon_capability( rgar( $existing, 'addon_slug' ) );
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$has_meta   = isset( $input['feed_meta'] ) && is_array( $input['feed_meta'] );
		$has_form   = isset( $input['form_id'] );
		$has_active = isset( $input['is_active'] );

		if ( ! $has_meta && ! $has_form && ! $has_active ) {
			return new \WP_Error(
				'gf_ability_missing_parameter',
				__( 'Provide feed_meta, is_active, or form_id to change.', 'gravityforms' )
			);
		}

		// GFAPI::update_feed() only finds active feeds, and feeds created here
		// start paused. update_feed_property() finds any feed and still encrypts
		// meta, so every change goes through it.
		$changes = array();

		if ( $has_meta ) {
			$meta = self::sanitize_feed_meta( $input['feed_meta'] );

			// The property updater rejects an empty array; its JSON form is accepted.
			$changes['meta'] = empty( $meta ) ? '{}' : $meta;
		}

		if ( $has_form ) {
			if ( ! \GFAPI::get_form( absint( $input['form_id'] ) ) ) {
				return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
			}

			$changes['form_id'] = absint( $input['form_id'] );
		}

		if ( $has_active ) {
			$changes['is_active'] = ! empty( $input['is_active'] ) ? 1 : 0;
		}

		foreach ( $changes as $property => $value ) {
			// Returns false when the stored value already matched; only a WP_Error is a failure.
			$result = \GFAPI::update_feed_property( absint( $input['feed_id'] ), $property, $value );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return array( 'success' => true );
	}

	/**
	 * Delete a feed.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function delete_feed( $input ) {
		$feed_id = absint( $input['feed_id'] );

		if ( ( $input['confirmation'] ?? '' ) !== (string) $feed_id ) {
			return new \WP_Error( 'gf_ability_confirmation_mismatch', __( 'Confirmation does not match the feed ID. Please provide the exact feed ID to confirm deletion.', 'gravityforms' ) );
		}

		$existing = \GFAPI::get_feed( $feed_id );
		if ( is_wp_error( $existing ) ) {
			// GFAPI answers a missing feed with its own not_found code; other errors (a missing table) pass through.
			return 'not_found' === $existing->get_error_code() ? new \WP_Error( 'gf_ability_not_found', __( 'Feed not found.', 'gravityforms' ) ) : $existing;
		}

		$denied = self::require_addon_capability( rgar( $existing, 'addon_slug' ) );
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$result = \GFAPI::delete_feed( $feed_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => (bool) $result );
	}
}
