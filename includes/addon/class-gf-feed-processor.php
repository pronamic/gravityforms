<?php

use Gravity_Forms\Gravity_Forms\Async\GF_Background_Process;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'Gravity_Forms\Gravity_Forms\Async\GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-background-process.php';
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
	 * Indicates if the task uses an array that supports the attempts key.
	 *
	 * @since 2.9.9
	 *
	 * @var bool
	 */
	protected $supports_attempts = true;

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
	 * Processes the task.
	 *
	 * @since  2.2
	 * @since  2.9.4 Updated to use the add-on save_entry_feed_status(), post_process_feed(), and fullfill_entry() methods.
	 *
	 * @access protected
	 *
	 * @param array $item {
	 *     The task arguments.
	 *
	 *     @type string $addon    The add-on class name.
	 *     @type array  $feed     The feed.
	 *     @type int    $entry_id The entry ID.
	 *     @type int    $form_id  The form ID.
	 *     @type int    $attempts The number of attempts. Only included if the task has been processed before.
	 * }
	 *
	 * @return bool|array
	 */
	protected function task( $item ) {

		$addon     = $item['addon'];
		$feed      = $item['feed'];
		$feed_name = GFAPI::get_feed_name( $feed );

		$callable = array( is_string( $addon ) ? $addon : get_class( $addon ), 'get_instance' );
		if ( is_callable( $callable ) ) {
			$addon = call_user_func( $callable );
		}

		$feed_id  = (int) rgar( $feed, 'id' );
		$entry_id = (int) rgar( $item, 'entry_id' );

		if ( ! $addon instanceof GFFeedAddOn ) {
			GFCommon::log_error( __METHOD__ . "(): Aborting. Add-on ({$feed['addon_slug']}) not found for feed (#{$feed_id} - {$feed_name}) and entry #{$entry_id}." );

			return false;
		}

		$addon->log_debug( __METHOD__ . "(): Preparing to process feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}." );

		$entry      = GFAPI::get_entry( $entry_id );
		$addon_slug = $addon->get_slug();

		// Remove task if entry cannot be found.
		if ( is_wp_error( $entry ) ) {
			$addon->log_error( __METHOD__ . "(): Aborting. Entry #{$entry_id} not found for feed (#{$feed_id} - {$feed_name})." );

			return false;

		}

		$form_id = (int) rgar( $item, 'form_id' );
		$form    = $this->filter_form( GFAPI::get_form( $form_id ), $entry );

		if ( ! $this->can_process_feed( $feed, $entry, $form, $addon ) ) {
			return false;
		}

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
			$addon->log_error( __METHOD__ . "(): Aborting. Feed (#{$feed_id} - {$feed_name}) attempted too many times for entry #{$entry_id}. Attempt number: {$item['attempts']}. Limit: {$max_attempts}." );

			return false;
		}

		$addon->log_debug( __METHOD__ . "(): Starting to process feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}. Attempt number: {$item['attempts']}." );

		// Keeping the try catch in case any third-party add-ons still throw exceptions.
		try {

			$result = $addon->process_feed( $feed, $entry, $form );

		} catch ( Exception $e ) {

			$addon->save_entry_feed_status( $e, $entry_id, $feed_id, $form_id );
			$addon->log_error( __METHOD__ . "(): Aborting. Error occurred during processing of feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}: {$e->getMessage()}" );

			// Return the item for another attempt
			return $item;
		}

		$addon->save_entry_feed_status( $result, $entry_id, $feed_id, $form_id );

		if ( is_wp_error( $result ) ) {
			/** @var WP_Error $result */
			$addon->log_error( __METHOD__ . "(): Aborting. Error occurred during processing of feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}: {$result->get_error_message()}" );

			// Return the item for another attempt
			return $item;
		}


		// If returned value from the process feed call is an array containing an ID, update entry and set the entry to its value.
		if ( (int) rgar( $result, 'id' ) === $entry_id ) {

			// Save updated entry.
			if ( $entry !== $result ) {
				GFAPI::update_entry( $result );
			}

			// Set entry to returned entry.
			$entry = $result;

		}

		$addon->post_process_feed( $feed, $entry, $form );
		$addon->fulfill_entry( $entry_id, $form_id );

		// Update the entry meta.
		GFAPI::update_processed_feeds_meta( $entry_id, $addon_slug, $feed_id, $form_id );
		$addon->log_debug( __METHOD__ . "(): Completed processing of feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}." );

		return false;

	}

	/**
	 * Determines if the task can be processed based on its attempts property.
	 *
	 * Overridden, returning true, so the existing feed-specific filters are used instead during task().
	 *
	 * @since 2.9.9
	 *
	 * @param mixed  $task     The task about to be processed.
	 * @param object $batch    The batch currently being processed.
	 * @param int    $task_num The number that identifies the task in the logs.
	 *
	 * @return bool
	 */
	protected function can_process_task( $task, $batch, $task_num ) {
		return true;
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
	 * Logs the error that occurred during feed processing.
	 *
	 * @since 2.9.8
	 *
	 * @param array $error The error returned by error_get_last().
	 *
	 * @return void
	 */
	protected function handle_error( $error ) {
		parent::handle_error( $error );

		$task = $this->get_current_task();
		if ( empty( $task ) ) {
			return;
		}

		$callable = array( $task['addon'], 'get_instance' );
		if ( ! is_callable( $callable ) ) {
			return;
		}

		$feed     = $task['feed'];
		$feed_id  = (int) rgar( $feed, 'id' );
		$entry_id = (int) rgar( $task, 'entry_id' );
		$addon    = call_user_func( $callable );
		$addon->log_error( __METHOD__ . "(): Aborting. Error occurred during processing of feed (#{$feed_id} - {$addon->get_feed_name( $feed )}) for entry #{$entry_id}: {$error['message']}" );
		$addon->save_entry_feed_status( new WP_Error( $error['type'], $error['message'] ), $entry_id, $feed_id, (int) rgar( $task, 'form_id' ) );
	}

	/**
	 * Increments the item attempts property and updates the batch in the database.
	 *
	 * @depecated 2.9.9
	 * @remove-in 3.1
	 *
	 * @since 2.4
	 * @since 2.9.4 Updated to use get_current_branch() instead of making a db request to get the batch.
	 *
	 * @param array $item {
	 *     The task arguments.
	 *
	 *     @type string $addon    The add-on class name.
	 *     @type array  $feed     The feed.
	 *     @type int    $entry_id The entry ID.
	 *     @type int    $form_id  The form ID.
	 *     @type int    $attempts The number of processing attempts. Only included if the task has been processed before.
	 * }
	 *
	 * @return array
	 */
	protected function increment_attempts( $item ) {
		$batch = $this->get_current_batch();

		$item_feed     = rgar( $item, 'feed' );
		$item_entry_id = rgar( $item, 'entry_id' );

		foreach ( $batch->data as $key => $task ) {
			$task_feed     = rgar( $task, 'feed' );
			$task_entry_id = rgar( $task, 'entry_id' );
			if ( $item_feed['id'] === $task_feed['id'] && $item_entry_id === $task_entry_id ) {
				$batch->data[ $key ]['attempts'] = isset( $batch->data[ $key ]['attempts'] ) ? $batch->data[ $key ]['attempts'] + 1 : 1;
				$item['attempts']                = $batch->data[ $key ]['attempts'];
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
