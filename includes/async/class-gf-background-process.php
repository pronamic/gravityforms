<?php

namespace Gravity_Forms\Gravity_Forms\Async;

use GFCommon;
use stdClass;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'Gravity_Forms\Gravity_Forms\Async\WP_Async_Request' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-wp-async-request.php';
}

/**
 * Abstract GF_Background_Process class.
 *
 * Based on WP_Background_Process
 * https://github.com/A5hleyRich/wp-background-processing/blob/master/classes/wp-background-process.php
 *
 * @since 2.2
 * @since 2.9.8 Namespaced.
 *
 * @abstract
 * @extends WP_Async_Request
 */
abstract class GF_Background_Process extends WP_Async_Request {

	/**
	 * The default query arg name used for passing the chain ID to new processes.
	 *
	 * @since 2.9.7
	 */
	const CHAIN_ID_ARG_NAME = 'chain_id';

	/**
	 * Unique background process chain ID.
	 *
	 * @since 2.9.7
	 *
	 * @var string
	 */
	private $chain_id;

	/**
	 * Action
	 *
	 * @since 2.2
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_process';

	/**
	 * Start time of current process.
	 *
	 * @since 2.2
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;

	/**
	 * Cron_hook_identifier
	 *
	 * @since 2.2
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_hook_identifier;

	/**
	 * Cron_interval_identifier
	 *
	 * @since 2.2
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_interval_identifier;

	/**
	 * Restrict object instantiation when using unserialize.
	 *
	 * @since 2.9.7
	 *
	 * @var bool|array
	 */
	protected $allowed_batch_data_classes = true;

	/**
	 * Null or the current batch.
	 *
	 * @since 2.9.4
	 *
	 * @var object|null
	 */
	protected $current_batch;

	/**
	 * Null or the current task.
	 *
	 * @since 2.9.9
	 *
	 * @var mixed|null
	 */
	protected $current_task;

	/**
	 * Indicates if the task uses an array that supports the attempts key.
	 *
	 * @since 2.9.9
	 *
	 * @var bool
	 */
	protected $supports_attempts = false;

	/**
	 * The status set when process is cancelling.
	 *
	 * @since 2.9.7
	 *
	 * @var int
	 */
	const STATUS_CANCELLED = 1;

	/**
	 * The status set when processing is paused using $this->pause( true ).
	 *
	 * @since 2.9.7
	 *
	 * @var int
	 */
	const STATUS_PAUSED = 2;

	/**
	 * The status set when processing is paused using $bp_object->pause() or $bp_object->pause( false ).
	 *
	 * @since 2.9.10
	 *
	 * @var int
	 */
	const STATUS_PAUSED_NO_TS = 3;

	/**
	 * Initiate new background process
	 *
	 * @since 2.2
	 *
	 * @param bool|array $allowed_batch_data_classes Optional. Array of class names that can be unserialized. Default true (any class).
	 */
	public function __construct( $allowed_batch_data_classes = true ) {
		parent::__construct();

		if ( empty( $allowed_batch_data_classes ) && false !== $allowed_batch_data_classes ) {
			$allowed_batch_data_classes = true;
		}

		if ( ! is_bool( $allowed_batch_data_classes ) && ! is_array( $allowed_batch_data_classes ) ) {
			$allowed_batch_data_classes = true;
		}

		// If allowed_batch_data_classes property set in subclass,
		// only apply override if not allowing any class.
		if ( true === $this->allowed_batch_data_classes || true !== $allowed_batch_data_classes ) {
			$this->allowed_batch_data_classes = $allowed_batch_data_classes;
		}

		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );

		// Ensure dispatch query args included extra data.
		add_filter( $this->identifier . '_query_args', array( $this, 'filter_dispatch_query_args' ), 1 );
		add_filter( $this->identifier . '_post_args', array( $this, 'filter_dispatch_post_args' ), 1 );

