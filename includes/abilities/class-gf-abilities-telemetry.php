<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Counts ability executions for the weekly telemetry snapshot.
 *
 * Keeps one set of running totals per ability: calls, errors bucketed by
 * error code, permission denials, and summed duration. The totals only
 * grow, like the email and REST API counters in GFCommon, and the telemetry
 * server computes period deltas. Nothing from the input or the result is
 * stored, and nothing is counted unless the site has enabled data
 * collection.
 *
 * Counters use a plain read, add, write on one option. Concurrent requests
 * can lose an increment, which is the same ceiling the GFCommon counters
 * accept.
 *
 * @since 3.2.2
 */
class GF_Abilities_Telemetry {

	/**
	 * Option that holds the per-ability totals.
	 *
	 * @since 3.2.2
	 */
	const OPTION = 'gf_abilities_telemetry';

	/**
	 * Distinct error codes kept per ability before the rest fall into "other".
	 *
	 * @since 3.2.2
	 */
	const MAX_ERROR_BUCKETS = 20;

	/**
	 * Execution start times keyed by ability object hash.
	 *
	 * @since 3.2.2
	 *
	 * @var array
	 */
	private $timers = array();

	/**
	 * Add the abilities section to the telemetry snapshot.
	 *
	 * Registered even when MCP is off, so the snapshot reports that state.
	 *
	 * @since 3.2.2
	 *
	 * @return void
	 */
	public function register_snapshot_hook() {
		add_filter( 'gform_telemetry_snapshot_data', array( $this, 'add_snapshot_callback' ) );
	}

	/**
	 * Count ability executions when the site allows data collection.
	 *
	 * @since 3.2.2
	 *
	 * @return void
	 */
	public function register_execution_hooks() {
		if ( ! $this->is_data_collection_allowed() ) {
			return;
		}

		add_action( 'gform_before_execute_ability', array( $this, 'before_execute' ), 10, 3 );
		add_action( 'gform_after_execute_ability', array( $this, 'after_execute' ), 10, 4 );
		add_action( 'gform_ability_permission_denied', array( $this, 'permission_denied' ), 10, 1 );
	}

	/**
	 * Whether the site has enabled data collection on the Settings page.
	 *
	 * @since 3.2.2
	 *
	 * @return bool
	 */
	public function is_data_collection_allowed() {
		return (bool) get_option( 'rg_gforms_dataCollection', false );
	}

	/**
	 * Start the execution timer.
	 *
	 * @since 3.2.2
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $input        The input passed to the ability.
	 * @param object $ability      The ability instance being executed.
	 *
	 * @return void
	 */
	public function before_execute( $ability_name, $input, $ability ) {
		$this->timers[ spl_object_hash( $ability ) ] = microtime( true );
	}

	/**
	 * Count a finished execution, its duration, and its error code.
	 *
	 * @since 3.2.2
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $input        The input passed to the ability.
	 * @param mixed  $result       The execution result, a WP_Error on failure.
	 * @param object $ability      The ability instance that was executed.
	 *
	 * @return void
	 */
	public function after_execute( $ability_name, $input, $result, $ability ) {
		$hash     = spl_object_hash( $ability );
		$duration = isset( $this->timers[ $hash ] ) ? (int) round( ( microtime( true ) - $this->timers[ $hash ] ) * 1000 ) : 0;
		unset( $this->timers[ $hash ] );

		$counts = $this->get_counts( $ability_name );

		++$counts['calls'];
		$counts['duration_ms'] += $duration;

		if ( is_wp_error( $result ) ) {
			$code = $this->error_bucket( $counts['errors'], $result->get_error_code() );

			$counts['errors'][ $code ] = isset( $counts['errors'][ $code ] ) ? $counts['errors'][ $code ] + 1 : 1;
		}

		$this->save_counts( $ability_name, $counts );
	}

	/**
	 * Count a denied execution attempt.
	 *
	 * Denials never reach after_execute, so they are not part of "calls".
	 *
	 * @since 3.2.2
	 *
	 * @param string $ability_name The registered ability name.
	 *
	 * @return void
	 */
	public function permission_denied( $ability_name ) {
		$counts = $this->get_counts( $ability_name );

		++$counts['denied'];

		$this->save_counts( $ability_name, $counts );
	}

	/**
	 * Register the snapshot callback with the telemetry service.
	 *
	 * @since 3.2.2
	 *
	 * @param array $callbacks Callbacks that each return snapshot data.
	 *
	 * @return array
	 */
	public function add_snapshot_callback( $callbacks ) {
		$callbacks[] = array( $this, 'get_snapshot_data' );

		return $callbacks;
	}

	/**
	 * Get the abilities section of the telemetry snapshot.
	 *
	 * @since 3.2.2
	 *
	 * @return array Empty when data collection is off.
	 */
	public function get_snapshot_data() {
		if ( ! $this->is_data_collection_allowed() ) {
			return array();
		}

		$usage = get_option( self::OPTION, array() );

		return array(
			'abilities' => array(
				'mcp_enabled'   => \GF_MCP_Settings::is_enabled(),
				'endpoint_mode' => \GF_MCP_Settings::get_endpoint_mode(),
				'enabled_tools' => \GF_MCP_Settings::get_enabled_tools(),
				'usage'         => is_array( $usage ) ? $usage : array(),
			),
		);
	}

	/**
	 * Get the stored totals for one ability.
	 *
	 * @since 3.2.2
	 *
	 * @param string $ability_name The registered ability name.
	 *
	 * @return array
	 */
	private function get_counts( $ability_name ) {
		$usage  = get_option( self::OPTION, array() );
		$counts = is_array( $usage ) && isset( $usage[ $ability_name ] ) && is_array( $usage[ $ability_name ] ) ? $usage[ $ability_name ] : array();

		return array_merge(
			array(
				'calls'       => 0,
				'denied'      => 0,
				'duration_ms' => 0,
				'errors'      => array(),
			),
			$counts
		);
	}

	/**
	 * Store the totals for one ability.
	 *
	 * @since 3.2.2
	 *
	 * @param string $ability_name The registered ability name.
	 * @param array  $counts       The totals to store.
	 *
	 * @return void
	 */
	private function save_counts( $ability_name, $counts ) {
		$usage = get_option( self::OPTION, array() );

		if ( ! is_array( $usage ) ) {
			$usage = array();
		}

		$usage[ $ability_name ] = $counts;

		update_option( self::OPTION, $usage, false );
	}

	/**
	 * Pick the bucket an error code is counted under.
	 *
	 * Codes are trimmed to a key-safe string. Once an ability has
	 * MAX_ERROR_BUCKETS distinct codes, new ones count as "other" so the
	 * option cannot grow without bound.
	 *
	 * @since 3.2.2
	 *
	 * @param array  $errors The ability's existing error buckets.
	 * @param mixed  $code   The WP_Error code.
	 *
	 * @return string
	 */
	private function error_bucket( $errors, $code ) {
		$code = substr( preg_replace( '/[^a-z0-9_\-]/', '_', strtolower( (string) $code ) ), 0, 64 );

		if ( '' === $code ) {
			$code = 'unknown';
		}

		if ( ! isset( $errors[ $code ] ) && count( $errors ) >= self::MAX_ERROR_BUCKETS ) {
			return 'other';
		}

		return $code;
	}
}
