<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Logs ability executions to a dedicated Gravity Forms logging subsystem.
 *
 * Provides an audit trail for the abilities/MCP system: every execution is
 * logged with the acting user, write and destructive operations are logged
 * at INFO level, and failures and permission denials are logged at ERROR
 * level. Input values are reduced to an IDs-only summary so form field
 * values (PII) never reach log files.
 *
 * @since 3.1.0
 */
class GF_Abilities_Logger {

	/**
	 * The logging subsystem slug registered with GFLogging.
	 *
	 * @since 3.1.0
	 */
	const LOG_SLUG = 'gravityformsabilities';

	/**
	 * Input keys whose values are safe to include in log messages.
	 *
	 * Everything else is logged as key name only with the value redacted,
	 * so entry payloads, field values, and other user-provided content
	 * never reach log files.
	 *
	 * @since 3.1.0
	 *
	 * @var string[]
	 */
	private static $safe_input_keys = array(
		'active',
		'addon_slug',
		'created_by',
		'date_created',
		'entry_id',
		'feed_id',
		'force',
		'form_id',
		'form_ids',
		'id',
		'is_active',
		'is_trash',
		'note_type',
		'notification_ids',
		'sort_column',
		'sort_dir',
		'status',
		'trash',
	);

	/**
	 * Execution start times keyed by ability object hash.
	 *
	 * @since 3.1.0
	 *
	 * @var array
	 */
	private $timers = array();

	/**
	 * Register hooks that expose the logging subsystem.
	 *
	 * These run even when MCP is disabled so the subsystem remains visible
	 * and manageable on the Forms > Settings > Logging page.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function register_logging_hooks() {
		add_filter( 'gform_logging_supported', array( $this, 'filter_logging_supported' ) );
		add_action( 'gform_post_upgrade', array( $this, 'maybe_seed_logger_setting' ), 10, 0 );
	}

	/**
	 * Register hooks that log ability executions.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function register_execution_hooks() {
		add_action( 'gform_before_execute_ability', array( $this, 'before_execute' ), 10, 3 );
		add_action( 'gform_after_execute_ability', array( $this, 'after_execute' ), 10, 4 );
		add_action( 'gform_ability_permission_denied', array( $this, 'permission_denied' ), 10, 3 );
	}

	/**
	 * Add the abilities subsystem to the supported logging plugins.
	 *
	 * @since 3.1.0
	 *
	 * @param array $plugins Registered logging subsystems.
	 *
	 * @return array
	 */
	public function filter_logging_supported( $plugins ) {
		$plugins[ self::LOG_SLUG ] = 'Gravity Forms Abilities (MCP)';

		return $plugins;
	}

	/**
	 * Seed the logger setting when logging is already enabled site-wide.
	 *
	 * GFLogging only enables loggers for known subsystems when the global
	 * logging toggle is switched on. If logging was enabled before this
	 * subsystem existed, the slug has no saved setting and would stay
	 * silent until logging is toggled again, so it is seeded the same way
	 * GFLogging::enable_all_loggers() would. Runs on gform_post_upgrade —
	 * the only window where an existing setup gains this subsystem — and
	 * is idempotent in place of a version_compare gate.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function maybe_seed_logger_setting() {
		if ( ! get_option( 'gform_enable_logging' ) || ! function_exists( 'gf_logging' ) ) {
			return;
		}

		$setting = gf_logging()->get_plugin_setting( self::LOG_SLUG );

		if ( ! empty( $setting ) ) {
			return;
		}

		\GFLogging::include_logger();

		$settings = gf_logging()->get_plugin_settings();

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$random = function_exists( 'random_bytes' ) ? random_bytes( 12 ) : wp_generate_password( 24, true, true );

		$settings[ self::LOG_SLUG ] = array(
			'log_level' => (string) \KLogger::DEBUG,
			'enable'    => '1',
			'file_name' => sha1( self::LOG_SLUG . $random ),
		);

		gf_logging()->update_plugin_settings( $settings );
	}

	/**
	 * Log the start of an ability execution.
	 *
	 * @since 3.1.0
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $input        The input passed to the ability.
	 * @param object $ability      The ability instance being executed.
	 *
	 * @return void
	 */
	public function before_execute( $ability_name, $input, $ability ) {
		$this->timers[ spl_object_hash( $ability ) ] = microtime( true );

		$this->log(
			sprintf(
				'Executing ability "%s" %s input=%s',
				$ability_name,
				$this->get_user_context(),
				$this->summarize_input( $input )
			)
		);
	}

	/**
	 * Log the result of an ability execution.
	 *
	 * Failures are logged at ERROR level, successful write and destructive
	 * operations at INFO level, and successful read-only operations at
	 * DEBUG level.
	 *
	 * @since 3.1.0
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $input        The input passed to the ability.
	 * @param mixed  $result       The execution result (may be a WP_Error).
	 * @param object $ability      The ability instance that was executed.
	 *
	 * @return void
	 */
	public function after_execute( $ability_name, $input, $result, $ability ) {
		$hash     = spl_object_hash( $ability );
		$duration = '';

		if ( isset( $this->timers[ $hash ] ) ) {
			$duration = sprintf( ' duration=%dms', round( ( microtime( true ) - $this->timers[ $hash ] ) * 1000 ) );
			unset( $this->timers[ $hash ] );
		}

		$tag     = $this->get_operation_tag( $ability );
		$context = sprintf(
			'Ability "%s" [%s] %s input=%s',
			$ability_name,
			$tag,
			$this->get_user_context(),
			$this->summarize_input( $input )
		);

		if ( is_wp_error( $result ) ) {
			$this->log(
				sprintf( '%s result=error code=%s message=%s%s', $context, $result->get_error_code(), $result->get_error_message(), $duration ),
				'error'
			);

			return;
		}

		$this->log(
			sprintf( '%s result=ok%s', $context, $duration ),
			'read' === $tag ? 'debug' : 'info'
		);
	}

