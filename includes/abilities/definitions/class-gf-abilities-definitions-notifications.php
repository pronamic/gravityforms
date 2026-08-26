<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines notification abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Notifications {

	/**
	 * Get notification ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/notifications-list',
				'args' => array(
					'label'            => __( 'List Notifications', 'gravityforms' ),
					'description'      => __( 'Returns all notification configurations for a form, including recipients, subject, message body, and conditional logic settings.', 'gravityforms' ),
					'summary'          => __( 'View a form\'s notification (email) settings.', 'gravityforms' ),
					'category'         => 'gravityforms-notifications',
					'execute_callback' => array( GF_Abilities_Handler_Notifications::class, 'list_notifications' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => GF_Ability_Schemas::single_id_input_schema( 'form_id', __( 'The ID of the form to list notifications for.', 'gravityforms' ) ),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'               => array( 'type' => 'string' ),
								'name'             => array( 'type' => 'string', 'description' => __( 'Notification name.', 'gravityforms' ) ),
								'event'            => array( 'type' => 'string', 'description' => __( 'Trigger event (e.g., \'form_submission\').', 'gravityforms' ) ),
								'to'               => array( 'type' => 'string', 'description' => __( 'Recipient email(s).', 'gravityforms' ) ),
								'toType'           => array( 'type' => 'string' ),
								'subject'          => array( 'type' => 'string' ),
								'message'          => array( 'type' => 'string' ),
								'isActive'         => array( 'type' => 'boolean' ),
								'conditionalLogic' => array( 'type' => array( 'object', 'null' ) ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/notifications-send',
				'args' => array(
					'label'            => __( 'Send Notifications', 'gravityforms' ),
					'description'      => __( 'Triggers notification sending for an existing entry. Useful for re-sending failed notifications or triggering manual notification sends.', 'gravityforms' ),
					'summary'          => __( 'Send a submission\'s notification emails.', 'gravityforms' ),
					'category'         => 'gravityforms-notifications',
					'execute_callback' => array( GF_Abilities_Handler_Notifications::class, 'send_notifications' ),
					'capability'       => 'gravityforms_edit_entries',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'entry_id' ),
						'properties'           => array(
							'form_id'          => array( 'type' => 'integer', 'description' => __( 'The ID of the form whose notifications to send.', 'gravityforms' ) ),
							'entry_id'         => array( 'type' => 'integer', 'description' => __( 'The ID of the entry to send notifications for.', 'gravityforms' ) ),
							'notification_ids' => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
								'description' => __( 'Optional. Specific notification IDs to send. If omitted, all active notifications for the form\'s submission event are sent.', 'gravityforms' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'sent' => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
								'description' => __( 'IDs of notifications that were sent.', 'gravityforms' ),
							),
						),
					),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/notifications-create',
				'args' => array(
					'label'            => __( 'Create Notification', 'gravityforms' ),
					'description'      => __( 'Adds a notification (email) to a form. Requires name, event (e.g. form_submission), to (a recipient email, or a field/routing value with toType), subject, and message (merge tags + HTML supported). Add conditionalLogic to send only on matching submissions. Returns the generated notification_id. This is the canonical way to add a notification — prefer it over forms-update.', 'gravityforms' ),
					'summary'          => __( 'Add a notification (email) to a form.', 'gravityforms' ),
					'category'         => 'gravityforms-notifications',
					'execute_callback' => array( GF_Abilities_Handler_Notifications::class, 'create_notification' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'notification' ),
						'properties'           => array(
							'form_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the form to add the notification to.', 'gravityforms' ) ),
							'notification' => self::notification_input_schema(),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => self::mutation_output_schema(),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/notifications-update',
				'args' => array(
					'label'            => __( 'Update Notification', 'gravityforms' ),
					'description'      => __( 'Partially updates one notification by ID (only supplied keys change; the id is immutable). The resulting notification must still have name, event, to, subject, and message. Call gravityforms/notifications-list first to get the notification_id. This is the canonical way to edit a notification — prefer it over forms-update.', 'gravityforms' ),
					'summary'          => __( 'Modify an existing notification.', 'gravityforms' ),
					'category'         => 'gravityforms-notifications',
					'execute_callback' => array( GF_Abilities_Handler_Notifications::class, 'update_notification' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'notification_id', 'notification' ),
						'properties'           => array(
							'form_id'         => array( 'type' => 'integer', 'description' => __( 'The ID of the form.', 'gravityforms' ) ),
							'notification_id' => array( 'type' => 'string', 'description' => __( 'The ID of the notification to update.', 'gravityforms' ) ),
							'notification'    => self::notification_input_schema(),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => self::mutation_output_schema(),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/notifications-delete',
				'args' => array(
					'label'            => __( 'Delete Notification', 'gravityforms' ),
					'description'      => __( 'Permanently removes one notification by ID. Call gravityforms/notifications-list first to get the notification_id.', 'gravityforms' ),
					'summary'          => __( 'Delete a notification from a form.', 'gravityforms' ),
					'category'         => 'gravityforms-notifications',
					'execute_callback' => array( GF_Abilities_Handler_Notifications::class, 'delete_notification' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'notification_id' ),
						'properties'           => array(
							'form_id'         => array( 'type' => 'integer', 'description' => __( 'The ID of the form.', 'gravityforms' ) ),
							'notification_id' => array( 'type' => 'string', 'description' => __( 'The ID of the notification to delete.', 'gravityforms' ) ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'success'                 => array( 'type' => 'boolean' ),
							'form_id'                 => array( 'type' => 'integer' ),
							'deleted_notification_id' => array( 'type' => 'string' ),
						),
					),
					'destructive'      => true,
				),
			),
		);
	}

	/**
	 * Schema for the notification settings a write accepts.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private static function notification_input_schema() {
		return array(
			'type'        => 'object',
			'description' => __( 'Notification settings.', 'gravityforms' ),
			'properties'  => array(
				'name'             => array( 'type' => 'string', 'description' => __( 'Admin-facing name.', 'gravityforms' ) ),
				'event'            => array( 'type' => 'string', 'description' => __( 'Trigger event, e.g. form_submission.', 'gravityforms' ) ),
				'to'               => array( 'type' => 'string', 'description' => __( 'Recipient: an email address, or a field ID / routing per toType.', 'gravityforms' ) ),
				'toType'           => array( 'type' => 'string', 'enum' => array( 'email', 'field', 'routing', 'hidden' ), 'description' => __( 'How "to" is interpreted. Default email.', 'gravityforms' ) ),
				'from'             => array( 'type' => 'string' ),
				'fromName'         => array( 'type' => 'string' ),
				'replyTo'          => array( 'type' => 'string' ),
				'bcc'              => array( 'type' => 'string' ),
				'subject'          => array( 'type' => 'string' ),
				'message'          => array( 'type' => 'string', 'description' => __( 'Email body. Merge tags and HTML supported.', 'gravityforms' ) ),
				'isActive'         => array( 'type' => 'boolean' ),
				'conditionalLogic' => array( 'type' => array( 'object', 'null' ), 'description' => __( 'Optional GF conditional-logic object gating when this notification sends.', 'gravityforms' ) ),
			),
		);
	}

	/**
	 * Shared write output schema (success + the saved notification).
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
				'notification_id' => array( 'type' => 'string' ),
				'notification'    => array( 'type' => 'object' ),
			),
		);
	}
}
