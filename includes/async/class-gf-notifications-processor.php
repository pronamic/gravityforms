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

		$event = rgar( $item, 'event', 'form_submission' );
		$data  = rgar( $item, 'data' );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		GFCommon::send_notifications( $notifications, $this->filter_form( $form, $entry ), $entry, true, $event, $data );

		return false;
	}

}
