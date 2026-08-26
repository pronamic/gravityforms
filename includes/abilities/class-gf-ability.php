<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Extends the WordPress ability object with Gravity Forms-specific guards and lifecycle hooks.
 *
 * @since 3.1.0
 */
class GF_Ability extends \WP_Ability {
	/**
	 * Check ability permissions.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return bool|\WP_Error True to allow, false to deny, or a WP_Error with a denial
	 *                        reason. Do not narrow to bool: a WP_Error return would then
	 *                        raise a TypeError at this boundary.
	 */
	public function check_permissions( $input = array() ) {
		if ( $this->get_submissions_block() ) {
			return false;
		}

		if ( $this->is_tool_disabled() ) {
			return false;
		}

		return parent::check_permissions( $input );
	}

	/**
	 * Determine whether this ability is disabled by the per-tool MCP settings.
	 *
	 * Enforced at execution time so the gate holds on every surface — the
	 * Abilities REST "run" route, MCP, and the dedicated server — rather than
	 * relying on a registration-time flag that is fixed when the ability is
	 * registered. A tool is permitted only when the admin has enabled it; the
	 * master MCP setting is already accounted for by
	 * GF_MCP_Settings::is_tool_enabled(). Fails closed: if the settings class
	 * is unavailable the tool is treated as disabled, so a partial bootstrap
	 * can never leave the gate open.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	protected function is_tool_disabled() {
		if ( ! class_exists( '\GF_MCP_Settings' ) ) {
			return true;
		}

		return ! \GF_MCP_Settings::is_tool_enabled( $this->get_name() );
	}

	/**
	 * Execute the ability after input validation and permission checks.
	 *
	 * Overridden so denials from the parent's permission gate fire the
	 * gform_ability_permission_denied action; the parent returns its error
	 * before do_execute() is ever reached.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $input The ability input.
	 *
	 * @return mixed
	 */
	public function execute( $input = null ) {
		$result = parent::execute( $input );

		if ( is_wp_error( $result ) && 'ability_invalid_permissions' === $result->get_error_code() ) {
			/** This action is documented in includes/abilities/class-gf-ability.php */
			do_action( 'gform_ability_permission_denied', $this->get_name(), is_array( $input ) ? $input : array(), $this );
		}

		return $result;
	}

	/**
	 * Execute the ability after re-checking permissions.
	 *
	 * This stays public so the tests can drive it directly. Production code must call execute()
	 * instead, which adds the schema validation this method skips.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return mixed
	 */
	public function do_execute( $input = array() ) {
		// Zero-arg calls receive the schema root default verbatim from core's
		// normalize_input() — a stdClass, while handlers and hooks expect arrays.
		if ( $input instanceof \stdClass ) {
			$input = (array) $input;
		}

		$permissions_passed = ( true === $this->check_permissions( $input ) );

		if ( ! $permissions_passed ) {
			/**
			 * Fires when an ability execution is blocked by a failed permission check.
			 *
			 * @since 3.1.0
			 *
			 * @param string     $ability_name   The registered ability name.
			 * @param array      $input          The input passed to the ability.
			 * @param GF_Ability $ability_object The ability instance that was denied.
			 *
			 * @example
			 * add_action( 'gform_ability_permission_denied', function( $name, $input, $ability ) {
			 *     GFCommon::log_error( "Permission denied for ability: {$name}" );
			 * }, 10, 3 );
			 */
			do_action( 'gform_ability_permission_denied', $this->get_name(), $input, $this );

			return new \WP_Error(
				'gf_ability_permission_denied',
				sprintf(
					/* translators: %s: ability name */
					__( 'Permission denied for ability "%s".', 'gravityforms' ),
					$this->get_name()
				)
			);
		}

		/**
		 * Fires before a Gravity Forms ability is executed.
		 *
		 * This action fires only when the ability's permission check passes.
		 * It cannot block or modify execution.
		 *
		 * @since 3.1.0
		 *
		 * @param string     $ability_name   The registered ability name.
		 * @param array      $input          The input passed to the ability.
		 * @param GF_Ability $ability_object  The ability instance being executed.
		 *
		 * @example
		 * add_action( 'gform_before_execute_ability', function( $name, $input, $ability ) {
		 *     GFCommon::log_debug( "Executing ability: {$name}" );
		 * }, 10, 3 );
		 */
		do_action( 'gform_before_execute_ability', $this->get_name(), $input, $this );

		/**
		 * Short-circuits ability execution.
		 *
		 * Runs after the permission check passes, so it can only restrict or
		 * substitute — never bypass authorization. Return a WP_Error to block
		 * execution (rate limiting, maintenance freezes, per-ability kill
		 * switches), or any other non-null value to use it as the result
		 * without running the ability callback (caching, stubbing). Substituted
		 * results are still validated against the ability's output schema.
		 * Blocked and substituted executions fire gform_after_execute_ability
		 * normally, so they remain visible to the audit log.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed      $pre            Null to run the ability normally. WP_Error to block, any other non-null value to short-circuit with that result.
		 * @param string     $ability_name   The registered ability name.
		 * @param array      $input          The input passed to the ability.
		 * @param GF_Ability $ability_object The ability instance being executed.
		 *
		 * @example
		 * add_filter( 'gform_pre_execute_ability', function( $pre, $name, $input ) {
		 *     if ( 'gravityforms/entries-delete' === $name && my_plugin_is_freeze_window() ) {
		 *         return new WP_Error( 'my_plugin_freeze', __( 'Deletions are paused during the deploy window.', 'my-plugin' ) );
		 *     }
		 *     return $pre;
		 * }, 10, 3 );
		 */
		$result = apply_filters( 'gform_pre_execute_ability', null, $this->get_name(), $input, $this );

		if ( null === $result ) {
			$result = parent::do_execute( $input );
		}

		/**
		 * Fires after a Gravity Forms ability has executed.
		 *
		 * This action fires for both successful results and WP_Error returns,
		 * but only when the ability's permission check passed.
		 * It cannot modify the result.
		 *
		 * @since 3.1.0
		 *
		 * @param string     $ability_name   The registered ability name.
		 * @param array      $input          The input passed to the ability.
		 * @param mixed      $result         The execution result (may be a WP_Error).
		 * @param GF_Ability $ability_object  The ability instance that was executed.
		 *
		 * @example
		 * add_action( 'gform_after_execute_ability', function( $name, $input, $result, $ability ) {
		 *     if ( is_wp_error( $result ) ) {
		 *         GFCommon::log_error( "Ability {$name} failed: " . $result->get_error_message() );
		 *     }
		 * }, 10, 4 );
		 */
		do_action( 'gform_after_execute_ability', $this->get_name(), $input, $result, $this );

		// Core's invoke_callback() wraps callback throwables with the exception
		// message verbatim — absolute server paths included — and that text is
		// relayed to connected MCP clients. Log the real message and return a
		// generic error instead; deliberate WP_Error returns from handlers pass
		// through untouched. The after-execute hook above still receives the raw
		// error so audit listeners see the true failure.
		if ( is_wp_error( $result ) && 'ability_callback_exception' === $result->get_error_code() ) {
			\GFCommon::log_error( __METHOD__ . '(): ' . $result->get_error_message() );

			return new \WP_Error(
				'gf_ability_execution_failed',
				sprintf(
					/* translators: %s: ability name */
					__( 'Ability "%s" failed unexpectedly. Check the Gravity Forms logs for details.', 'gravityforms' ),
					$this->get_name()
				)
			);
		}

		return $result;
	}

	protected function get_submissions_block() {
		return gf_upgrade()->get_submissions_block();
	}
}
