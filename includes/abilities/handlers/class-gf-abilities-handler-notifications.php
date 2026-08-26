<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles notification ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Notifications {

	/**
	 * List notifications for a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function list_notifications( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$notifications = rgar( $form, 'notifications', array() );

		return array_values( is_array( $notifications ) ? $notifications : array() );
	}

	/**
	 * Send notifications for an entry.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function send_notifications( $input ) {
		if ( ! \GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return new \WP_Error(
				'gf_ability_forbidden',
				__( 'Sending notifications requires the gravityforms_view_entries capability.', 'gravityforms' )
			);
		}

		$form = \GFAPI::get_form( absint( $input['form_id'] ) );
		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$entry = \GFAPI::get_entry( absint( $input['entry_id'] ) );
		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		if ( (int) rgar( $entry, 'form_id' ) !== absint( $input['form_id'] ) ) {
			return new \WP_Error( 'gf_ability_form_entry_mismatch', __( 'Entry does not belong to the specified form.', 'gravityforms' ) );
		}

		$notification_ids = isset( $input['notification_ids'] ) && is_array( $input['notification_ids'] ) ? array_values( $input['notification_ids'] ) : array();
		$notifications    = rgar( $form, 'notifications', array() );

		if ( ! empty( $notification_ids ) && is_array( $notifications ) ) {
			$notifications         = array_intersect_key( $notifications, array_flip( $notification_ids ) );
			$form['notifications'] = $notifications;
		}

		$sent = \GFAPI::send_notifications( $form, $entry );

		return array( 'sent' => array_values( is_array( $sent ) ? $sent : array() ) );
	}

	/**
	 * Load a form and its notifications array.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $form_id The form ID.
	 *
	 * @return array|\WP_Error array( $form, $notifications ) or a WP_Error.
	 */
	private static function load( $form_id ) {
		$form = \GFAPI::get_form( absint( $form_id ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$notifications = rgar( $form, 'notifications', array() );

		return array( $form, is_array( $notifications ) ? $notifications : array() );
	}

	/**
	 * Create a notification on a form.
	 *
	 * Targeted save via GFFormsModel::save_form_notifications() — no full-form
	 * round-trip. Returns the generated notification_id.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function create_notification( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $notifications ) = $loaded;

		$settings       = isset( $input['notification'] ) && is_array( $input['notification'] ) ? $input['notification'] : array();
		$settings['id'] = uniqid();

		$error = self::validate( $settings );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$settings                         = self::sanitize( $settings );
		$notifications[ $settings['id'] ] = $settings;

		\GFFormsModel::save_form_notifications( absint( $form['id'] ), $notifications );

		return array(
			'success'         => true,
			'form_id'         => absint( $form['id'] ),
			'notification_id' => $settings['id'],
			'notification'    => $settings,
		);
	}

	/**
	 * Update one notification (partial merge by ID).
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function update_notification( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $notifications ) = $loaded;

		$id = (string) rgar( $input, 'notification_id' );

		if ( ! isset( $notifications[ $id ] ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Notification not found on this form.', 'gravityforms' ) );
		}

		$settings = isset( $input['notification'] ) && is_array( $input['notification'] ) ? $input['notification'] : array();
		unset( $settings['id'] );

		$merged       = array_merge( $notifications[ $id ], $settings );
		$merged['id'] = $id;

		$error = self::validate( $merged );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$merged               = self::sanitize( $merged );
		$notifications[ $id ] = $merged;

		\GFFormsModel::save_form_notifications( absint( $form['id'] ), $notifications );

		return array(
			'success'         => true,
			'form_id'         => absint( $form['id'] ),
			'notification_id' => $id,
			'notification'    => $merged,
		);
	}

	/**
	 * Delete one notification.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function delete_notification( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $notifications ) = $loaded;

		$id = (string) rgar( $input, 'notification_id' );

		if ( ! isset( $notifications[ $id ] ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Notification not found on this form.', 'gravityforms' ) );
		}

		unset( $notifications[ $id ] );

		\GFFormsModel::save_form_notifications( absint( $form['id'] ), $notifications );

		return array(
			'success'                 => true,
			'form_id'                 => absint( $form['id'] ),
			'deleted_notification_id' => $id,
		);
	}

	/**
	 * Apply the admin editor's sanitization to a notification's content.
	 *
	 * This mirrors the Settings-framework sanitizing that the abilities write path skips. It is a no-
	 * op for users who hold unfiltered_html.
	 *
	 * @since 3.1.0
	 *
	 * @param array $notification The notification settings.
	 *
	 * @return array
	 */
	public static function sanitize( $notification ) {
		foreach ( array( 'name', 'subject', 'from', 'fromName', 'replyTo', 'to', 'bcc', 'cc' ) as $key ) {
			if ( isset( $notification[ $key ] ) && is_string( $notification[ $key ] ) ) {
				$notification[ $key ] = sanitize_text_field( $notification[ $key ] );
			}
		}

		if ( isset( $notification['message'] ) && is_string( $notification['message'] ) ) {
			$notification['message'] = \GFCommon::maybe_wp_kses( $notification['message'] );
		}

		if ( isset( $notification['conditionalLogic'] ) && is_array( $notification['conditionalLogic'] ) ) {
			$notification['conditionalLogic'] = \GFFormsModel::sanitize_conditional_logic( $notification['conditionalLogic'] );
		}

		if ( isset( $notification['routing'] ) && is_array( $notification['routing'] ) ) {
			$routing = \GFFormsModel::sanitize_conditional_logic( array( 'rules' => $notification['routing'] ) );

			$notification['routing'] = rgar( $routing, 'rules', array() );
		}

		return $notification;
	}

	/**
	 * Validate a notification's required fields for a functioning email.
	 *
	 * @since 3.1.0
	 *
	 * @param array $notification The notification to validate.
	 *
	 * @return true|\WP_Error
	 */
	private static function validate( $notification ) {
		$required = array(
			'name'    => __( 'a name', 'gravityforms' ),
			'event'   => __( 'an event (e.g. form_submission)', 'gravityforms' ),
			'to'      => __( 'a recipient (to)', 'gravityforms' ),
			'subject' => __( 'a subject', 'gravityforms' ),
			'message' => __( 'a message', 'gravityforms' ),
		);

		$missing = array();
		foreach ( $required as $key => $label ) {
			if ( '' === (string) rgar( $notification, $key ) ) {
				$missing[] = $label;
			}
		}

		if ( ! empty( $missing ) ) {
			return new \WP_Error(
				'gf_ability_invalid_notification',
				sprintf(
					/* translators: %s: the list of missing notification fields */
					__( 'A notification requires %s.', 'gravityforms' ),
					implode( ', ', $missing )
				)
			);
		}

		return true;
	}
}