	/**
	 * Log a denied ability execution attempt.
	 *
	 * @since 3.1.0
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $input        The input passed to the ability.
	 * @param object $ability      The ability instance that was denied.
	 *
	 * @return void
	 */
	public function permission_denied( $ability_name, $input, $ability ) {
		$this->log(
			sprintf(
				'Permission denied for ability "%s" [%s] %s input=%s',
				$ability_name,
				$this->get_operation_tag( $ability ),
				$this->get_user_context(),
				$this->summarize_input( $input )
			),
			'error'
		);
	}

	/**
	 * Get the operation tag for an ability based on its annotations.
	 *
	 * @since 3.1.0
	 *
	 * @param object $ability The ability instance.
	 *
	 * @return string One of 'read', 'write', or 'destructive'.
	 */
	private function get_operation_tag( $ability ) {
		$annotations = array();

		if ( is_object( $ability ) && method_exists( $ability, 'get_meta_item' ) ) {
			$annotations = (array) $ability->get_meta_item( 'annotations', array() );
		}

		if ( ! empty( $annotations['destructive'] ) ) {
			return 'destructive';
		}

		return empty( $annotations['readonly'] ) ? 'write' : 'read';
	}

	/**
	 * Get the acting user context for log messages.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private function get_user_context() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return 'user=0';
		}

		$user = get_user_by( 'id', $user_id );

		return $user ? sprintf( 'user=%d (%s)', $user_id, $user->user_login ) : sprintf( 'user=%d', $user_id );
	}

	/**
	 * Summarize ability input for logging without exposing field values.
	 *
	 * Values are only included for whitelisted keys (IDs, flags, and other
	 * non-PII parameters). All other keys are listed with their values
	 * redacted so the shape of the request remains visible.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $input The ability input.
	 *
	 * @return string
	 */
	private function summarize_input( $input ) {
		if ( ! is_array( $input ) || empty( $input ) ) {
			return '{}';
		}

		$safe_keys = $this->get_safe_input_keys();
		$parts     = array();

		foreach ( $input as $key => $value ) {
			$parts[] = $key . '=' . $this->summarize_value( $key, $value, $safe_keys );
		}

		return '{' . implode( ', ', $parts ) . '}';
	}

	/**
	 * Get the input keys whose values may appear in log messages.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	private function get_safe_input_keys() {
		/**
		 * Filters the input keys whose values are written to the abilities log.
		 *
		 * Values for keys not in this list are logged as [redacted]. Add-ons
		 * registering abilities with their own identifier-style inputs can add
		 * those keys so their audit log lines stay meaningful. Never add keys
		 * that can carry user-submitted content.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $safe_input_keys Input keys safe to log verbatim.
		 *
		 * @return string[]
		 *
		 * @example
		 * add_filter( 'gform_abilities_log_safe_input_keys', function( $keys ) {
		 *     $keys[] = 'subscriber_id';
		 *     return $keys;
		 * } );
		 */
		$safe_keys = apply_filters( 'gform_abilities_log_safe_input_keys', self::$safe_input_keys );

		return is_array( $safe_keys ) ? $safe_keys : self::$safe_input_keys;
	}

	/**
	 * Summarize a single input value.
	 *
	 * @since 3.1.0
	 *
	 * @param string   $key       The input key.
	 * @param mixed    $value     The input value.
	 * @param string[] $safe_keys Keys whose values may be logged.
	 *
	 * @return string
	 */
	private function summarize_value( $key, $value, $safe_keys ) {
		if ( ! in_array( $key, $safe_keys, true ) ) {
			return '[redacted]';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_scalar( $value ) ) {
			return self::scrub_log_scalar( $value );
		}

		if ( is_array( $value ) ) {
			$scalars = array_filter( $value, 'is_scalar' );

			if ( count( $scalars ) === count( $value ) && count( $value ) <= 10 ) {
				return '[' . implode( ',', array_map( array( __CLASS__, 'scrub_log_scalar' ), $value ) ) . ']';
			}

			return count( $value ) . ' items';
		}

		return '[redacted]';
	}

	/**
	 * Make a scalar safe for a single log line.
	 *
	 * Removes ASCII control characters and caps the length, so a caller-controlled value cannot forge
	 * log records.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $value A scalar value.
	 *
	 * @return string
	 */
	private static function scrub_log_scalar( $value ) {
		$string = preg_replace( '/[\x00-\x1F\x7F]/', ' ', (string) $value );

		return \GFCommon::safe_substr( (string) $string, 0, 200 );
	}

	/**
	 * Write a message to the abilities log.
	 *
	 * @since 3.1.0
	 *
	 * @param string $message The log message.
	 * @param string $level   The log level: 'debug', 'info', or 'error'.
	 *
	 * @return void
	 */
	private function log( $message, $level = 'debug' ) {
		if ( ! class_exists( 'GFLogging' ) ) {
			return;
		}

		\GFLogging::include_logger();

		$levels = array(
			'debug' => \KLogger::DEBUG,
			'info'  => \KLogger::INFO,
			'error' => \KLogger::ERROR,
		);

		\GFLogging::log_message( self::LOG_SLUG, $message, isset( $levels[ $level ] ) ? $levels[ $level ] : \KLogger::DEBUG );
	}
}
