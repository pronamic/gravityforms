<?php

use Gravity_Forms\Gravity_Forms\Settings\Settings;

class_exists( 'GFForms' ) || die();

/**
 * Class GFNotification
 * Handles notifications within Gravity Forms
 */
Class GFNotification {

	use Redirects_On_Save;

	/**
	 * Defines the fields that support notifications.
	 *
	 * @since  Unknown
	 * @access private
	 *
	 * @var array Array of field types.
	 */
	private static $supported_fields = array(
		'checkbox', 'radio', 'select', 'text', 'website', 'textarea', 'email', 'hidden', 'number', 'phone', 'multiselect', 'post_title',
		'post_tags', 'post_custom_field', 'post_content', 'post_excerpt',
	);

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Gets a notification based on a Form Object and a notification ID.
	 *
	 * @since  Unknown
	 * @access private
	 *
	 * @param array $form            The Form Object.
	 * @param int   $notification_id The notification ID.
	 *
	 * @return array The Notification Object.
	 */
	private static function get_notification( $form, $notification_id ) {
		foreach ( $form['notifications'] as $id => $notification ) {
			if ( $id == $notification_id ) {
				return $notification;
			}
		}

		return array();
	}

	/**
	 * Displays the Notification page.
	 *
	 * If the notification ID is passed, the Notification Edit page is displayed.
	 * Otherwise, the Notification List page is displayed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFNotification::notification_edit_page()
	 * @uses GFNotification::notification_list_page()
	 *
	 * @return void
	 */
	public static function notification_page() {
		$form_id         = rgget( 'id' );
		$notification_id = rgget( 'nid' );
		if ( ! rgblank( $notification_id ) ) {
			self::notification_edit_page( $form_id, $notification_id );
		} else {
			self::notification_list_page( $form_id );
		}
	}

	/**
	 * Builds the Notification Edit page.
	 *
	 * @access public
	 *
	 * @used-by GFNotification::notification_page()
	 * @uses    GFFormsModel::get_form_meta()
	 * @uses    GFNotification::get_notification()
	 * @uses    GFNotification::validate_notification
	 * @uses    GFFormsModel::sanitize_conditional_logic()
	 * @uses    GFFormsModel::trim_conditional_logic_values_from_element()
	 * @uses    GFFormsModel::save_form_notifications()
	 * @uses    GFCommon::add_message()
	 * @uses    GFCommon::json_decode()
	 * @uses    GFCommon::add_error_message()
	 * @uses    GFFormSettings::page_header()
	 * @uses    GFNotification::get_notification_ui_settings()
	 * @uses    SCRIPT_DEBUG
	 * @uses    GFFormsModel::get_entry_meta()
	 * @uses    GFFormSettings::output_field_scripts()
	 * @uses    GFFormSettings::page_footer()
	 *
	 * @param int $form_id         The ID of the form that the notification belongs to
	 * @param int $notification_id The ID of the notification being edited
	 *
	 * @return void
	 */
	public static function notification_edit_page( $form_id, $notification_id ) {

		GFFormSettings::page_header( esc_html__( 'Notifications', 'gravityforms' ) );

		if ( ! self::get_settings_renderer() ) {
			self::initialize_settings_renderer();
		}

		// Render settings.
		self::get_settings_renderer()->render();

		GFFormSettings::page_footer();

	}

	/**
	 * Get Notification settings fields.
	 *
	 * @since 2.5
	 *
	 * @param array $notification Notification being edited.
	 * @param array $form        The Form object.
	 *
	 * @return array
	 */
	private static function settings_fields( $notification, $form ) {

		// Get notification events.
		$events = self::get_notification_events( $form );

		// Prepare notification events as choices.
		$events_choices = array();
		foreach ( $events as $name => $label ) {
			$events_choices[] = array(
				'label' => $label,
				'value' => $name,
			);
		}

		// Get notification services.
		$services = self::get_notification_services();

		// Prepare notification services as choices.
		$services_choices = array();
		foreach ( $services as $name => $service_meta ) {
			$services_choices[] = array(
				'label'   => rgar( $service_meta, 'label' ),
				'value'   => $name,
				'icon'    => rgar( $service_meta, 'image' ),
				'onclick' => "jQuery(this).parents('form').submit();",
			);
		}

		/**
		 * Disable the From Email warning.
		 *
		 * @since 2.4.13
		 *
		 * @param bool $disable_from_warning Should the From Email warning be disabled?
		 */
		$disable_from_warning = gf_apply_filters( array( 'gform_notification_disable_from_warning', $form['id'], rgar( $notification, 'id' ) ), false );

		$from_email_warning = '';

		// Prepare From Email warning.
		if ( ! $disable_from_warning && self::get_settings_renderer()->get_value( 'service' ) === 'wordpress' ) {

			// Get From Email address.
			$from_address = self::get_settings_renderer()->get_value( 'from' );

			// Determine if From Email is invalid
			$is_invalid_from_email = ! empty( $from_address ) && ! self::is_valid_notification_email( $from_address );

			// Display warning message if not using an email address containing the site domain or {admin_email}.
			if ( ! $is_invalid_from_email && ! self::is_site_domain_in_from( $from_address ) ) {
				$from_email_warning = sprintf(
					'<div class="alert warning" role="alert" style="">%s</div>',
					sprintf(
						esc_html__( 'Warning! Using a third-party email in the From Email field may prevent your notification from being delivered. It is best to use an email with the same domain as your website. %sMore details in our documentation.%s', 'gravityforms' ),
						'<a href="https://docs.gravityforms.com/troubleshooting-notifications/#use-a-valid-from-address" target="_blank" >',
						'</a>'
					)
				);
			}

		}

		// Prepare To Type field.
		if ( 'hidden' === rgar( $notification, 'toType' ) ) {
			$to_type = array(
				'name'          => 'toType',
				'type'          => 'hidden',
				'default_value' => 'hidden',
			);
		} else {
			$to_type = array(
				'name'          => 'toType',
				'label'         => esc_html__( 'Send To', 'gravityforms' ),
				'tooltip'       => gform_tooltip( 'notification_send_to_email', null, true ),
				'type'          => 'radio',
				'horizontal'    => true,
				'choices'       => array(
					array(
						'label' => esc_html__( 'Enter Email', 'gravityforms' ),
						'value' => 'email',
					),
					array(
						'label' => esc_html__( 'Select a Field', 'gravityforms' ),
						'value' => 'field',
					),
					array(
						'label'   => esc_html__( 'Configure Routing', 'gravityforms' ),
						'value'   => 'routing',
						'tooltip' => gform_tooltip( 'notification_send_to_routing', null, true ),
					),
				),
				'default_value' => 'email',
			);
		}

		$fields = array(
			array(
				'title'  => esc_html__( 'Notifications', 'gravityforms' ),
				'fields' => array(
					array(
						'name'     => 'name',
						'label'    => esc_html__( 'Name', 'gravityforms' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'name'          => 'service',
						'label'         => esc_html__( 'Email Service', 'gravityforms' ),
						'type'          => 'radio',
						'choices'       => $services_choices,
						'default_value' => $services_choices[0]['value'],
						'hidden'        => count( $services_choices ) === 1,
					),
					array(
						'name'    => 'event',
						'label'   => esc_html__( 'Event', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'notification_event', null, true ),
						'type'    => 'select',
						'choices' => $events_choices,
						'hidden'  => count( $events_choices ) === 1,
					),
					$to_type,
					array(
						'name'                => 'toEmail',
						'label'               => esc_html__( 'Send To Email', 'gravityforms' ),
						'type'                => 'text',
						'required'            => true,
						'default_value'       => '{admin_email}',
						'dependency'          => array(
							'live'   => true,
							'fields' => array(
								array(
									'field'  => 'toType',
									'values' => array( 'email' ),
								),
							),
						),
						'validation_callback' => function( $field, $value ) {

							// Determine if valid.
							$is_valid = GFNotification::is_valid_notification_email( $value );

							// Get filter parameters.
							$to_type  = GFNotification::get_settings_renderer()->get_value( 'toType' );
							$to_field = GFNotification::get_settings_renderer()->get_value( 'toField' );

							/**
							 * Allows overriding of the notification destination validation
							 *
							 * @since Unknown
							 *
							 * @param bool   $is_valid                    True if valid. False, otherwise.
							 * @param string $gform_notification_to_type  The type of destination.
							 * @param string $gform_notification_to_email The destination email address, if available.
							 * @param string $gform_notification_to_field The field that is being used for the notification, if available.
							 */
							$is_valid = apply_filters( 'gform_is_valid_notification_to', $is_valid, $to_type, $value, $to_field );

							if ( ! $is_valid ) {
								$field->set_error( __( 'Please enter a valid email address.', 'gravityforms' ) );
							}

						},
					),
					array(
						'name'                => 'toField',
						'label'               => esc_html__( 'Send To Field', 'gravityforms' ),
						'type'                => 'field_select',
						'required'            => true,
						'args'                => array( 'input_types' => array( 'email' ) ),
						'no_choices'          => esc_html__( 'Your form does not have an email field. Add an email field to your form and try again.', 'gravityforms' ),
						'fields_callback'     => array( self::class, 'append_filtered_notification_email_fields' ),
						'dependency'          => array(
							'live'   => true,
							'fields' => array(
								array(
									'field'  => 'toType',
									'values' => array( 'field' ),
								),
							),
						),
						'validation_callback' => function ( $field, $value ) {

							// Get filter parameters.
							$to_type  = GFNotification::get_settings_renderer()->get_value( 'toType' );
							$to_email = GFNotification::get_settings_renderer()->get_value( 'toEmail' );

							/**
							 * Allows overriding of the notification destination validation
							 *
							 * @since Unknown
							 *
							 * @param bool   $is_valid                    True if valid. False, otherwise.
							 * @param string $gform_notification_to_type  The type of destination.
							 * @param string $gform_notification_to_email The destination email address, if available.
							 * @param string $gform_notification_to_field The field that is being used for the notification, if available.
							 */
							$is_valid = apply_filters( 'gform_is_valid_notification_to', ! empty( $value ), $to_type, $to_email, $value );

							if ( ! $is_valid ) {
								$field->set_error( __( 'Please select an Email Address field.', 'gravityforms' ) );
							}

						},
					),
					array(
						'name'       => 'routing',
						'type'       => 'notification_routing',
						'dependency' => array(
							'live'   => true,
							'fields' => array(
								array(
									'field'  => 'toType',
									'values' => array( 'routing' ),
								),
							),
						),
					),
					array(
						'name'    => 'fromName',
						'label'   => esc_html__( 'From Name', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'notification_from_name', null, true ),
						'type'    => 'text',
						'class'   => 'merge-tag-support mt-position-right mt-hide_all_fields',
					),
					array(
						'name'                => 'from',
						'label'               => esc_html__( 'From Email', 'gravityforms' ),
						'tooltip'             => gform_tooltip( 'notification_from_email', null, true ),
						'type'                => 'text',
						'class'               => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'default_value'       => '{admin_email}',
						'after_input'         => $from_email_warning,
						'validation_callback' => function( $field, $value ) {
							if ( ! empty( $value ) && ! GFNotification::is_valid_notification_email( $value ) ) {
								$field->set_error( __( 'Please enter a valid email address or merge tag in the From Email field.', 'gravityforms' ) );
							}
						},
					),
					array(
						'name'                => 'replyTo',
						'label'               => esc_html__( 'Reply To', 'gravityforms' ),
						'tooltip'             => gform_tooltip( 'notification_reply_to', null, true ),
						'type'                => 'text',
						'class'               => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'validation_callback' => function( $field, $value ) {
							if ( ! empty( $value ) && ! GFNotification::is_valid_notification_email( $value ) ) {
								$field->set_error( __( 'Please enter a valid email address or merge tag in the Reply To field.', 'gravityforms' ) );
							}
						},
					),
					array(
						'name'                => 'cc',
						'label'               => esc_html__( 'CC', 'gravityforms' ),
						'tooltip'             => gform_tooltip( 'notification_cc', null, true ),
						'type'                => 'text',
						'class'               => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'dependency'          => function() use ( $form, $notification ) {

							/**
							 * Enable the CC Notification field.
							 *
							 * @since 2.3
							 *
							 * @param bool  $enable_cc     Should the CC field be enabled?
							 * @param array $notification The current notification object.
							 * @param array $from         The current form object.
							 */
							return gf_apply_filters( array( 'gform_notification_enable_cc', $form['id'], rgar( $notification, 'id' ) ), false, $notification, $form );

						},
						'validation_callback' => function( $field, $value ) {
							if ( ! empty( $value ) && ! GFNotification::is_valid_notification_email( $value ) ) {
								$field->set_error( __( 'Please enter a valid email address or merge tag in the CC field.', 'gravityforms' ) );
							}
						},
					),
					array(
						'name'                => 'bcc',
						'label'               => esc_html__( 'BCC', 'gravityforms' ),
						'tooltip'             => gform_tooltip( 'notification_bcc', null, true ),
						'type'                => 'text',
						'class'               => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'validation_callback' => function( $field, $value ) {
							if ( ! empty( $value ) && ! GFNotification::is_valid_notification_email( $value ) ) {
								$field->set_error( __( 'Please enter a valid email address or merge tag in the BCC field.', 'gravityforms' ) );
							}
						},
					),
					array(
						'name'     => 'subject',
						'label'    => esc_html__( 'Subject', 'gravityforms' ),
						'type'     => 'text',
						'class'    => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'required' => true,
					),
					array(
						'name'       => 'message',
						'label'      => esc_html__( 'Message', 'gravityforms' ),
						'type'       => 'textarea',
						'use_editor' => true,
						'required'   => true,
					),
					array(
						'name'    => 'disableAutoformat',
						'label'   => esc_html__( 'Auto-formatting', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'notification_autoformat', null, true ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'name'  => 'disableAutoformat',
								'label' => esc_html__( 'Disable auto-formatting', 'gravityforms' ),
							),
						),
					),
					array(
						'name'    => 'enableAttachments',
						'label'   => esc_html__( 'Attachments', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'notification_attachments', null, true ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'name'  => 'enableAttachments',
								'label' => esc_html__( 'Attach uploaded fields to notification', 'gravityforms' ),
							),
						),
						'dependency' => function() use ( $form ) {
							$upload_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );
							return ! empty( $upload_fields );
						}
					),
					array(
						'name'        => 'conditionalLogic',
						'label'       => esc_html__( 'Conditional Logic', 'gravityforms ' ),
						'type'        => 'conditional_logic',
						'object_type' => 'notification',
						'checkbox'    => array(
							'label'  => esc_html__( 'Enable conditional logic', 'gravityforms' ),
							'hidden' => false,
						),
					),
					array(
						'type'  => 'save',
						'value' => esc_html__( 'Update Notification', 'gravityforms' ),
					),
				),
			),
		);

		// Append registered legacy settings to the fields array.
		$fields = self::append_legacy_settings_fields( $fields, $notification, $form );

		/**
		 * Filters the Notification settings fields before they are displayed.
		 *
		 * @since 2.5
		 *
		 * @param array $fields Form settings fields.
		 * @param array $form   Form Object.
		 */
		$fields = gf_apply_filters( array( 'gform_notification_settings_fields', $form['id'] ), $fields, $notification, $form );

		return $fields;

	}

	/**
	 * Pass the field choices for the select field through the gform_email_fields_notification_admin filter to allow
	 * third-parties to add or remove arbitrary fields.
	 *
	 * @since 2.5.7
	 *
	 * @param array $fields The form fields to be used as choices.
	 * @param array $form   The form belonging to the notification being configured.
	 *
	 * @return array
	 */
	public static function append_filtered_notification_email_fields( $fields, $form ) {
		return gf_apply_filters( array( 'gform_email_fields_notification_admin', $form['id'] ), $fields, $form );
	}

	/**
	 * Appends any legacy settings fields to the fields array, if they exist.
	 *
	 * @since 2.5.6
	 *
	 * @param array $fields       Array of settings fields.
	 * @param array $notification The notification being edited.
	 * @param array $form         The form being edited.
	 *
	 * @return array
	 */
	private static function append_legacy_settings_fields( $fields, $notification, $form ) {
		/**
		 * Add new or modify existing notification settings that display on the Notification Edit screen.
		 *
		 * @deprecated
		 * @since 1.7
		 *
		 * @param array $ui_settings  An array of settings for the notification UI.
		 * @param array $notification The current notification object being edited.
		 * @param array $form         The current form object to which the notification being edited belongs.
		 * @param null  $is_valid     Whether or not the current notification has passed validation. (Deprecated.)
		 */
		$legacy_settings = apply_filters( 'gform_notification_ui_settings', array(), $notification, $form, null );

		if ( empty( $legacy_settings ) ) {
			return $fields;
		}

		// Add the Legacy Settings section.
		$fields[] = array(
			'title'  => esc_html__( 'Legacy Settings', 'gravityforms' ),
			'class'  => 'gform-settings-panel--full',
			'fields' => array(
				array(
					'name' => 'legacy',
					'type' => 'html',
					'html' => function() use ( $legacy_settings ) {
						$html = '<table class="gforms_form_settings" cellspacing="0" cellpadding="0" width="100%">';

						foreach ( $legacy_settings as $title => $legacy_fields ) {
							$html .= sprintf(
								'<tr><td colspan="2"><h4 class="gf_settings_subgroup_title">%s</h4></td>',
								esc_html( $title )
							);

							switch ( $legacy_fields ) {
								case is_string( $legacy_fields ):
									$html .= $legacy_fields;
									break;
								case is_array( $legacy_fields ):
									foreach ( $legacy_fields as $field ) {
										$html .= $field;
									}
									break;
							}
						}

						return $html . '</table>';
					},
				),
			),
		);

		return $fields;
	}

	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Initialize Plugin Settings fields renderer.
	 *
	 * @since 2.5
	 */
	public static function initialize_settings_renderer() {

		if ( ! class_exists( 'GFFormSettings' ) ) {
			require_once( GFCommon::get_base_path() . '/form_settings.php' );
		}

		$form_id         = rgget( 'id' );
		$notification_id = rgget( 'nid' );

		if ( ! rgempty( 'gform_notification_id' ) ) {
			$notification_id = rgpost( 'gform_notification_id' );
		}

		$form = GFFormsModel::get_form_meta( $form_id );

		/**
		 * Filters the form to be used in the notification page
		 *
		 * @since 1.8.6
		 *
		 * @param array $form            The Form Object
		 * @param int   $notification_id The notification ID
		 */
		$form = gf_apply_filters( array( 'gform_form_notification_page', $form_id ), $form, $notification_id );

		$notification = ! $notification_id ? array() : self::get_notification( $form, $notification_id );

		// Prepare initial values.
		$initial_notification                                          = $notification;
		$initial_notification['toEmail']                               = rgar( $notification, 'toType' ) === 'email' ? rgar( $notification, 'to' ) : '';
		$initial_notification['toField']                               = rgar( $notification, 'toType' ) === 'field' ? rgar( $notification, 'to' ) : '';
		$initial_notification['notification_conditional_logic']        = is_array( rgar( $notification, 'conditionalLogic' ) );
		$initial_notification['notification_conditional_logic_object'] = rgar( $notification, 'conditionalLogic' );

		// Initialize new settings renderer.
		$renderer = new Settings(
			array(
				'initial_values' => $initial_notification,
				'save_callback'  => function( $values ) use ( &$notification, &$form, &$notification_id ) {

					// Determine if new notification.
					$is_new_notification = empty( $notification ) || empty( $notification_id );

					// Set notification ID.
					if ( $is_new_notification ) {
						$notification_id = $notification['id'] = uniqid();
					}

					// Removing legacy (pre-1.7) admin/user notification property.
					unset( $notification['type'] );

					// Save values to the confirmation object in advance so non-custom values will be rewritten when we apply values below.
					$notification = GFFormSettings::save_changed_form_settings_fields( $notification, $values );

					$notification['name']    = rgar( $values, 'name' );
					$notification['service'] = rgar( $values, 'service' );
					$notification['event']   = rgar( $values, 'event' );

					$notification['toType']   = rgar( $values, 'toType' );
					$notification['to']       = rgar( $values, 'toType' ) === 'email' ? rgar( $values, 'toEmail' ) : ( rgar( $values, 'toType' ) === 'field' ? rgar( $values, 'toField' ) : '' );

					$notification['from']     = rgar( $values, 'from' );
					$notification['fromName'] = rgar( $values, 'fromName' );
					$notification['replyTo']  = rgar( $values, 'replyTo' );
					$notification['cc']       = rgar( $values, 'cc' );
					$notification['bcc']      = rgar( $values, 'bcc' );

					$notification['subject'] = rgar( $values, 'subject' );
					$notification['message'] = rgar( $values, 'message' );

					$notification['disableAutoformat'] = (bool) rgar( $values, 'disableAutoformat' );
					$notification['enableAttachments'] = (bool) rgar( $values, 'enableAttachments' );

					// Set the conditional logic object, and clear it if conditional logic is disabled
					$conditionalLogicObject = rgar( $values, 'notification_conditional_logic_object' );
					$notification['conditionalLogic'] = rgar( $values, 'notification_conditional_logic' ) && is_array( $conditionalLogicObject ) ? GFFormsModel::sanitize_conditional_logic( $conditionalLogicObject ) : null;

					if ( isset( $values['routing'] ) && ! empty( $values['routing'] ) ) {
						$routing_logic           = array( 'rules' => $values['routing'] );
						$routing_logic           = GFFormsModel::sanitize_conditional_logic( $routing_logic );
						$notification['routing'] = $routing_logic['rules'];
					}

					$notification = GFCommon::fix_notification_routing( $notification );

					/**
					 * Filters the notification before it is saved
					 *
					 * @since 1.7
					 *
					 * @param array $form                The Form Object.
					 * @param bool  $is_new_notification True if it is a new notification.  False otherwise.
					 *
					 * @param array $notification        The Notification Object.
					 */
					$notification = gf_apply_filters( array(
						'gform_pre_notification_save',
						$form['id'],
					), $notification, $form, $is_new_notification );

					// Save notification.
					$notification                              = GFFormsModel::trim_conditional_logic_values_from_element( $notification, $form );
					$form['notifications'][ $notification_id ] = $notification;
					RGFormsModel::save_form_notifications( $form['id'], $form['notifications'] );

					self::$_saved_item_id = $notification_id;
				},
				'before_fields' => function() use ( &$form, $form_id, &$notification, $notification_id ) {
					?>

					<script type="text/javascript">

						gform.addFilter( 'gform_merge_tags', 'MaybeAddSaveLinkMergeTag' );
						function MaybeAddSaveLinkMergeTag( mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option ) {
							var event = document.getElementById( 'event' ).value;
							if ( event === 'form_saved' || event === 'form_save_email_requested' ) {
								mergeTags['other'].tags.push( {
									tag:  '{save_link}',
									label: <?php echo json_encode( esc_html__( 'Save & Continue Link', 'gravityforms' ) ); ?>
								} );
								mergeTags['other'].tags.push( {
									tag:   '{save_token}',
									label: <?php echo json_encode( esc_html__( 'Save & Continue Token', 'gravityforms' ) ); ?>
								} );
							}
							return mergeTags;
						}

						<?php
						if ( empty( $form['notifications'] ) ) {
							$form['notifications'] = array();
						}

						$entry_meta = GFFormsModel::get_entry_meta( $form_id );
						/**
						 * Filters the entry meta when notification conditional logic is being edited
						 *
						 * @since 1.7.6
						 *
						 * @param array $entry_meta      The Entry meta
						 * @param array $form            The Form Object
						 * @param int   $notification_id The notification ID
						 */
						$entry_meta = apply_filters( 'gform_entry_meta_conditional_logic_notifications', $entry_meta, $form, $notification_id );

						?>

						var form = <?php echo json_encode( $form ) ?>;
						var current_notification = <?php echo GFCommon::json_encode( $notification ) ?>;
						var entry_meta = <?php echo GFCommon::json_encode( $entry_meta ) ?>;

						jQuery( function() {
							ToggleConditionalLogic( true, 'notification' );
						} );

						<?php GFFormSettings::output_field_scripts() ?>

					</script>

					<?php
				},
				'after_fields'   => function() use ( &$notification_id ) {
					printf( '<input type="hidden" id="gform_notification_id" name="gform_notification_id" value="%s" />', esc_attr( $notification_id ) );
				}
			)
		);

		// Save renderer to class.
		self::set_settings_renderer( $renderer );

		// Define settings fields.
		self::get_settings_renderer()->set_fields( self::settings_fields( $notification, $form ) );

		if ( self::is_save_redirect( 'nid' ) ) {
			self::get_settings_renderer()->set_save_message_after_redirect();
		}

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
			self::redirect_after_valid_save( 'nid' );
		}

	}

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|Settings
	 */
	public static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}





	// # NOTIFICATION LIST ---------------------------------------------------------------------------------------------

	/**
	 * Displays the notification list page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFNotification::notification_page()
	 * @uses    GFNotification::maybe_process_notification_list_action()
	 * @uses    GFFormsModel::get_form_meta()
	 * @uses    GFFormSettings::page_header()
	 * @uses    GFNotificationTable::__construct()
	 * @uses    GFNotificationTable::prepare_items()
	 * @uses    GFNotificationTable::display()
	 * @uses    GFFormSettings::page_footer()
	 *
	 * @param int $form_id The form ID to list notifications on.
	 *
	 * @return void
	 */
	public static function notification_list_page( $form_id ) {

		// Handle form actions
		self::maybe_process_notification_list_action();

		$form = RGFormsModel::get_form_meta( $form_id );

		$notification_table = new GFNotificationTable( $form );
		$notification_table->prepare_items();

		GFFormSettings::page_header();
		?>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Notifications', 'gravityforms' ); ?></h4>
			</header>

			<div class="gform-settings-panel__content">

				<form id="notification_list_form" method="post">

					<?php
					$notification_table->display();
					wp_nonce_field( 'gform_notification_list_action', 'gform_notification_list_action' );
					?>

					<input id="action_argument" name="action_argument" type="hidden" />
					<input id="action" name="action" type="hidden" />

				</form>

			</div>

		</div>

		<script type="text/javascript">
			function ToggleActive( btn, notification_id ) {
				var is_active = jQuery( btn ).hasClass( 'gform-status--active' );

				jQuery.ajax(
					{
						url:      '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						method:   'POST',
						dataType: 'json',
						data: {
							action:                        'rg_update_notification_active',
							rg_update_notification_active: '<?php echo wp_create_nonce( 'rg_update_notification_active' ); ?>',
							form_id:                       '<?php echo intval( $form_id ); ?>',
							notification_id:               notification_id,
							is_active:                     is_active ? 0 : 1,
						},
						success:  function() {
							if ( is_active ) {
								setToggleInactive();
							} else {
								setToggleActive();
							}
						},
						error:    function() {
							if ( ! is_active ) {
								setToggleInactive();
							} else {
								setToggleActive();
							}

							alert( '<?php echo esc_js( __( 'Ajax error while updating form', 'gravityforms' ) ); ?>' );
						}
					}
				);

				function setToggleInactive() {
					jQuery( btn ).removeClass( 'gform-status--active' ).addClass( 'gform-status--inactive' ).find( '.gform-status-indicator-status' ).html( <?php echo wp_json_encode( esc_attr__( 'Inactive', 'gravityforms' ) ); ?> );
				}

				function setToggleActive() {
					jQuery( btn ).removeClass( 'gform-status--inactive' ).addClass( 'gform-status--active' ).find( '.gform-status-indicator-status' ).html( <?php echo wp_json_encode( esc_attr__( 'Active', 'gravityforms' ) ); ?> );
				}

			}

		</script>

		<?php
		GFFormSettings::page_footer();
	}

	/**
	 * Processes a notification list action if needed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFNotification::notification_list_page()
	 * @uses    GFNotification::delete_notification()
	 * @uses    GFNotification::duplicate_notification()
	 * @uses    GFCommon::add_message()
	 * @uses    GFCommon::add_error_message()
	 *
	 * @return void
	 */
	public static function maybe_process_notification_list_action() {

		if ( empty( $_POST ) || ! check_admin_referer( 'gform_notification_list_action', 'gform_notification_list_action' ) ) {
			return;
		}

		$action    = rgpost( 'action' );
		$object_id = rgpost( 'action_argument' );

		switch ( $action ) {
			case 'delete':
				$notification_deleted = GFNotification::delete_notification( $object_id, rgget( 'id' ) );
				if ( $notification_deleted ) {
					GFCommon::add_message( esc_html__( 'Notification deleted.', 'gravityforms' ) );
				} else {
					GFCommon::add_error_message( esc_html__( 'There was an issue deleting this notification.', 'gravityforms' ) );
				}
				break;
			case 'duplicate':
				$notification_duplicated = GFNotification::duplicate_notification( $object_id, rgget( 'id' ) );
				if ( $notification_duplicated ) {
					GFCommon::add_message( esc_html__( 'Notification duplicated.', 'gravityforms' ) );
				} else {
					GFCommon::add_error_message( esc_html__( 'There was an issue duplicating this notification.', 'gravityforms' ) );
				}
				break;
		}

	}

	/**
	 * Get list of notification services.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array The notification services available.
	 */
	public static function get_notification_services() {

		$services = array(
			'wordpress' => array(
				'label' => esc_html__( 'WordPress', 'gravityforms' ),
				'image' => admin_url( 'images/wordpress-logo.svg' )
			)
		);

		/**
		 * Filters the list of notification services.
		 *
		 * @since 1.9.16
		 *
		 * @param array $services The services available.
		 */
		return gf_apply_filters( array( 'gform_notification_services' ), $services );

	}

	/**
	 * Get the notification events for the current form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $form The current Form Object.
	 *
	 * @return array Notification events available within the form.
	 */
	public static function get_notification_events( $form ) {
		$notification_events = array( 'form_submission' => esc_html__( 'Form is submitted', 'gravityforms' ) );
		if ( rgars( $form, 'save/enabled' ) ) {
			$notification_events['form_saved']                = esc_html__( 'Form is saved', 'gravityforms' );
			$notification_events['form_save_email_requested'] = esc_html__( 'Save and continue email is requested', 'gravityforms' );
		}

		/**
		 * Allow custom notification events to be added.
		 *
		 * @since Unknown
		 *
		 * @param array $notification_events The notification events.
		 * @param array $form The current form.
		 */
		return apply_filters( 'gform_notification_events', $notification_events, $form );
	}

	/**
	 * Validates email addresses within notifications.
	 *
	 * @since  Unknown
	 * @access private
	 *
	 * @uses GFCommon::is_invalid_or_empty_email()
	 *
	 * @param $text String containing comma-separated email addresses.
	 *
	 * @return bool True if valid. Otherwise, false.
	 */
	public static function is_valid_notification_email( $text ) {
		if ( empty( $text ) ) {
			return false;
		}

		$emails = explode( ',', $text );
		foreach ( $emails as $email ) {
			$email            = trim( $email );
			$invalid_email    = GFCommon::is_invalid_or_empty_email( $email );
			// this used to be more strict; updated to match any merge-tag-like string
			$invalid_variable = ! preg_match( '/^{.+}$/', $email );

			if ( $invalid_email && $invalid_variable ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if notification from email is using the site domain.
	 *
	 * @since  2.4.12
	 *
	 * @param string $from_email Email address to check.
	 *
	 * @return bool
	 */
	private static function is_site_domain_in_from( $from_email ) {

		// If {admin_email} is used check email from WP settings.
		if ( strpos( $from_email, '{admin_email}' ) !== false ) {
			$from_email = get_bloginfo( 'admin_email' );
		}

		return GFCommon::email_domain_matches( $from_email );

	}

	/**
	 * Gets supported routing field types.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFNotification::$supported_fields()
	 *
	 * @return array $field_types Supported field types.
	 */
	public static function get_routing_field_types() {
		/**
		 * Filters the field types supported by notification routing
		 *
		 * @since 1.9.6
		 *
		 * @param array GFNotification::$supported_fields Currently supported field types.
		 */
		$field_types = apply_filters( 'gform_routing_field_types', self::$supported_fields );
		return $field_types;
	}

	/**
	 * Gets a dropdown list of available post categories
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function get_post_category_values() {

		$id       = 'routing_value_' . rgpost( 'ruleIndex' );
		$selected = rgempty( 'selectedValue' ) ? 0 : rgpost( 'selectedValue' );

		$dropdown = wp_dropdown_categories( array( 'class' => 'gfield_routing_select gfield_routing_value_dropdown gfield_category_dropdown', 'orderby' => 'name', 'id' => $id, 'selected' => $selected, 'hierarchical' => true, 'hide_empty' => 0, 'echo' => false ) );
		die( $dropdown );
	}

	/**
	 * Delete a form notification
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFFormsModel::get_form_meta()
	 * @uses GFFormsModel::flush_current_forms()
	 * @uses GFFormsModel::save_form_notifications()
	 *
	 * @param int       $notification_id The notification ID to delete
	 * @param int|array $form_id         Can pass a form ID or a form object
	 *
	 * @return int|false The result from $wpdb->query deletion
	 */
	public static function delete_notification( $notification_id, $form_id ) {

		if ( ! $form_id ) {
			return false;
		}

		$form = ! is_array( $form_id ) ? RGFormsModel::get_form_meta( $form_id ) : $form_id;

		/**
		 * Fires before a notification is deleted.
		 *
		 * @since Unknown
		 *
		 * @param array $form['notifications'][$notification_id] The notification being deleted.
		 * @param array $form                                    The Form Object that the notification is being deleted from.
		 */
		do_action( 'gform_pre_notification_deleted', $form['notifications'][ $notification_id ], $form );

		unset( $form['notifications'][ $notification_id ] );

		// Clear Form cache so next retrieval of form meta will reflect deleted notification
		RGFormsModel::flush_current_forms();

		return RGFormsModel::save_form_notifications( $form['id'], $form['notifications'] );
	}

	/**
	 * Duplicates a form notification.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFFormsModel::get_form_meta()
	 * @uses GFNotification::is_unique_name()
	 * @uses GFFormsModel::flush_current_forms()
	 * @uses GFFormsModel::save_form_notifications()
	 *
	 * @param int       $notification_id The notification ID to duplicate.
	 * @param int|array $form_id         The ID of the form or Form Object that contains the notification.
	 *
	 * @return int|false The result from $wpdb->query after duplication
	 */
	public static function duplicate_notification( $notification_id, $form_id ) {

		if ( ! $form_id ) {
			return false;
		}

		$form = ! is_array( $form_id ) ? RGFormsModel::get_form_meta( $form_id ) : $form_id;

		$new_notification = $form['notifications'][ $notification_id ];
		$name             = rgar( $new_notification, 'name' );
		$new_id           = uniqid();

		$count    = 2;
		$new_name = $name . ' - Copy 1';
		while ( ! self::is_unique_name( $new_name, $form['notifications'] ) ) {
			$new_name = $name . " - Copy $count";
			$count ++;
		}
		$new_notification['name'] = $new_name;
		$new_notification['id']   = $new_id;
		unset( $new_notification['isDefault'] );
		if ( $new_notification['toType'] == 'hidden' ) {
			$new_notification['toType'] = 'email';
		}

		// Removing legacy (pre-1.7) admin/user notification property.
		unset( $new_notification['type'] );

		$new_notification = GFCommon::fix_notification_routing( $new_notification );

		$form['notifications'][ $new_id ] = $new_notification;

		// Clear form cache so next retrieval of form meta will return duplicated notification
		RGFormsModel::flush_current_forms();

		return RGFormsModel::save_form_notifications( $form['id'], $form['notifications'] );
	}

	/**
	 * Checks if a notification name is unique.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $name          The name to check.
	 * @param array  $notifications The notifications to check against.
	 *
	 * @return bool Returns true if unique.  Otherwise, false.
	 */
	public static function is_unique_name( $name, $notifications ) {

		foreach ( $notifications as $notification ) {
			if ( strtolower( rgar( $notification, 'name' ) ) == strtolower( $name ) ) {
				return false;
			}
		}

		return true;
	}

}

// Include WP_List_Table.
require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );

/**
 * Class GFNotificationTable.
 *
 * Extends WP_List_Table to display the notifications list.
 *
 * @uses WP_List_Table
 */
class GFNotificationTable extends WP_List_Table {

	/**
	 * Contains the Form Object.
	 *
	 * Passed when calling the class.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var array
	 */
	public $form;

	/**
	 * Contains the notification events for the form.
	 *
	 * Generated in the constructor based on the passed Form Object.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var array
	 */
	public $notification_events;

	/**
	 * Contains the notification services for the form.
	 *
	 * Generated in the constructor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var array
	 */
	public $notification_services;

	/**
	 * GFNotificationTable constructor.
	 *
	 * Sets required class properties and defines the list table columns.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFNotification::get_notification_events()
	 * @uses GFNotification::get_notification_services()
	 * @uses GFNotificationTable::$form
	 * @uses GFNotificationTable::$notification_events
	 * @uses GFNotificationTable::$notification_services
	 * @uses WP_List_Table::__construct()
	 *
	 * @param array $form The Form Object to use.
	 */
	function __construct( $form ) {

		$this->form                  = $form;
		$this->notification_events   = GFNotification::get_notification_events( $form );
		$this->notification_services = GFNotification::get_notification_services();

		$columns = array(
			'cb'      => '',
			'name'    => esc_html__( 'Name', 'gravityforms' ),
			'subject' => esc_html__( 'Subject', 'gravityforms' ),
		);

		if ( count( $this->notification_events ) > 1 ) {
			$columns['event'] = esc_html__( 'Event', 'gravityforms' );
		}

		if ( count( $this->notification_services ) > 1 ) {
			$columns['service'] = esc_html__( 'Service', 'gravityforms' );
		}

		$this->_column_headers = array(
			$columns,
			array(),
			array( 'name' => array( 'name', false ) ),
			'name',
		);

		parent::__construct();
	}

	/**
	 * Prepares the list items for displaying.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses WP_List_Table::$items
	 * @uses GFNotificationTable::$form
	 *
	 * @return void
	 */
	function prepare_items() {

		$this->items = $this->form['notifications'];

		switch ( rgget( 'orderby' ) ) {

			case 'name':

				// Sort notifications alphabetically.
				usort( $this->items, array( $this, 'sort_notifications' ) );

				// Reverse sort.
				if ( 'desc' === rgget( 'order' ) ) {
					$this->items = array_reverse( $this->items );
				}

				break;

			default:
				break;

		}

	}

	/**
	 * Sort notifications alphabetically.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $a First notification to compare.
	 * @param array $b Second notification to compare.
	 *
	 * @return int
	 */
	function sort_notifications( $a = array(), $b = array() ) {

		return strcasecmp( $a['name'], $b['name'] );

	}

	/**
	 * Displays the list table.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses \WP_List_Table::get_table_classes()
	 * @uses \WP_List_Table::print_column_headers()
	 * @uses \WP_List_Table::display_rows_or_placeholder()
	 *
	 * @return void
	 */
	function display() {
		$singular = rgar( $this->_args, 'singular' );

		$this->display_tablenav( 'top' );
		?>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-list"<?php if ( $singular ) {
				echo " class='list:$singular'";
			} ?>>

			<?php $this->display_rows_or_placeholder(); ?>

			</tbody>
		</table>

	<?php
	}

	/**
	 * Builds the single row content for the list table
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses WP_List_Table::single_row_columns()
	 *
	 * @param object $item The current view.
	 *
	 * @return void
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr id="notification-' . esc_attr( $item['id'] ) . '" ' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Gets the column headers.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by Filter: manage_{$this->screen->id}_columns
	 * @uses    WP_List_Table::$_column_headers
	 *
	 * @return array The column headers.
	 */
	function get_columns() {
		return $this->_column_headers[0];
	}

	/**
	 * Defines the default values in a column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param object $item   The content to display.
	 * @param string $column The column to apply to.
	 *
	 * @return void
	 */
	function column_default( $item, $column ) {
		echo rgar( $item, $column );
	}

	/**
	 * Defines a checkbox column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::get_base_url()
	 *
	 * @param array $item The column data.
	 *
	 * @return void
	 */
	function column_cb( $item ) {
		if ( rgar( $item, 'isDefault' ) ) {
			return;
		}

		$active = rgar( $item, 'isActive' ) !== false;

		if ( $active ) {
			$class = 'gform-status--active';
			$text  = esc_html__( 'Active', 'gravityforms' );
		} else {
			$class = 'gform-status--inactive';
			$text  = esc_html__( 'Inactive', 'gravityforms' );
		}
		?>
		<button type="button" class="gform-status-indicator <?php echo esc_attr( $class ); ?>" onclick="ToggleActive( this, '<?php echo esc_js( $item['id'] ); ?>' );" onkeypress="ToggleActive( this, '<?php echo esc_js( $item['id'] ); ?>' );">
			<svg role="presentation" focusable="false" viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"><circle cx="3" cy="2" r="1" stroke-width="2"/></svg>
			<span class="gform-status-indicator-status"><?php echo esc_html( $text ); ?></span>
		</button>
		<?php
	}

	/**
	 * Sets the column name in the list table.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $item The column data.
	 *
	 * @return void
	 */
	function column_name( $item ) {
		$edit_url = add_query_arg( array( 'nid' => $item['id'] ) );
		/**
		 * Filters the row action links.
		 *
		 * @since Unknown
		 *
		 * @param array $actions The action links.
		 */
		$actions  = apply_filters(
			'gform_notification_actions', array(
				'edit'      => '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'gravityforms' ) . '</a>',
				'duplicate' => '<a href="javascript:void(0);" onclick="javascript: DuplicateNotification(\'' . esc_js( $item['id'] ) . '\');" onkeypress="javascript: DuplicateNotification(\'' . esc_js( $item['id'] ) . '\');" style="cursor:pointer;">' . esc_html__( 'Duplicate', 'gravityforms' ) . '</a>',
				'delete'    => '<a href="javascript:void(0);" class="submitdelete" onclick="javascript: if(confirm(\'' . esc_js( esc_html__( 'WARNING: You are about to delete this notification.', 'gravityforms' ) ) . esc_js( esc_html__( "'Cancel' to stop, 'OK' to delete.", 'gravityforms' ) ) . '\')){ DeleteNotification(\'' . esc_js( $item['id'] ) . '\'); }" onkeypress="javascript: if(confirm(\'' . esc_js( esc_html__( 'WARNING: You are about to delete this notification.', 'gravityforms' ) ) . esc_js( esc_html__( "'Cancel' to stop, 'OK' to delete.", 'gravityforms' ) ) . '\')){ DeleteNotification(\'' . esc_js( $item['id'] ) . '\'); }" style="cursor:pointer;">' . esc_html__( 'Delete', 'gravityforms' ) . '</a>'
			)
		);

		if ( isset( $item['isDefault'] ) && $item['isDefault'] ) {
			unset( $actions['delete'] );
		}

		?>

		<a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( rgar( $item, 'name' ) ); ?></strong></a>
		<div class="row-actions">

			<?php
			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$keys     = array_keys( $actions );
				$last_key = array_pop( $keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key == $last_key ? '' : ' | ';
					?>
					<span class="<?php echo $key; ?>">
                        <?php echo $html . $divider; ?>
                    </span>
				<?php
				}
			}
			?>

		</div>

	<?php
	}

	/**
	 * Displays the content of the Service column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFNotificationTable::$notification_services
	 *
	 * @param array $notification The Notification Object.
	 *
	 * @return void
	 */
	function column_service( $notification ) {

		$services = $this->notification_services;

		if ( ! rgar( $notification, 'service' ) ) {
			esc_html_e( 'WordPress', 'gravityforms' );
		} else if ( rgar( $services, $notification['service'] ) ) {
			$service = rgar( $services, $notification['service'] );
			echo rgar( $service, 'label' );
		} else {
			esc_html_e( 'Undefined Service', 'gravityforms' );
		}

	}

	/**
	 * Displays the content of the Event column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFNotificationTable::$notification_events()
	 *
	 * @param array $notification The Notification Object.
	 *
	 * @return void
	 */
	function column_event( $notification ) {
		echo rgar( $this->notification_events, rgar( $notification, 'event' ) );
	}

	/**
	 * Content to display if the form does not have any notifications.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	function no_items() {
		$url = add_query_arg( array( 'nid' => 0 ) );
		printf( esc_html__( "This form doesn't have any notifications. Let's go %screate one%s.", 'gravityforms' ), "<a href='" . esc_url( $url ) . "'>", '</a>' );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 2.5
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		printf(
			'<div class="alignright"><a href="%s" class="button">%s</a></div>',
			esc_url( add_query_arg( array( 'nid' => 0 ) ) ),
			esc_html__( 'Add New', 'gravityforms' )
		);

	}

}
