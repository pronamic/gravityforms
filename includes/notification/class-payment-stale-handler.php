<?php
/**
 * Handles Payment Stale notification functionality, including setting up the cron for processing stale payment notifications, checking for stale payment notifications, and sending stale payment notifications.
 *
 * @package Gravity_Forms\Gravity_Forms\Notification
 */
namespace Gravity_Forms\Gravity_Forms\Notification;

use GFCommon;
use GFAPI;

/**
 * Class Payment_Stale_Handler
 *
 * @since 2.9.29
 *
 * Provides functionality for handling stale payment notifications.
 */
class Payment_Stale_Handler {

	/**
	 * Sets up the cron for processing stale payment notifications.
	 * This cron will run hourly and check for entries with a payment status of "Processing" that are older than a specified threshold (default is 60 minutes).
	 * For each of those entries, it will send a notification and update the entry meta to indicate that the stale notification has been processed.
	 *
	 * @since 2.9.29
	 *
	 * @return void
	 */
	public function activate_cron() {
		$cron_name = 'gform_stale_payment_cron';

		add_action( $cron_name, array( $this, 'process_stale_payment_notifications' ) );

		if ( ! wp_next_scheduled( $cron_name ) ) {
			wp_schedule_event( time(), 'hourly', $cron_name );
		}
	}

	/**
	 * Deactivates the cron for processing stale payment notifications. This function is hooked to the 'gform_uninstalling' action and will run when Gravity Forms is uninstalled, to clean up the scheduled cron job.
	 *
	 * @since 2.9.29
	 *
	 * @return void
	 */
	public function deactivate_cron() {
		wp_clear_scheduled_hook( 'gform_stale_payment_cron' );
	}