		add_action( 'wp_delete_site', array( $this, 'delete_site_batches' ) );
		add_action( 'make_spam_blog', array( $this, 'delete_site_batches' ) );
		add_action( 'archive_blog', array( $this, 'delete_site_batches' ) );
		add_action( 'make_delete_blog', array( $this, 'delete_site_batches' ) );
	}

	/**
	 * Dispatches the queued tasks to Admin Ajax for processing and schedules a cron job in case processing fails.
	 *
	 * @since 2.2
	 *
	 * @access public
	 *
	 * @return array|\WP_Error|false HTTP Response array, WP_Error on failure, or false if not attempted.
	 */
	public function dispatch() {
		$this->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );

		if ( $this->is_processing() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Already processing for %s.', __METHOD__, $this->action ) );
			// Process already running.
			return false;
		}

		if ( $this->is_queue_empty() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Queue is empty for %s.', __METHOD__, $this->action ) );

			return false;
		}

		/**
		 * Filter fired before background process dispatches its next process.
		 *
		 * @since 2.9.7
		 *
		 * @param bool   $cancel   Should the dispatch be cancelled? Default false.
		 * @param string $chain_id The background process chain ID.
		 */
		$cancel = apply_filters( $this->identifier . '_pre_dispatch', false, $this->get_chain_id() );

		if ( $cancel ) {
			$this->log_debug( sprintf( '%s(): Aborting. Cancelled using the %s_pre_dispatch filter for %s.', __METHOD__, $this->identifier, $this->action ) );

			return false;
		}

		// Schedule the cron healthcheck.
		$this->schedule_event();

		// Perform remote post.
		$dispatched = parent::dispatch();

		if ( is_wp_error( $dispatched ) ) {
			$this->log_debug( sprintf( '%s(): Unable to dispatch tasks to Admin Ajax: %s', __METHOD__, $dispatched->get_error_message() ) );
		}

		return $dispatched;
	}

	/**
	 * Push to queue
	 *
	 * @since 2.2
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		$this->data[] = $data;

		return $this;
	}

	/**
	 * Save queue
	 *
	 * @since 2.2
	 * @since 2.9.7 Added timestamps to the data array.
	 *
	 * @return $this
	 */
	public function save() {
		if ( empty( $this->data ) ) {
			return $this;
		}

		$key = $this->generate_key();
		$this->log_debug( sprintf( '%s(): Saving batch %s. Tasks: %d.', __METHOD__, $key, count( $this->data ) ) );

		$time  = microtime( true );
		$batch = array(
			'blog_id'           => get_current_blog_id(),
			'data'              => $this->data,
			'timestamp_created' => $time,
			'timestamp_updated' => $time,
		);
		update_site_option( $key, $batch );

		/**
		 * Batch saved action.
		 *
		 * @since 2.9.8
		 *
		 * @param string $key   The batch key.
		 * @param array  $batch The saved batch.
		 */
		do_action( $this->identifier . '_batch_saved', $key, $batch );

		// Clean out data so that new data isn't prepended with closed session's data.
		$this->data = array();

		return $this;
	}

	/**
	 * Update queue
	 *
	 * @since 2.2
	 * @since 2.9.7 Added timestamps to the data array.
	 *
	 * @param string $key Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $data ) {
		if ( empty( $data ) ) {
			return $this;
		}

		$existing_batch = get_site_option( $key );
		if ( empty( $existing_batch ) ) {
			return $this;
		}

		$batch = array(
			'blog_id'           => get_current_blog_id(),
			'data'              => $data,
			'timestamp_created' => rgar( $existing_batch, 'timestamp_created' ),
			'timestamp_updated' => microtime( true ),
		);

		$result = update_site_option( $key, $batch );
		if ( ! $result ) {
			return $this;
		}

		$this->log_debug( sprintf( '%s(): Batch %s updated. Tasks remaining: %d.', __METHOD__, $key, count( $data ) ) );

		/**
		 * Batch updated action.
		 *
		 * @since 2.9.8
		 *
		 * @param string $key            The batch key.
		 * @param array  $batch          The updated batch.
		 * @param array  $existing_batch The batch from before it was updated.
		 */
		do_action( $this->identifier . '_batch_updated', $key, $batch, $existing_batch );

		return $this;
	}

	/**
	 * Delete batch from queue.
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		$this->log_debug( sprintf( '%s(): Deleting batch %s.', __METHOD__, $key ) );
		delete_site_option( $key );

		/**
		 * Batch deleted action.
		 *
		 * @since 2.9.8
		 *
		 * @param string $key The batch key.
		 */
		do_action( $this->identifier . '_batch_deleted', $key );

		return $this;
	}

	/**
	 * Delete entire job queue.
	 *
	 * @since 2.9.7
	 */
	public function delete_all() {
		$this->delete_batches();

		delete_site_option( $this->get_status_key() );

		$this->cancelled();
	}

	/**
	 * Cancel job on next batch.
	 *
	 * @since 2.9.7
	 */
	public function cancel() {
		update_site_option( $this->get_status_key(), self::STATUS_CANCELLED );

		// Just in case the job was paused at the time.
		$this->dispatch();
	}

	/**
	 * Has the process been cancelled?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function is_cancelled() {
		return $this->get_status() === self::STATUS_CANCELLED;
	}

	/**
	 * Called when background process has been cancelled.
	 *
	 * @since 2.9.7
	 */
	protected function cancelled() {
		do_action( $this->identifier . '_cancelled', $this->get_chain_id() );
	}

	/**
	 * Pause job on next batch.
	 *
	 * @since 2.9.7
	 *
	 * @param bool $set_timestamp Indicates of the timestamp option should be set, so it can be used by the cron healthcheck to automatically resume processing.
	 */
	public function pause( $set_timestamp = false ) {
		$this->log_debug( sprintf( '%s(): Pausing processing for %s.', __METHOD__, $this->action ) );
		update_site_option( $this->get_status_key(), $set_timestamp ? self::STATUS_PAUSED : self::STATUS_PAUSED_NO_TS );
		if ( $set_timestamp ) {
			update_site_option( $this->get_identifier() . '_pause_timestamp', microtime( true ) );
		}
	}

	/**
	 * Has the process been paused?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function is_paused() {
		return in_array( $this->get_status(), array( self::STATUS_PAUSED, self::STATUS_PAUSED_NO_TS ), true );
	}

	/**
	 * Called when background process has been paused.
	 *
	 * @since 2.9.7
	 */
	protected function paused() {
		do_action( $this->identifier . '_paused', $this->get_chain_id() );
	}

	/**
	 * Resume job.
	 *
	 * @since 2.9.7
	 *
	 * @param bool $dispatch Indicates if the Ajax request to trigger processing of the queue should be sent.
	 */
	public function resume( $dispatch = true ) {
		$this->log_debug( sprintf( '%s(): Resuming processing for %s.', __METHOD__, $this->action ) );
		delete_site_option( $this->get_status_key() );
		delete_site_option( $this->get_identifier() . '_pause_timestamp' );

		if ( $dispatch ) {
			// dispatch() handles calling schedule_event().
			//$this->schedule_event();
			$this->dispatch();
		}

		$this->resumed();
	}

	/**
	 * Called when background process has been resumed.
	 *
	 * @since 2.9.7
	 */
	protected function resumed() {
		do_action( $this->identifier . '_resumed', $this->get_chain_id() );
	}

	/**
	 * Is queued?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function is_queued() {
		return ! $this->is_queue_empty();
	}

	/**
	 * Is the tool currently active, e.g. starting, working, paused or cleaning up?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->is_queued() || $this->is_processing() || $this->is_paused() || $this->is_cancelled();
	}

	/**
	 * Generate key
	 *
	 * Generates a unique key based on microtime. Queue items are
	 * given a unique key so that they can be merged upon save.
	 *
	 * @since 2.2
	 * @since 2.9.7 Added the $key param.
	 *
	 * @param int    $length Optional max length to trim key to, defaults to 64 characters.
	 * @param string $key    Optional string to append to identifier before hash, defaults to "batch".
	 *
	 * @return string
	 */
	protected function generate_key( $length = 64, $key = 'batch' ) {
		$unique  = md5( microtime() . wp_rand() );
		$prepend = $this->identifier . '_' . $key . '_blog_id_' . get_current_blog_id() . '_';

		return substr( $prepend . $unique, 0, $length );
	}

	/**
	 * Get the status key.
	 *
	 * @since 2.9.7
	 *
	 * @return string
	 */
	protected function get_status_key() {
		return $this->identifier . '_status';
	}

	/**
	 * Get the status value for the process.
	 *
	 * @since 2.9.7
	 *
	 * @return int
	 */
	protected function get_status() {
		global $wpdb;

		if ( is_multisite() ) {
			$status = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d LIMIT 1",
					$this->get_status_key(),
					get_current_network_id()
				)
			);
		} else {
			$status = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
					$this->get_status_key()
				)
			);
		}

		return absint( $status );
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 *
	 * @since 2.2
	 *
	 * @return void|mixed
	 */
	public function maybe_handle() {
		$this->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );

		// Don't lock up other requests while processing
		session_write_close();

		check_ajax_referer( $this->identifier, 'nonce' );

		// Background process already running.
		if ( $this->is_processing() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Already processing for %s.', __METHOD__, $this->action ) );

			return $this->maybe_wp_die();
		}

		// Cancel requested.
		if ( $this->is_cancelled() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Processing has been cancelled for %s.', __METHOD__, $this->action ) );
			$this->clear_scheduled_event();
			$this->delete_all();

			return $this->maybe_wp_die();
		}

		// Pause requested.
		if ( $this->is_paused() ) {
			if ( $this->is_pause_expired() ) {
				$this->resume( false );
			} else {
				$this->log_debug( sprintf( '%s(): Aborting. Processing is paused for %s.', __METHOD__, $this->action ) );
				// Not clearing; we use it to resume when the pause duration has expired.
				//$this->clear_scheduled_event();
				$this->paused();

				return $this->maybe_wp_die();
			}
		}

		// No data to process.
		if ( $this->is_queue_empty() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Queue is empty for %s.', __METHOD__, $this->action ) );

			return $this->maybe_wp_die();
		}

		$this->handle();

		return $this->maybe_wp_die();
	}

	/**
	 * Is queue empty
	 *
	 * @since 2.2
	 * @since 2.9.7 Updated to use get_batch().
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		return empty( $this->get_batch() );
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 *
	 * @since 2.2
	 *
	 * @deprecated 2.9.7 Superseded.
	 * @see        is_processing()
	 */
	protected function is_process_running() {
		return $this->is_processing();
	}

	/**
	 * Is the background process currently running?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function is_processing() {
		if ( get_site_transient( $this->identifier . '_process_lock' ) ) {
			// Process already running.
			return true;
		}

		return false;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 *
	 * @since 2.2
	 *
	 * @param bool $reset_start_time Optional, default true.
	 */
	public function lock_process( $reset_start_time = true ) {
		if ( $reset_start_time ) {
			$this->start_time = time(); // Set start time of current process.
		}

		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
		$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

		$microtime = microtime();
		$locked    = set_site_transient( $this->identifier . '_process_lock', $microtime, $lock_duration );

		/**
		 * Action to note whether the background process managed to create its lock.
		 *
		 * The lock is used to signify that a process is running a task and no other
		 * process should be allowed to run the same task until the lock is released.
		 *
		 * @since 2.9.7
		 *
		 * @param bool   $locked        Whether the lock was successfully created.
		 * @param string $microtime     Microtime string value used for the lock.
		 * @param int    $lock_duration Max number of seconds that the lock will live for.
		 * @param string $chain_id      Current background process chain ID.
		 */
		do_action(
			$this->identifier . '_process_locked',
			$locked,
			$microtime,
			$lock_duration,
			$this->get_chain_id()
		);
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @since 2.2
	 *
	 * @return $this
	 */
	public function unlock_process() {
		$unlocked = delete_site_transient( $this->identifier . '_process_lock' );

		/**
		 * Action to note whether the background process managed to release its lock.
		 *
		 * The lock is used to signify that a process is running a task and no other
		 * process should be allowed to run the same task until the lock is released.
		 *
		 * @since 2.9.7
		 *
		 * @param bool   $unlocked Whether the lock was released.
		 * @param string $chain_id Current background process chain ID.
		 */
		do_action( $this->identifier . '_process_unlocked', $unlocked, $this->get_chain_id() );

		return $this;
	}

	/**
	 * Get batch
	 *
	 * @since 2.2
	 * @since 2.9.7 Updated to use get_batches().
	 *
	 * @return stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		return array_reduce(
			$this->get_batches( 1 ),
			static function ( $carry, $batch ) {
				return $batch;
			},
			array()
		);
	}

	/**
	 * Get batches.
	 *
	 * @since 2.9.7
	 *
	 * @param int $limit Number of batches to return, defaults to all.
	 *
	 * @return array of stdClass
	 */
	public function get_batches( $limit = 0 ) {
		global $wpdb;

		if ( empty( $limit ) || ! is_int( $limit ) ) {
			$limit = 0;
		}

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key   = $this->identifier . '_batch_blog_id_' . get_current_blog_id() . '_';
		$items = $this->get_batches_results( $table, $column, $key_column, $limit, $key );

		// No more batches for this blog ID. Get the next in the queue regardless of the blog ID.
		if ( empty( $items ) && is_multisite() ) {
			$items = $this->get_batches_results( $table, $column, $key_column, $limit, $this->identifier . '_batch_' );
		}

		$batches = array();

		if ( ! empty( $items ) ) {
			$allowed_classes = $this->allowed_batch_data_classes;

			$batches = array_map(
				static function ( $item ) use ( $column, $value_column, $allowed_classes ) {
					$batch                    = new stdClass();
					$batch->key               = $item->{$column};
					$value                    = static::maybe_unserialize( $item->{$value_column}, $allowed_classes );
					$batch->data              = rgar( $value, 'data' );
					$batch->blog_id           = rgar( $value, 'blog_id' );
					$batch->timestamp_created = rgar( $value, 'timestamp_created' );
					$batch->timestamp_updated = rgar( $value, 'timestamp_updated' );

					return $batch;
				},
				$items
			);
		}

		return $batches;
	}

	/**
	 * Performs the database query to get the batches.
	 *
	 * @since 2.9.7
	 *
	 * @param string $table      The name of the table containing the batches.
	 * @param string $column     The name of column containing the batch key.
	 * @param string $key_column The name of the column containing the record ID.
	 * @param int    $limit      Number of batches to return.
	 * @param string $key        The prefix used by the batch key.
	 *
	 * @return array|object|stdClass[]|null
	 */
	protected function get_batches_results( $table, $column, $key_column, $limit, $key ) {
		global $wpdb;

		$key = $wpdb->esc_like( $key ) . '%';

		$sql = '
				SELECT *
				FROM ' . $table . '
				WHERE ' . $column . ' LIKE %s
				ORDER BY ' . $key_column . ' ASC
				';

		$args = array( $key );

		if ( ! empty( $limit ) ) {
			$sql .= ' LIMIT %d';

			$args[] = $limit;
		}

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$args
			)
		);
	}

	/**
	 * Sets the current_batch property.
	 *
	 * @since 2.9.4
	 *
	 * @param object|null $batch Null or the batch currently being processed.
	 *
	 * @return void
	 */
	protected function set_current_batch( $batch = null ) {
		$this->current_batch = $batch;
	}

	/**
	 * Gets the batch currently being processed.
	 *
	 * @since 2.9.4
	 *
	 * @return object|null
	 */
	protected function get_current_batch() {
		return $this->current_batch;
	}

	/**
	 * Sets the current_task property.
	 *
	 * @since 2.9.9
	 *
	 * @param mixed|null $task Null or the task currently being processed.
	 *
	 * @return void
	 */
	protected function set_current_task( $task = null ) {
		$this->current_task = $task;
	}

	/**
	 * Gets the task currently being processed.
	 *
	 * @since 2.9.9
	 *
	 * @return mixed|null
	 */
	protected function get_current_task() {
		return $this->current_task;
	}

	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 *
	 * @since 2.2
	 *
	 * @return void|mixed
	 */
	protected function handle() {
		$is_cron = false;
		if ( wp_doing_ajax() ) {
			$method = ' via Ajax request';
		} elseif ( wp_doing_cron() ) {
			$method  = ' via cron request';
			$is_cron = true;
		} else {
			$method = '';
		}

		$this->log_debug( sprintf( '%s(): Running%s for %s.', __METHOD__, $method, $this->action ) );
		$this->lock_process();

		/**
		 * Number of seconds to sleep between batches. Defaults to 0 seconds, minimum 0.
		 *
		 * @param int $seconds
		 */
		$throttle_seconds = max(
			0,
			apply_filters(
				$this->identifier . '_seconds_between_batches',
				apply_filters(
					$this->prefix . '_seconds_between_batches',
					0
				)
			)
		);

		do {
			$batch = $this->get_batch();

			if ( ! is_object( $batch ) ) {
				$this->log_debug( __METHOD__ . '(): Aborting. Getting the next batch returned empty or an invalid value.' );
				break;
			}

			if ( is_multisite() ) {
				$current_blog_id = get_current_blog_id();
				if ( $current_blog_id !== $batch->blog_id ) {
					if ( ! $this->is_valid_blog( $batch->blog_id ) ) {
						$this->log_debug( sprintf( '%s(): Blog #%s is no longer valid for batch %s.', __METHOD__, $batch->blog_id, $batch->key ) );
						$this->delete_batches( $batch->blog_id );
						continue;
					}

					$this->spawn_multisite_child_process( $batch->blog_id );
					if ( $is_cron ) {
						// Switch back to the current blog and return so the other tasks queued in this process can be run.
						switch_to_blog( $current_blog_id );
						return;
					} else {
						return $this->maybe_wp_die();
					}
				}
			}

			if ( $this->has_batch_been_updated( $batch ) ) {
				$this->log_debug( sprintf( '%s(): Processing batch %s; Tasks: %d; Created: %s; Last update: %s.', __METHOD__, $batch->key, count( $batch->data ), wp_date( 'Y-m-d H:i:s', (int) $batch->timestamp_created ), wp_date( 'Y-m-d H:i:s', (int) $batch->timestamp_updated ) ) );
			} else {
				$this->log_debug( sprintf( '%s(): Processing batch %s; Tasks: %d.', __METHOD__, $batch->key, count( $batch->data ) ) );
			}

			$task_num = 0;

			add_action( 'shutdown', array( $this, 'shutdown_error_handler' ), 0 );
			foreach ( $batch->data as $key => $task ) {
				$this->increment_task_attempts( $batch, $key, $task );
				$attempt_num = $this->supports_attempts ? sprintf( ' Attempt number: %d.', rgar( $task, 'attempts', 1 ) ) : '';
				$this->log_debug( sprintf( '%s(): Processing task %d.%s', __METHOD__, ++ $task_num, $attempt_num ) );

				// Setting or refreshing the current batch before processing the task.
				$this->set_current_batch( $batch );
				$this->set_current_task( $task );

				$task = $this->can_process_task( $task, $batch, $task_num ) ? $this->task( $task ) : false;
				$this->set_current_task();

				if ( $task !== false ) {
					$this->log_debug( sprintf( '%s(): Keeping task %d in batch.', __METHOD__, $task_num ) );
					$batch->data[ $key ] = $task;
					// Pausing, so the task is not retried immediately.
					$this->pause( true );
				} else {
					$this->log_debug( sprintf( '%s(): Removing task %d from batch.', __METHOD__, $task_num ) );
					unset( $batch->data[ $key ] );
				}

				// Keep the batch up to date while processing it.
				if ( ! empty( $batch->data ) ) {
					$this->update( $batch->key, $batch->data );
				}

				// Let the server breathe a little.
				sleep( $throttle_seconds );

				// Batch limits reached, or pause or cancel requested.
				if ( ! $this->should_continue() ) {
					break;
				}
			}
			remove_action( 'shutdown', array( $this, 'shutdown_error_handler' ), 0 );

			$this->log_debug( sprintf( '%s(): Batch completed for %s.', __METHOD__, $this->action ) );

			// Delete current batch if fully processed.
			if ( empty( $batch->data ) ) {
				$this->delete( $batch->key );
			}
		} while ( ! $this->is_queue_empty() && $this->should_continue() );

		$this->set_current_batch();
		$this->unlock_process();

		// Start next batch or complete process.
		if ( $this->is_paused() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Processing is paused for %s.', __METHOD__, $this->action ) );
			$this->paused();
		} elseif ( ! $this->is_queue_empty() ) {
			$this->log_debug( sprintf( '%s(): Batches remain in the queue for %s.', __METHOD__, $this->action ) );
			$this->dispatch();
		} else {
			$this->log_debug( sprintf( '%s(): All batches completed for %s.', __METHOD__, $this->action ) );
			$this->complete();
		}

		if ( $is_cron ) {
			exit;
		}

		return $this->maybe_wp_die();
	}

	/**
	 * Increments the item attempts property and updates the batch in the database.
	 *
	 * @since 2.9.9
	 *
	 * @param object $batch The current batch.
	 * @param int    $key   The key used to access the task in the batch.
	 * @param mixed  $task  The current task from the batch.
	 */
	protected function increment_task_attempts( &$batch, $key, &$task ) {
		if ( ! $this->supports_attempts || ! is_array( $task ) ) {
			return;
		}

		$task['attempts']    = ( $task['attempts'] ?? 0 ) + 1;
		$batch->data[ $key ] = $task;
		$this->update( $batch->key, $batch->data );
	}

	/**
	 * Determines if the task can be processed based on its attempts property.
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
		if ( ! $this->supports_attempts ) {
			return true;
		}

		$attempts = rgar( $task, 'attempts', 1 );
		if ( $attempts === 1 ) { // Task is about to be processed for the first time.
			return true;
		}

		$max_attempts = 1;
		$identifier   = $this->get_identifier();

		/**
		 * Allows the number of retries to be modified before the task is abandoned.
		 *
		 * @since 2.9.9
		 *
		 * @param int    $max_attempts The maximum number of attempts allowed. Default: 1.
		 * @param mixed  $task         The task about to be processed.
		 * @param object $batch        The batch currently being processed.
		 * @param string $identifier   The string used to identify the type of background process.
		 */
		$max_attempts = apply_filters( 'gform_max_async_task_attempts', $max_attempts, $task, $batch, $identifier );

		if ( $attempts > $max_attempts ) {
			$this->log_debug( sprintf( '%s(): Aborting. Task %d attempted too many times. Attempt number: %d. Limit: %d.', __METHOD__, $task_num, $attempts, $max_attempts ) );

			return false;
		}

		return true;
	}

	/**
	 * Detects if an error occurred before the shutdown hook was triggered, and then triggers logging of the error details.
	 *
	 * @since 2.9.9
	 *
	 * @return void
	 */
	public function shutdown_error_handler() {
		if ( empty( $this->get_current_batch() ) ) {
			return;
		}

		$error = error_get_last();
		if ( empty( $error['type'] ) ) {
			return;
		}

		if ( ! in_array( $error['type'], array(
			E_ERROR,
			E_PARSE,
			E_USER_ERROR,
			E_COMPILE_ERROR,
			E_RECOVERABLE_ERROR,
		), true ) ) {
			return;
		}

		$this->handle_error( $error );
	}

	/**
	 * Logs the error.
	 *
	 * @since 2.9.9
	 *
	 * @param array $error The error returned by error_get_last().
	 *
	 * @return void
	 */
	protected function handle_error( $error ) {
		$batch = $this->get_current_batch();
		$this->log_error( sprintf( '%s(): Aborting. Error occurred during processing of batch %s; Tasks remaining: %d. Details: %s', __METHOD__, $batch->key, count( $batch->data ), print_r( $error, true ) ) );
		$this->unlock_process();
	}

	/**
	 * Determines if a batch has been updated.
	 *
	 * @since 2.9.7
	 *
	 * @param stdClass $batch The batch object.
	 *
	 * @return bool
	 */
	protected function has_batch_been_updated( $batch ) {
		return ! empty( $batch->timestamp_created ) && ! empty( $batch->timestamp_updated ) && ( $batch->timestamp_updated > $batch->timestamp_created );
	}

	/**
	 * Spawn a new background process on the multisite that scheduled the current task
	 *
	 * @param int $blog_id
	 *
	 * @since 2.3
	 */
	protected function spawn_multisite_child_process( $blog_id ) {
		$this->log_debug( sprintf( '%s(): Running for blog #%s.', __METHOD__, $blog_id ) );
		switch_to_blog( $blog_id );
		$this->unlock_process();
		$this->dispatch();
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @since 2.2
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @since 2.2
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Time exceeded.
	 *
	 * @since 2.2
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_time_exceeded', $return );
	}

	/**
	 * Complete.
	 *
	 * @since 2.2
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		delete_site_option( $this->get_status_key() );

		// Remove the cron healthcheck job from the cron schedule.
		$this->clear_scheduled_event();

		$this->completed();
	}

	/**
	 * Called when background process has completed.
	 *
	 * @since 2.9.7
	 */
	protected function completed() {
		do_action( $this->identifier . '_completed', $this->get_chain_id() );
	}

	/**
	 * Get the cron healthcheck interval in minutes.
	 *
	 * Default is 5 minutes, minimum is 1 minute.
	 *
	 * @since 2.9.7
	 *
	 * @return int
	 */
	public function get_cron_interval() {
		$interval = 5;

		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = $this->cron_interval;
		}

		$interval = apply_filters( $this->cron_interval_identifier, $interval );

		return is_int( $interval ) && 0 < $interval ? $interval : 5;
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @since 2.2
	 *
	 * @access public
	 *
	 * @param mixed $schedules Schedules.
	 *
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		$interval = $this->get_cron_interval();

		if ( 1 === $interval ) {
			$display = __( 'Every Minute', 'gravityforms' );
		} else {
			$display = sprintf( __( 'Every %d Minutes', 'gravityforms' ), $interval );
		}

		// Adds an "Every NNN Minute(s)" schedule to the existing cron schedules.
		$schedules[ $this->cron_interval_identifier ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			'display'  => $display,
		);

		return $schedules;
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 *
	 * @since 2.2
	 */
	public function handle_cron_healthcheck() {
		$this->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );
		GFCommon::record_cron_event( $this->cron_hook_identifier );

		if ( $this->is_processing() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Already processing for %s.', __METHOD__, $this->action ) );
			// Background process already running.
			exit;
		}

		if ( $this->is_queue_empty() ) {
			$this->log_debug( sprintf( '%s(): Aborting. Queue is empty for %s.', __METHOD__, $this->action ) );
			// No data to process.
			$this->clear_scheduled_event();
			exit;
		}

		if ( $this->is_paused() ) {
			if ( $this->is_pause_expired() ) {
				$this->resume( false );
			} else {
				$this->log_debug( sprintf( '%s(): Aborting. Processing is paused for %s.', __METHOD__, $this->action ) );
				exit;
			}
		}

		// Calling handle() directly instead of dispatch() because the Ajax request most likely failed with a cURL error.
		$this->handle();
	}

	/**
	 * Determines if the queue can be resumed based on how long ago it was paused.
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	protected function is_pause_expired() {
		if ( $this->get_status() === self::STATUS_PAUSED_NO_TS ) {
			$this->log_debug( __METHOD__ . '(): Processing was paused by an external method and will remain paused until $bp_object->resume() is called.' );

			return false;
		}

		$pause_timestamp = get_site_option( $this->get_identifier() . '_pause_timestamp' );
		if ( empty( $pause_timestamp ) || ! is_numeric( $pause_timestamp ) ) {
			$this->log_error( __METHOD__ . '(): Processing is paused and the expiry timestamp is not set or contains an invalid value: ' . var_export( $pause_timestamp, true ) );

			return true;
		}

		$paused_duration = time() - $pause_timestamp;
		$duration_limit  = ( $this->get_cron_interval() / 2 ) * MINUTE_IN_SECONDS;
		$is_expired      = ( $paused_duration >= $duration_limit );

		if ( ! $is_expired ) {
			$this->log_debug( sprintf( '%s(): Processing is paused and can resume after %s.', __METHOD__, wp_date( 'Y-m-d H:i:s', (int) ( $pause_timestamp + $duration_limit ) ) ) );
		}

		return $is_expired;
	}

	/**
	 * Schedule event
	 *
	 * @since 2.2
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			$this->log_debug( sprintf( '%s(): Scheduling cron event for %s.', __METHOD__, $this->action ) );
			wp_schedule_event(
				time() + ( $this->get_cron_interval() * MINUTE_IN_SECONDS ),
				$this->cron_interval_identifier,
				$this->cron_hook_identifier
			);
		}
	}

	/**
	 * Clear scheduled event
	 *
	 * @since 2.2
	 */
	protected function clear_scheduled_event() {
		$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

		if ( $timestamp ) {
			$this->log_debug( sprintf( '%s(): Clearing cron event for %s.', __METHOD__, $this->action ) );
			wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
		}
	}

	/**
	 * Clears all scheduled events.
	 *
	 * @since 2.3.1.x
	 */
	public function clear_scheduled_events() {
		wp_clear_scheduled_hook( $this->cron_hook_identifier );
	}

	/**
	 * Cancel Process
	 *
	 * Stop processing queue items, clear cronjob and delete batch.
	 *
	 * @since 2.2
	 */
	public function cancel_process() {
		$this->cancel();
	}

	/**
	 * Clears all batches from the queue.
	 *
	 * @since 2.3
	 *
	 * @param bool $all_blogs_in_network
	 *
	 * @return false|int
	 */
	public function clear_queue( $all_blogs_in_network = false ) {
		$this->data = array();

		return $this->delete_batches( $all_blogs_in_network );
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 2.2
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	abstract protected function task( $item );

	/**
	 * Maybe unserialize data, but not if an object.
	 *
	 * @since 2.9.7
	 *
	 * @param mixed      $data            Data to be unserialized.
	 * @param bool|array $allowed_classes Array of class names that can be unserialized.
	 *
	 * @return mixed
	 */
	protected static function maybe_unserialize( $data, $allowed_classes ) {
		if ( is_serialized( $data ) ) {
			$options = array();
			if ( is_bool( $allowed_classes ) || is_array( $allowed_classes ) ) {
				$options['allowed_classes'] = $allowed_classes;
			}

			return @unserialize( $data, $options ); // @phpcs:ignore
		}

		return $data;
	}

	/**
	 * Should any processing continue?
	 *
	 * @since 2.9.7
	 *
	 * @return bool
	 */
	public function should_continue() {
		/**
		 * Filter whether the current background process should continue running the task
		 * if there is data to be processed.
		 *
		 * If the processing time or memory limits have been exceeded, the value will be false.
		 * If pause or cancel have been requested, the value will be false.
		 *
		 * It is very unlikely that you would want to override a false value with true.
		 *
		 * If false is returned here, it does not necessarily mean background processing is
		 * complete. If there is batch data still to be processed and pause or cancel have not
		 * been requested, it simply means this background process should spawn a new process
		 * for the chain to continue processing and then close itself down.
		 *
		 * @since 2.9.7
		 *
		 * @param bool   $continue Should the current process continue processing the task?
		 * @param string $chain_id The current background process chain's ID.
		 *
		 * @return bool
		 */
		return apply_filters(
			$this->identifier . '_should_continue',
			! ( $this->time_exceeded() || $this->memory_exceeded() || $this->is_paused() || $this->is_cancelled() ),
			$this->get_chain_id()
		);
	}

	/**
	 * Get the string used to identify this type of background process.
	 *
	 * @since 2.9.7
	 *
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Return the current background process chain's ID.
	 *
	 * @since 2.9.7
	 * @since 2.9.8 Added the Ajax request action check.
	 *
	 * If the chain's ID hasn't been set before this function is first used,
	 * and hasn't been passed as a query arg during dispatch,
	 * the chain ID will be generated before being returned.
	 *
	 * @return string
	 */
	public function get_chain_id() {
		if ( empty( $this->chain_id ) && wp_doing_ajax() && rgar( $_REQUEST, 'action' ) === $this->identifier ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			check_ajax_referer( $this->identifier, 'nonce' );

			if ( ! empty( $_GET[ $this->get_chain_id_arg_name() ] ) ) {
				$chain_id = sanitize_key( $_GET[ $this->get_chain_id_arg_name() ] );

				if ( wp_is_uuid( $chain_id ) ) {
					$this->chain_id = $chain_id;

					return $this->chain_id;
				}
			}
		}

		if ( empty( $this->chain_id ) ) {
			$this->chain_id = wp_generate_uuid4();
		}

		return $this->chain_id;
	}

	/**
	 * Filters the query arguments used during an async request.
	 *
	 * @since 2.9.7
	 *
	 * @param array $args Current query args.
	 *
	 * @return array
	 */
	public function filter_dispatch_query_args( $args ) {
		$args[ $this->get_chain_id_arg_name() ] = $this->get_chain_id();

		return $args;
	}

	/**
	 * Filters the post arguments used during an async request.
	 *
	 * @since 2.9.10
	 *
	 * @param array $args Current post args.
	 *
	 * @return array
	 */
	public function filter_dispatch_post_args( $args ) {
		// Reducing timeout to help with form submission performance.
		$args['timeout'] = 0.01;

		// Blocking set to false prevents some issues such as cURL connection errors being reported, but can help with form submission performance, so only removing when debugging is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			unset( $args['blocking'] );
		}

		return $args;
	}

	/**
	 * Get the query arg name used for passing the chain ID to new processes.
	 *
	 * @since 2.9.7
	 *
	 * @return string
	 */
	private function get_chain_id_arg_name() {
		static $chain_id_arg_name;

		if ( ! empty( $chain_id_arg_name ) ) {
			return $chain_id_arg_name;
		}

		/**
		 * Filter the query arg name used for passing the chain ID to new processes.
		 *
		 * If you encounter problems with using the default query arg name, you can
		 * change it with this filter.
		 *
		 * @since 2.9.7
		 *
		 * @param string $chain_id_arg_name Default "chain_id".
		 *
		 * @return string
		 */
		$chain_id_arg_name = apply_filters( $this->identifier . '_chain_id_arg_name', self::CHAIN_ID_ARG_NAME );

		if ( ! is_string( $chain_id_arg_name ) || empty( $chain_id_arg_name ) ) {
			$chain_id_arg_name = self::CHAIN_ID_ARG_NAME;
		}

		return $chain_id_arg_name;
	}

	/**
	 * Allows filtering of the form before the task is processed.
	 *
	 * @since 2.6.9
	 *
	 * @param array $form The form being processed.
	 * @param array $entry The entry being processed.
	 *
	 * @return array
	 */
	public function filter_form( $form, $entry ) {
		return gf_apply_filters( array(
			'gform_form_pre_process_async_task',
			absint( rgar( $form, 'id' ) ),
		), $form, $entry );
	}

	/**
	 * Determines if the specified blog is suitable for batch processing.
	 *
	 * @since 2.8.16
	 *
	 * @param int $blog_id The blog ID.
	 *
	 * @return bool
	 */
	public function is_valid_blog( $blog_id ) {
		$site = get_site( $blog_id );

		return $site instanceof \WP_Site && ! $site->deleted && ! $site->archived && ! $site->spam;
	}

	/**
	 * Deletes the site batches when the site is deleted.
	 *
	 * @since 2.8.16
	 *
	 * @param WP_Site|int $old_site The deleted site object or ID.
	 *
	 * @return void
	 */
	public function delete_site_batches( $old_site ) {
		$blog_id = is_object( $old_site ) ? $old_site->blog_id : $old_site;
		$this->delete_batches( $blog_id );
	}

	/**
	 * Deletes batches from the database.
	 *
	 * @since 2.8.16
	 *
	 * @param bool|int $all_blogs_in_network True to delete batches for all blogs. False to delete batches for the current blog. A blog ID to delete batches for the specified blog.
	 *
	 * @return bool|int
	 */
	public function delete_batches( $all_blogs_in_network = false ) {
		global $wpdb;

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		} else {
			$table  = $wpdb->options;
			$column = 'option_name';
		}

		$key = $this->identifier . '_batch_';

		if ( is_bool( $all_blogs_in_network ) ) {
			$blog_id = $all_blogs_in_network ? 0 : get_current_blog_id();
		} else {
			$blog_id = absint( $all_blogs_in_network );
			if ( ! $blog_id ) {
				return false;
			}
		}

		if ( $blog_id ) {
			$key .= 'blog_id_' . $blog_id . '_';
		}

		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $wpdb->esc_like( $key ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->log_debug( sprintf( '%s(): %d batch(es) deleted with prefix %s.', __METHOD__, $result, $key ) );

		return $result;
	}

	/**
	 * Writes a message to the core log.
	 *
	 * @since 2.9.7
	 *
	 * @param string $message The message to be logged.
	 *
	 * @return void
	 */
	public function log_debug( $message ) {
		GFCommon::log_debug( $message );
	}

	/**
	 * Writes an error message to the core log.
	 *
	 * @since 2.9.9
	 *
	 * @param string $message The message to be logged.
	 *
	 * @return void
	 */
	public function log_error( $message ) {
		GFCommon::log_error( $message );
	}

}
