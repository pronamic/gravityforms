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
	 * Contains instances of this class, if available.
	 *
	 * @since  2.2
	 * @since  next Changed to an array.
	 *
	 * @var   self[] $_instances If available, contains instances of this class.
	 */
	private static $_instances = array();

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
	 * Null or the add-on instance.
	 *
	 * @since 2.9.25
	 *
	 * @var null|GFFeedAddOn
	 */
	protected $add_on = null;

	/**
	 * Instantiates the class.
	 *
	 * @since 2.9.25
	 *
	 * @param bool|array       $allowed_batch_data_classes Optional. Array of class names that can be unserialized. Default true (any class).
	 * @param null|GFFeedAddOn $add_on                     Optional. The add-on instance.
	 */
	public function __construct( $allowed_batch_data_classes = true, $add_on = null ) {
		if ( $add_on instanceof GFFeedAddOn ) {
			$this->action = str_replace( 'gf', 'gf_' . $add_on->get_short_slug(), $this->action );
			$this->add_on = $add_on;
		}
		parent::__construct( $allowed_batch_data_classes );
	}

	/**
	 * Get instance of this class.
	 *
	 * @since  2.2
	 * @since  next Added the $add_on param.
	 *
	 * @param null|GFFeedAddOn $add_on Optional. The add-on instance.
	 *
	 * @return GF_Feed_Processor
	 */
	public static function get_instance( $add_on = null ) {
		$key = $add_on instanceof GFFeedAddOn ? $add_on->get_slug() : 0;

		if ( empty( self::$_instances[ $key ] ) ) {
			self::$_instances[ $key ] = new self( true, $add_on );
		}

		return self::$_instances[ $key ];
	}

	/**
	 * Push to queue
	 *
	 * @since 2.9.25
	 * @remove-in 3.0
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		if ( ! is_string( rgar( $data, 'addon' ) ) ) {
			_doing_it_wrong( __METHOD__, "Support for passing an add-on instance to `\$data['addon']` is deprecated since v2.7.15 and will be removed in v3.0. Please pass the add-on’s fully qualified class name (including its namespace) instead.", '' );
			$data['addon'] = get_class( $data['addon'] );
		}

		return parent::push_to_queue( $data );
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
		$feed      = $item['feed'];
		$feed_name = GFAPI::get_feed_name( $feed );
		$feed_id   = (int) rgar( $feed, 'id' );
		$entry_id  = (int) rgar( $item, 'entry_id' );

		if ( $this->add_on instanceof GFFeedAddOn ) {
			$addon     = $this->add_on;
			$feed_slug = rgar( $feed, 'addon_slug' );
			if ( $feed_slug !== $addon->get_slug() ) {
				$this->log_error( __METHOD__ . "(): Aborting. Feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id} belongs to a different add-on ({$feed_slug})." );

				return false;
			}
		} else {
			/**
			 * Passing an instance of the add-on.
			 * @deprecated 2.7.15
			 * @remove-in  3.0
			 */
			$addon    = $item['addon'];
			$callable = array( is_string( $addon ) ? $addon : get_class( $addon ), 'get_instance' );
			if ( is_callable( $callable ) ) {
				$addon = call_user_func( $callable );
			}

			if ( ! $addon instanceof GFFeedAddOn ) {
				GFCommon::log_error( __METHOD__ . "(): Aborting. Add-on ({$feed['addon_slug']}) not found for feed (#{$feed_id} - {$feed_name}) and entry #{$entry_id}." );

				return false;
			}
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

			$error = new WP_Error( $e->getCode(), $e->getMessage() );

			return $addon->is_feed_error_retryable( true, $error, $feed, $entry, $form ) ? $item : false;
		}

		$addon->save_entry_feed_status( $result, $entry_id, $feed_id, $form_id );

		if ( is_wp_error( $result ) ) {
			/** @var WP_Error $result */
			$addon->log_error( __METHOD__ . "(): Aborting. Error occurred during processing of feed (#{$feed_id} - {$feed_name}) for entry #{$entry_id}: {$result->get_error_message()}" );

			return $addon->is_feed_error_retryable( true, $result, $feed, $entry, $form ) ? $item : false;
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
		return $addon->can_process_feed( $feed, $entry, $form );
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

		if ( $this->add_on ) {
			$addon = $this->add_on;
		} else {
			$callable = array( $task['addon'], 'get_instance' );
			if ( ! is_callable( $callable ) ) {
				return;
			}
			$addon = call_user_func( $callable );
		}

		$feed     = $task['feed'];
		$feed_id  = (int) rgar( $feed, 'id' );
		$entry_id = (int) rgar( $task, 'entry_id' );
		$addon->log_error( __METHOD__ . "(): Aborting. Error occurred during processing of feed (#{$feed_id} - {$addon->get_feed_name( $feed )}) for entry #{$entry_id}: {$error['message']}" );
		$addon->save_entry_feed_status( new WP_Error( $error['type'], $error['message'] ), $entry_id, $feed_id, (int) rgar( $task, 'form_id' ) );
	}

	/**
	 * Increments the item attempts property and updates the batch in the database.
	 *
	 * @depecated 2.9.9
	 * @remove-in 4.0
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

	/**
	 * Writes a message to the add-on or core log.
	 *
	 * @since 2.9.25
	 *
	 * @param string $message The message to be logged.
	 *
	 * @return void
	 */
	public function log_debug( $message ) {
		if ( $this->add_on ) {
			$this->add_on->log_debug( $message );
		} else {
			parent::log_debug( $message );
		}
	}

	/**
	 * Writes an error message to the add-on or core log.
	 *
	 * @since 2.9.25
	 *
	 * @param string $message The message to be logged.
	 *
	 * @return void
	 */
	public function log_error( $message ) {
		if ( $this->add_on ) {
			$this->add_on->log_error( $message );
		} else {
			parent::log_error( $message );
		}
	}

	/**
	 * Returns the action portion of logging statements.
	 *
	 * @since 2.9.25
	 *
	 * @return string
	 */
	protected function get_action_for_log() {
		return $this->add_on ? '' : parent::get_action_for_log();
	}

}

/**
 * Returns an instance of the GF_Feed_Processor class
 *
 * @since 2.2
 * @since 2.9.25 Added the $add_on param.
 *
 * @param null|GFFeedAddOn $add_on Optional. The add-on instance.
 *
 * @return GF_Feed_Processor
 */
function gf_feed_processor( $add_on = null ) {
	return GF_Feed_Processor::get_instance( $add_on );
}
