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
		if ( ! \GFAddOn::get_addon_by_slug( $input['addon_slug'] ) ) {
			return new \WP_Error(
				'gf_ability_unknown_addon',
				/* translators: %s: add-on slug */
				sprintf( __( 'No active add-on is registered for the slug "%s".', 'gravityforms' ), (string) $input['addon_slug'] )
			);
		}

		$denied = self::require_addon_capability( $input['addon_slug'] );
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$feed_meta = self::sanitize_feed_meta( $input['feed_meta'] );
		$feed_id   = \GFAPI::add_feed( absint( $input['form_id'] ), $feed_meta, $input['addon_slug'] );

		if ( is_wp_error( $feed_id ) ) {
			return $feed_id;
		}

		return array( 'feed_id' => (int) $feed_id );
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
			return $existing;
		}

		$denied = self::require_addon_capability( rgar( $existing, 'addon_slug' ) );
		if ( is_wp_error( $denied ) ) {
			return $denied;
		}

		$result = \GFAPI::update_feed(
			absint( $input['feed_id'] ),
			self::sanitize_feed_meta( $input['feed_meta'] ),
			isset( $input['form_id'] ) ? absint( $input['form_id'] ) : null
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => (bool) $result );
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
			return $existing;
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
