<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Registers Gravity Forms ability definitions.
 *
 * @since 3.1.0
 */
class GF_Abilities_Registry {

	/**
	 * Get all ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		$raw_definitions = array();
		$definition_sets = array(
			GF_Abilities_Definitions_Forms::get_definitions(),
			GF_Abilities_Definitions_Entries::get_definitions(),
			GF_Abilities_Definitions_Submissions::get_definitions(),
			GF_Abilities_Definitions_Feeds::get_definitions(),
			GF_Abilities_Definitions_Notifications::get_definitions(),
			GF_Abilities_Definitions_Confirmations::get_definitions(),
			GF_Abilities_Definitions_System::get_definitions(),
			GF_Abilities_Definitions_Notes::get_definitions(),
		);

		foreach ( $definition_sets as $definition_set ) {
			foreach ( $definition_set as $definition ) {
				$raw_definitions[] = $definition;
			}
		}

		$core_security = array();

		foreach ( $raw_definitions as $definition ) {
			if ( isset( $definition['name'], $definition['args'] ) && is_array( $definition['args'] ) ) {
				$core_security[ $definition['name'] ] = array(
					'capability'       => isset( $definition['args']['capability'] ) ? $definition['args']['capability'] : '',
					'execute_callback' => isset( $definition['args']['execute_callback'] ) ? $definition['args']['execute_callback'] : null,
				);
			}
		}

		/**
		 * Filters all ability definitions before per-definition processing.
		 *
		 * Add-ons can add, remove, or reorder definitions. The capability and execute callback of a core
		 * ability stay immutable, so a filter cannot weaken a shipped ability.
		 *
		 * @since 3.1.0
		 *
		 * @param array[] $raw_definitions Array of raw definition arrays, each with 'name' and 'args' keys.
		 *
		 * @return array[] Filtered array of raw definition arrays.
		 *
		 * @example
		 * add_filter( 'gform_abilities_definitions', function( $definitions ) {
		 *     $definitions[] = [
		 *         'name' => 'gravityforms/myaddon/my-action',
		 *         'args' => [
		 *             'label'            => __( 'My Action', 'gravityforms-myaddon' ),
		 *             'description'      => __( 'Does something useful.', 'gravityforms-myaddon' ),
		 *             'summary'          => __( 'Short, plain-language line shown as the settings tooltip.', 'gravityforms-myaddon' ),
		 *             'capability'       => 'gravityforms_edit_forms',
		 *             'execute_callback' => [ $this, 'handle_my_action' ],
		 *         ],
		 *     ];
		 *     return $definitions;
		 * } );
		 */
		$filtered_definitions = apply_filters( 'gform_abilities_definitions', $raw_definitions );

		if ( ! is_array( $filtered_definitions ) ) {
			$filtered_definitions = $raw_definitions;
		}

		$definitions = array();

		foreach ( $filtered_definitions as $definition ) {
			if ( ! is_array( $definition ) || ! isset( $definition['name'], $definition['args'] ) ) {
				continue;
			}

			if ( isset( $core_security[ $definition['name'] ] ) && is_array( $definition['args'] ) ) {
				$definition['args']['capability']       = $core_security[ $definition['name'] ]['capability'];
				$definition['args']['execute_callback'] = $core_security[ $definition['name'] ]['execute_callback'];
			}

			$definitions[] = self::definition( $definition['name'], $definition['args'] );
		}

		return $definitions;
	}

	/**
	 * Build an ability definition.
	 *
	 * @since 3.1.0
	 *
	 * @param string $name The ability name.
	 * @param array  $args The ability configuration.
	 *
	 * @return array
	 */
	protected static function definition( $name, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'label'            => '',
				'description'      => '',
				'summary'          => '',
				'category'         => '',
				'execute_callback' => null,
				'capability'       => '',
				'input_schema'     => array(),
				'output_schema'    => array(),
				'readonly'         => false,
				'destructive'      => false,
				'idempotent'       => false,
				'mcp'              => array(),
			)
		);

		// A defined-but-empty input schema makes the ability unusable through MCP:
		// WP_Ability::execute() rejects any input — including the empty object MCP
		// clients always send — when no schema exists. Default no-input abilities
		// to an explicit empty-object schema instead.
		if ( empty( $args['input_schema'] ) ) {
			$args['input_schema'] = array(
				'type'                 => 'object',
				'default'              => (object) array(),
				'properties'           => array(),
				'additionalProperties' => false,
			);
		}

		$definition = array(
			'name'                => $name,
			'label'               => $args['label'],
			'description'         => $args['description'],
			'category'            => $args['category'],
			'ability_class'       => GF_Ability::class,
			'execute_callback'    => $args['execute_callback'],
			'permission_callback' => static function () use ( $args ) {
				$result = \GFCommon::current_user_can_any( $args['capability'] );
				return is_wp_error( $result ) ? $result : (bool) $result;
			},
			'input_schema'        => $args['input_schema'],
			'output_schema'       => $args['output_schema'],
			'meta'                => array(
				'mcp'          => array_merge(
					array( 'public' => ! \GF_MCP_Settings::is_dedicated_endpoint() ),
					$args['mcp']
				),
				'annotations'  => array(
					'readonly'    => (bool) $args['readonly'],
					'destructive' => (bool) $args['destructive'],
					'idempotent'  => (bool) $args['idempotent'],
				),
				// Plain-language, admin-facing one-liner for the settings UI tooltip.
				// Distinct from `description`, which is verbose agent-facing copy.
				// Stored in meta because the Abilities API rejects unknown top-level
				// properties but treats meta as a free-form bucket.
				'summary'      => $args['summary'],
				'show_in_rest' => true,
			),
		);

		$original_name                = $definition['name'];
		$original_permission_callback = $definition['permission_callback'];
		$original_ability_class       = $definition['ability_class'];
		$original_execute_callback    = $definition['execute_callback'];

		/**
		 * Filters a single ability definition after normalization.
		 *
		 * A filter can change properties such as label, description, or schema. The name,
		 * permission_callback, ability_class, and execute_callback stay immutable, so a filter
		 * cannot weaken the capability gate or replace the callback.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $definition The normalized ability definition.
		 * @param string $name       The ability name (e.g. 'gravityforms/system-info').
		 *
		 * @return array The filtered ability definition.
		 *
		 * @example
		 * add_filter( 'gform_ability_definition', function( $definition, $name ) {
		 *     if ( $name === 'gravityforms/forms-delete' ) {
		 *         $definition['label'] = __( 'Trash Form', 'gravityforms' );
		 *     }
		 *     return $definition;
		 * }, 10, 2 );
		 */
		$definition = apply_filters( 'gform_ability_definition', $definition, $name );

		// Ability name is immutable — restore if changed by filter.
		$definition['name'] = $original_name;

		$definition['permission_callback'] = $original_permission_callback;

		$definition['ability_class'] = $original_ability_class;

		$definition['execute_callback'] = $original_execute_callback;

		return $definition;
	}
}
