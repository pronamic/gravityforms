<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/libraries/gf-background-process.php';
}

/**
 * GF_Feed_Processor Class.
 *
 * @since 2.2
 */
class GF_Feed_Processor extends GF_Background_Process {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  2.2
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * The action name.
	 *
	 * @since  2.2
	 * @access protected
	 * @var    string
	 */
	protected $action = 'gf_feed_processor';

	/**
	 * Get instance of this class.
	 *
	 * @since  2.2
	 * @access public
	 * @static
	 *
	 * @return GF_Feed_Processor
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Task
	 *
	 * @since  2.2
	 * @access protected
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $item The task arguments: addon, feed, entry_id, and form_id.
	 *
	 * @return bool
	 */
	protected function task( $item ) {

		$addon     = $item['addon'];
		$feed      = $item['feed'];
		$feed_name = rgars( $feed, 'meta/feed_name' ) ? $feed['meta']['feed_name'] : rgars( $feed, 'meta/feedName' );

		$callable = array( is_string( $addon ) ? $addon : get_class( $addon ), 'get_instance' );
		if ( is_callable( $callable ) ) {
			$addon = call_user_func( $callable );
		}

		if ( ! $addon instanceof GFFeedAddOn ) {
			GFCommon::log_error( __METHOD__ . "(): attempted feed (#{$feed['id']} - {$feed_name}) for entry #{$item['entry_id']} for {$feed['addon_slug']} but add-on could not be found. Bailing." );

			return false;
		}

		$entry      = GFAPI::get_entry( $item['entry_id'] );
		$addon_slug = $addon->get_slug();

		// Remove task if entry cannot be found.
		if ( is_wp_error( $entry ) ) {

			call_user_func( array(
				$addon,
				'log_debug',
			), __METHOD__ . "(): attempted feed (#{$feed['id']} - {$feed_name}) for entry #{$item['entry_id']} for {$addon_slug} but entry could not be found. Bailing." );

			return false;

		}

		$form = $this->filter_form( GFAPI::get_form( $item['form_id'] ), $entry );

		if ( ! $this->can_process_feed( $feed, $entry, $form, $addon ) ) {
			return false;
		}

		$item = $this->increment_attempts( $item );

		$max_attempts = 1;

		/**
		 * Allow the number of retries to be modified before the feed is abandoned.
		 *
		 * if $max_attempts > 1 and if GFFeedAddOn::process_feed() throws an error or returns a WP_Error then the feed
		 * will be attempted again. Once the maximum number of attempts has been reached then the feed will be abandoned.
		 *
		 * @since 2.4
		 *
		 * @param int    $max_attempts The maximum number of retries allowed. Default: 1.
		 * @param array  $form         The form array
		 * @param array  $entry        The entry array
		 * @param string $addon_slug   The add-on slug
		 * @param array  $feed         The feed array
		 */
		$max_attempts = apply_filters( 'gform_max_async_feed_attempts', $max_attempts, $form, $entry, $addon_slug, $feed );

		// Remove task if it was attempted too many times but failed to complete.
		if ( $item['attempts'] > $max_attempts ) {

			call_user_func( array(
				$addon,
				'log_debug',
			), __METHOD__ . "(): attempted feed (#{$feed['id']} - {$feed_name}) for entry #{$entry['id']} for {$addon->get_slug()} too many times. Bailing." );

			return false;
		}

		// Use the add-on to log the start of feed processing.
		call_user_func( array(
			$addon,
			'log_debug',
		), __METHOD__ . "(): Starting to process feed (#{$feed['id']} - {$feed_name}) for entry #{$entry['id']} for {$addon->get_slug()}. Attempt number: " . $item['attempts'] );

		try {

			// Maybe convert PHP errors to exceptions so that they get caught.
			// This will catch some fatal errors, but not all.
			// Errors that are not caught will halt execution of subsequent feeds, but those will be
			// executed during the next cron cycles, which happens every 5 minutes
			set_error_handler( array( $this, 'custom_error_handler' ) );

			// Process feed.
			$returned_entry = call_user_func( array( $addon, 'process_feed' ), $feed, $entry, $form );

			// Back to built-in error handler.
			restore_error_handler();

		} catch ( Exception $e ) {

			// Back to built-in error handler.
			restore_error_handler();

			// Log the exception.
			call_user_func( array(
				$addon,
				'log_error',
			), __METHOD__ . "(): Unable to process feed due to error: {$e->getMessage()}" );

			// Return the item for another attempt
			return $item;
		}

		if ( is_wp_error( $returned_entry ) ) {
			/** @var WP_Error $returned_entry */
			// Log the error.
			call_user_func( array(
				$addon,
				'log_error',
			), __METHOD__ . "(): Unable to process feed due to error: {$returned_entry->get_error_message()}" );

			// Return the item for another attempt
			return $item;
		}


		// If returned value from the process feed call is an array containing an ID, update entry and set the entry to its value.
		if ( is_array( $returned_entry ) && rgar( $returned_entry, 'id' ) ) {

			// Set entry to returned entry.
			$entry = $returned_entry;

			// Save updated entry.
			if ( $entry !== $returned_entry ) {
				GFAPI::update_entry( $entry );
			}

		}

		/**
		 * Perform a custom action when a feed has been processed.
		 *
		 * @since 2.0
		 *
		 * @param array   $feed The feed which was processed.
		 * @param array   $entry The current entry object, which may have been modified by the processed feed.
		 * @param array   $form The current form object.
		 * @param GFAddOn $addon The current instance of the GFAddOn object which extends GFFeedAddOn or GFPaymentAddOn (i.e. GFCoupons, GF_User_Registration, GFStripe).
		 */
		do_action( 'gform_post_process_feed', $feed, $entry, $form, $addon );
		do_action( "gform_{$feed['addon_slug']}_post_process_feed", $feed, $entry, $form, $addon );

		// Log that Add-On has been fulfilled.
		call_user_func( array(
			$addon,
			'log_debug',
		), __METHOD__ . '(): Marking entry #' . $entry['id'] . ' as fulfilled for ' . $feed['addon_slug'] );
		gform_update_meta( $entry['id'], "{$feed['addon_slug']}_is_fulfilled", true );

		// Update the entry meta.
		GFAPI::update_processed_feeds_meta( $entry['id'], $addon_slug, $feed['id'], rgar( $form, 'id' ) );

		return false;

	}

