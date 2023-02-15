<?php

namespace Gravity_Forms\Gravity_Forms\Async;

use GF_Background_Process;
use GFCommon;
use GFAPI;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/libraries/gf-background-process.php';
}

/**
 * GF_Notifications_Processor Class.
 *
 * @since 2.6.9
 */
class GF_Notifications_Processor extends GF_Background_Process {

	/**
	 * The action name.
	 *
	 * @since 2.6.9
	 *
	 * @var string
	 */
	protected $action = 'gf_notifications_processor';

	/**
	 * Processes the task.
	 *
	 * @since 2.6.9
	 *
	 * @param array $item The task arguments.
	 *
	 * @return bool
	 */
	protected function task( $item ) {
		$notifications = rgar( $item, 'notifications' );
		if ( empty( $notifications ) || ! is_array( $notifications ) ) {
			return false;
		}

		$entry = GFAPI::get_entry( rgar( $item, 'entry_id' ) );
		if ( is_wp_error( $entry ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; Entry #%d not found.', rgar( $item, 'entry_id' ) ) );

			return false;
		}

		$form = GFAPI::get_form( rgar( $item, 'form_id' ) );
		if ( empty( $form ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Aborting; Form #%d not found.', rgar( $item, 'form_id' ) ) );

			return false;
		}

		$form  = $this->filter_form( $form, $entry );
		$event = rgar( $item, 'event', 'form_submission' );
		$data  = rgar( $item, 'data' );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		/**
		 * Allows custom actions to be performed before notifications are sent asynchronously.
		 *
		 * @since 2.7.1
		 *
		 * @param string $event         The event being processed.
		 * @param array  $notifications An array containing the IDs of the notifications being processed.
		 * @param array  $form          The form being processed.
		 * @param array  $entry         The entry being processed.
		 * @param array  $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
		 */
		do_action( 'gform_pre_process_async_notifications', $event, $notifications, $form, $entry, $data );

		GFCommon::send_notifications( $notifications, $form, $entry, true, $event, $data );

		/**
		 * Allows custom actions to be performed after notifications are sent asynchronously.
		 *
		 * @since 2.7.1
		 *
		 * @param string $event         The event being processed.
		 * @param array  $notifications An array containing the IDs of the notifications being processed.
		 * @param array  $form          The form being processed.
		 * @param array  $entry         The entry being processed.
		 * @param array  $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
		 */
		do_action( 'gform_post_process_async_notifications', $event, $notifications, $form, $entry, $data );

		return false;
	}

	/**
	 * Determines if async (background) processing of notifications is enabled.
	 *
	 * @since 2.7.1
	 *
	 * @param array  $notifications An array containing the IDs of the notifications to be sent.
	 * @param array  $form          The form being processed.
	 * @param array  $entry         The entry being processed.
	 * @param string $event         The event being processed.
	 * @param array  $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
	 *
	 * @return bool
	 */
	public function is_enabled( $notifications, $form, $entry, $event = 'form_submission', $data = array() ) {
		$form_id    = absint( rgar( $form, 'id' ) );
		$is_enabled = false;

		/**
		 * Allows async (background) processing of notifications to be enabled or disabled.
		 *
		 * @since 2.6.9
		 *
		 * @param bool   $is_enabled    Is async (background) processing of notifications enabled? Default is false.
		 * @param string $event         The event the notifications are to be sent for.
		 * @param array  $notifications An array containing the IDs of the notifications to be sent.
		 * @param array  $form          The form currently being processed.
		 * @param array  $entry         The entry currently being processed.
		 * @param array  $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
		 */
		return gf_apply_filters( array(
			'gform_is_asynchronous_notifications_enabled',
			$form_id,
		), $is_enabled, $event, $notifications, $form, $entry, $data );
	}

}