	/**
	 * This method allows executing the cron for processing stale payment notifications manually for debugging purposes. It checks for a "_execute_stale_payment_cron" query string parameter.
	 *
	 * @return void
	 */
	public function maybe_execute_cron() {
		if ( defined( 'GF_DEBUG' ) && GF_DEBUG && isset( $_GET['_execute_stale_payment_cron'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for debugging purposes only and requires the GF_DEBUG constant to be set.
			$this->process_stale_payment_notifications();
		}
	}

	/**
	 * Processes stale payment notifications. This function is hooked to the 'gform_stale_payment_cron' and will run hourly to check for entries with a payment status of "Processing" that are older than a specified threshold (default is 60 minutes).
	 * For each of those entries, it will send a notification and update the entry meta to indicate that the stale notification has been processed.
	 *
	 * @since 2.9.29
	 *
	 * @return void
	 */
	public function process_stale_payment_notifications() {

		GFCommon::log_debug( __METHOD__ . '(): Starting to process stale payment notifications.' );

		// Recording the cron event for system report page.
		GFCommon::record_cron_event( 'gform_stale_payment_cron' );

		// Getting entries that are considered to have a "stale" payment.
		$entries = $this->get_stale_entries();

		if ( empty( $entries ) ) {
			GFCommon::log_debug( __METHOD__ . '(): There are no entries with a stale payment. Nothing to do. Aborting.' );
			return;
		}

		foreach ( $entries as $entry ) {
			GFCommon::log_debug( __METHOD__ . '(): Sending stale payment notification for entry #' . $entry['id'] );

			// Updating the entry meta to indicate that the stale notification has been processed for this entry, to prevent sending multiple notifications for the same entry.
			$this->update_entry_meta( $entry );

			// Sending all stale payment notifications configured for this form.
			$this->send_stale_payment_notifications( $entry );
		}
	}

	/**
	 * Retrieves entries that are considered to have a "stale" payment. These are entries with a payment status of "Processing" that are older than a specified threshold (default is 60 minutes).
	 *
	 * @since 2.9.29
	 *
	 * @return array Returns an array of entries that are considered to have a "stale" payment.
	 */
	public function get_stale_entries() {

		/**
		 * Filters the threshold for considering an entry with a "Processing" payment status as stale. Defaults to 60 minutes.
		 *
		 * @since 2.9.29
		 *
		 * @param int $threshold_minutes The threshold time in minutes.
		 *
		 * @return int The filtered threshold in minutes.
		 */
		$threshold_minutes = absint( apply_filters( 'gform_payment_stale_threshold_minutes', 60 ) );

		// Getting entries with "Processing" payment status that haven't had a stale notification sent yet and are older than the threshold.
		// NOTE: Only entries with the payment_stale_notification_processed = 0 will be returned here. This meta is added when the entry is placed in Processing status. This helps keep this query performing well for large datasets.
		$search_criteria = [ 'field_filters' => [
			[ 'key' => 'payment_status', 'value' => 'Processing' ],
			[ 'key' => 'payment_stale_notification_processed', 'value' => '0' ],
			[ 'key' => 'date_created', 'operator' => '<=', 'value' => gmdate( 'Y-m-d H:i:s', time() - ( $threshold_minutes * MINUTE_IN_SECONDS ) ) ],
		],
		];

		// How many entries to process per cron run? Defaults to 100.
		$paging = [ 'offset' => 0, 'page_size' => (int) apply_filters( 'gform_payment_stale_batch_size', 100 ) ];

		return GFAPI::get_entries( 0, $search_criteria, null, $paging );
	}

	/**
	 * Retrieves the notifications for a form that are configured to be sent for the "payment_stale" event.
	 *
	 * @since 2.9.29
	 *
	 * @param array $form The form object to retrieve the notifications for.
	 *
	 * @return array An array of notifications that are configured to be sent for the "payment_stale" event.
	 */
	public function get_stale_payment_notifications( $form ) {
		return array_filter( rgar( $form, 'notifications', [] ), function( $notification ) {
			return rgar( $notification, 'event' ) === 'payment_stale';
		} );
	}

	/**
	 * Checks if the form has at least one active stale payment notification configured.
	 *
	 * @since 2.9.29
	 *
	 * @param array $form The form object to check for stale payment notifications.
	 *
	 * @return bool Returns true if the form has at least one active stale payment notification, false otherwise.
	 */
	public function has_stale_payment_notification( $form ) {
		$notifications = $this->get_stale_payment_notifications( $form );

		// Check for active notifications.
		foreach ( $notifications as $notification ) {
			// Notification is active by default (if 'isActive' is not set) and if it is set to true.
			$is_active = ! isset( $notification['isActive'] ) || $notification['isActive'] === true;
			if ( $is_active ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sends the stale payment notification for a given entry.
	 * This function retrieves the notifications for the form that are configured to be sent for the "payment_stale" event and sends them.
	 * It also updates the entry meta to indicate that the stale notification has been processed for this entry, which will prevent the cron from sending multiple notifications for the same entry.
	 *
	 * @since 2.9.29
	 *
	 * @param array $entry The entry object for which to send the stale payment notification.
	 *
	 * @return void
	 */
	public function send_stale_payment_notifications( $entry ) {

		$form = GFAPI::get_form( (int) $entry['form_id'] );

		$notification_ids = array_keys( $this->get_stale_payment_notifications( $form ) );

		// Sending stale payment notifications.
		GFCommon::send_notifications( $notification_ids, $form, $entry, true, 'payment_stale' );
	}

	/**
	 * Handles the logic for when an entry's payment status changes. This function is hooked to the 'gform_post_payment_status_change' action and will run whenever an entry's payment status is updated.
	 * When an entry's payment status is updated to "Processing" and the form has an active stale payment notification, it enables the stale payment notification functionality by ensuring the cron job is scheduled
	 * and adding an entry meta to include this entry in the stale payment notification cron process.
	 *
	 * @since 2.9.29
	 *
	 * @param $entry The entry object for which the payment status has changed.
	 *
	 * @return void
	 */
	public function handle_payment_status_change( $entry ) {

		// When an entry's payment status is updated to "Processing" and the form has an active stale payment notification, enable the stale payment notification functionality.
		if ( $entry['payment_status'] === 'Processing' ) {

			if ( $this->has_stale_payment_notification( GFAPI::get_form( (int) $entry['form_id'] ) ) ) {
				// Ensure the stale payment cron job is scheduled.
				$this->activate_cron();

				// Setting entry meta to enable payment stale notification for this entry.
				$this->add_entry_meta( $entry );
			}
		} else {
			// Remove meta when the payment processing is completed (i.e., when the payment status changes from "Processing" to any other status), to prevent the cron from sending a stale payment notification for this entry.
			$this->remove_entry_meta( $entry);
		}
	}

	/**
	 * Checks if the stale payment notification has already been processed for the given entry.
	 * If it hasn't, it adds an entry meta to indicate that the stale payment notification needs to be processed for this entry.
	 * This function should be called when an entry's payment status is updated to "Processing".
	 *
	 * @since 2.9.29
	 *
	 * @param array $entry The entry object for which to maybe add the stale payment notification meta.
	 *
	 * @return void
	 */
	public function add_entry_meta( $entry ) {
		$alreadyProcessed = gform_get_meta( $entry['id'], 'payment_stale_notification_processed' );

		// If the stale notification has already been processed for this entry, abort to prevent resending.
		if ( $alreadyProcessed ) {
			return;
		}

		GFCommon::log_debug( __METHOD__ . '(): Entry #' . $entry['id'] . ' marked as "Processing". Adding payment_stale_notification_processed=0 entry meta to include this entry in the stale payment notification cron process.' );

		// Add the entry meta. This will include this entry in the stale payment notification cron process.
		gform_update_meta( $entry['id'], 'payment_stale_notification_processed', 0, $entry['form_id'] );
	}

	/**
	 * Updates the entry meta to indicate that the stale payment notification has been processed for the given entry. This will prevent the cron from sending multiple notifications for the same entry.
	 *
	 * @since 2.9.29
	 *
	 * @param array $entry The entry object for which to update the stale payment notification meta.
	 *
	 * @return void
	 */
	public function update_entry_meta( $entry ) {
		// Updating the entry meta to indicate that the stale notification has been processed for this entry. This will prevent the cron from sending multiple notifications for the same entry.
		gform_update_meta( $entry['id'], 'payment_stale_notification_processed', 1, $entry['form_id'] );

		GFCommon::log_debug( __METHOD__ . '(): Stale payment notification processed for entry #' . $entry['id'] . '. Updating payment_stale_notification_processed=1 entry meta to prevent resending.' );
	}

	/**
	 * Removes the entry meta that indicates that the stale payment notification needs to be processed for this entry.
	 * This function should be called when an entry's payment status is updated from "Processing" to any other status, to prevent the cron from sending a stale payment notification for this entry.
	 *
	 * @since 2.9.29
	 *
	 * @param array $entry The entry object for which to remove the stale payment notification meta.
	 *
	 * @return void
	 */
	public function remove_entry_meta( $entry ) {
		GFCommon::log_debug( __METHOD__ . '(): Entry #' . $entry['id'] . ' completed payment processing. Deleting payment_stale_notification_processed entry meta to exclude this entry in the stale payment notification cron process.' );

		// Deleting the entry meta. This will exclude this entry in the stale payment notification cron process.
		gform_delete_meta( $entry['id'], 'payment_stale_notification_processed' );
	}
}