	/**
	 * Determines if the feed can be processed based on the contents of the processed feeds entry meta.
	 *
	 * @since 2.9.2
	 *
	 * @param array       $entry The entry being processed.
	 * @param array       $feed  The feed queued for processing.
	 * @param array       $form  The form the entry belongs to.
	 * @param GFFeedAddOn $addon The current instance of the add-on the feed belongs to.
	 *
	 * @return bool
	 */
	public function can_process_feed( $feed, $entry, $form, $addon ) {
		$entry_id          = (int) rgar( $entry, 'id' );
		$processed_feeds   = GFAPI::get_processed_feeds_meta( $entry_id, $addon->get_slug() );
		$already_processed = ! empty( $processed_feeds ) && in_array( (int) rgar( $feed, 'id' ), $processed_feeds );

		if ( ! $already_processed ) {
			return true;
		}

		$feed_name = rgars( $feed, 'meta/feed_name' ) ? $feed['meta']['feed_name'] : rgars( $feed, 'meta/feedName' );

		if ( ! $addon->is_reprocessing_supported( $feed, $entry, $form ) ) {
			$addon->log_debug( __METHOD__ . sprintf( "(): Feed (#%d - %s) has already been processed for entry #%d. Reprocessing is NOT supported.", rgar( $feed, 'id' ), $feed_name, $entry_id ) );

			return false;
		}

		/**
		 * Allows reprocessing of the feed to be enabled.
		 *
		 * @since 2.9.2
		 *
		 * @param bool        $allow_reprocessing Indicates if the feed can be reprocessed. Default is false.
		 * @param array       $feed               The feed queued for processing.
		 * @param array       $entry              The entry being processed.
		 * @param array       $form               The form the entry belongs to.
		 * @param GFFeedAddOn $addon              The current instance of the add-on the feed belongs to.
		 * @param array       $processed_feeds    An array of feed IDs that have already been processed for the given entry.
		 */
		$allow_reprocessing = apply_filters( 'gform_allow_async_feed_reprocessing', false, $feed, $entry, $form, $addon, $processed_feeds );

		if ( ! $allow_reprocessing ) {
			$addon->log_debug( __METHOD__ . sprintf( "(): Feed (#%d - %s) has already been processed for entry #%d. Reprocessing is NOT allowed.", rgar( $feed, 'id' ), $feed_name, $entry_id ) );

			return false;
		}

		$addon->log_debug( __METHOD__ . sprintf( "(): Feed (#%d - %s) has already been processed for entry #%d. Reprocessing IS allowed.", rgar( $feed, 'id' ), $feed_name, $entry_id ) );

		return true;
	}

	/**
	 * Custom error handler to convert any errors to an exception.
	 *
	 * @since  2.2
	 * @since  2.6.5 Removed the $context param.
	 * @access public
	 *
	 * @param int    $number  The level of error raised.
	 * @param string $string  The error message, as a string.
	 * @param string $file    The filename the error was raised in.
	 * @param int    $line    The line number the error was raised at.
	 * @param array  $context An array that points to the active symbol table at the point the error occurred.
	 *
	 * @throws ErrorException
	 *
	 * @return false
	 */
	public function custom_error_handler( $number, $string, $file, $line ) {

		// Determine if this error is one of the enabled ones in php config (php.ini, .htaccess, etc).
		$error_is_enabled = (bool) ( $number & ini_get( 'error_reporting' ) );

		// Throw an Error Exception, to be handled by whatever Exception handling logic is available in this context.
		if ( in_array( $number, array( E_USER_ERROR, E_RECOVERABLE_ERROR ) ) && $error_is_enabled ) {

			throw new ErrorException( $string, 0, $number, $file, $line );

		} elseif ( $error_is_enabled ) {

			// Log the error if it's enabled. Otherwise, just ignore it.
			error_log( $string, 0 );

			// Make sure this ends up in $php_errormsg, if appropriate.
			return false;
		}
	}

	protected function increment_attempts( $item ) {
		$batch = $this->get_batch();

		$item_feed  = rgar( $item, 'feed' );
		$item_entry_id = rgar( $item, 'entry_id' );

		foreach ( $batch->data as $key => $task ) {
			$task_feed  = rgar( $task, 'feed' );
			$task_entry_id = rgar( $task, 'entry_id' );
			if ( $item_feed['id'] === $task_feed['id'] && $item_entry_id === $task_entry_id ) {
				$batch->data[ $key ]['attempts'] = isset( $batch->data[ $key ]['attempts'] ) ? $batch->data[ $key ]['attempts'] + 1 : 1;
				$item['attempts'] = $batch->data[ $key ]['attempts'];
				break;
			}
		}

		$this->update( $batch->key, $batch->data );
		return $item;
	}
}

/**
 * Returns an instance of the GF_Feed_Processor class
 *
 * @see    GF_Feed_Processor::get_instance()
 * @return GF_Feed_Processor
 */
function gf_feed_processor() {
	return GF_Feed_Processor::get_instance();
}
