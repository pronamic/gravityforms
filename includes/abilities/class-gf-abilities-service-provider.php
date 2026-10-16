<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use WP\MCP\Domain\Utils\McpNameSanitizer;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\HttpTransport;

/**
 * Registers Gravity Forms abilities and categories with the Abilities API.
 *
 * @since 3.1.0
 */
class GF_Abilities_Service_Provider extends GF_Service_Provider {

	/**
	 * Container service name for the abilities logger.
	 *
	 * @since 3.1.0
	 */
	const ABILITIES_LOGGER = 'abilities_logger';

	protected static function get_categories() {
		$categories = array(
			'gravityforms-forms'         => array(
				'label'       => __( 'Gravity Forms — Forms', 'gravityforms' ),
				'description' => __( 'Abilities for creating, reading, updating, and deleting forms.', 'gravityforms' ),
			),
			'gravityforms-entries'       => array(
				'label'       => __( 'Gravity Forms — Entries', 'gravityforms' ),
				'description' => __( 'Abilities for managing form entries (submissions data).', 'gravityforms' ),
			),
			'gravityforms-notes'         => array(
				'label'       => __( 'Gravity Forms — Notes', 'gravityforms' ),
				'description' => __( 'Abilities for managing entry notes.', 'gravityforms' ),
			),
			'gravityforms-submissions'   => array(
				'label'       => __( 'Gravity Forms — Submissions', 'gravityforms' ),
				'description' => __( 'Abilities for submitting forms and validating input.', 'gravityforms' ),
			),
			'gravityforms-feeds'         => array(
				'label'       => __( 'Gravity Forms — Feeds', 'gravityforms' ),
				'description' => __( 'Abilities for managing add-on feeds (integrations connected to forms).', 'gravityforms' ),
			),
			'gravityforms-notifications' => array(
				'label'       => __( 'Gravity Forms — Notifications', 'gravityforms' ),
				'description' => __( 'Abilities for managing form notification settings.', 'gravityforms' ),
			),
			'gravityforms-confirmations' => array(
				'label'       => __( 'Gravity Forms — Confirmations', 'gravityforms' ),
				'description' => __( 'Abilities for managing form confirmations (the response shown after submission).', 'gravityforms' ),
			),
			'gravityforms-system'        => array(
				'label'       => __( 'Gravity Forms — System', 'gravityforms' ),
				'description' => __( 'Abilities for plugin settings, system status, and diagnostics.', 'gravityforms' ),
			),
			'gravityforms-addons'        => array(
				'label'       => __( 'Gravity Forms — Add-ons', 'gravityforms' ),
				'description' => __( 'Abilities registered by Gravity Forms add-ons and third-party extensions.', 'gravityforms' ),
			),
		);

		/**
		 * Filters the ability categories before registration.
		 *
		 * Allows add-ons and third-party code to add, modify, or remove ability
		 * categories. If the filter returns a non-array value, the original
		 * categories are used as a fallback.
		 *
		 * @since 3.1.0
		 *
		 * @param array $categories Associative array of category slug => category config.
		 *
		 * @return array Filtered associative array of category slug => category config.
		 *
		 * @example
		 * add_filter( 'gform_ability_categories', function( $categories ) {
		 *     $categories['gravityforms-myaddon'] = [
		 *         'label'       => __( 'Gravity Forms — My Add-on', 'gravityforms-myaddon' ),
		 *         'description' => __( 'Abilities for my add-on.', 'gravityforms-myaddon' ),
		 *     ];
		 *     return $categories;
		 * } );
		 */
		$categories = apply_filters( 'gform_ability_categories', $categories );

		if ( ! is_array( $categories ) ) {
			$categories = self::get_categories_defaults();
		}

		return $categories;
	}

	/**
	 * Get the default ability categories without filters.
	 *
	 * Used as a fallback when the gform_ability_categories filter returns
	 * a malformed (non-array) value.
	 *
	 * @since 3.1.0
	 *
	 * @return array Associative array of category slug => category config.
	 */
	private static function get_categories_defaults() {
		return array(
			'gravityforms-forms'         => array(
				'label'       => __( 'Gravity Forms — Forms', 'gravityforms' ),
				'description' => __( 'Abilities for creating, reading, updating, and deleting forms.', 'gravityforms' ),
			),
			'gravityforms-entries'       => array(
				'label'       => __( 'Gravity Forms — Entries', 'gravityforms' ),
				'description' => __( 'Abilities for managing form entries (submissions data).', 'gravityforms' ),
			),
			'gravityforms-notes'         => array(
				'label'       => __( 'Gravity Forms — Notes', 'gravityforms' ),
				'description' => __( 'Abilities for managing entry notes.', 'gravityforms' ),
			),
			'gravityforms-submissions'   => array(
				'label'       => __( 'Gravity Forms — Submissions', 'gravityforms' ),
				'description' => __( 'Abilities for submitting forms and validating input.', 'gravityforms' ),
			),
			'gravityforms-feeds'         => array(
				'label'       => __( 'Gravity Forms — Feeds', 'gravityforms' ),
				'description' => __( 'Abilities for managing add-on feeds (integrations connected to forms).', 'gravityforms' ),
			),
			'gravityforms-notifications' => array(
				'label'       => __( 'Gravity Forms — Notifications', 'gravityforms' ),
				'description' => __( 'Abilities for managing form notification settings.', 'gravityforms' ),
			),
			'gravityforms-confirmations' => array(
				'label'       => __( 'Gravity Forms — Confirmations', 'gravityforms' ),
				'description' => __( 'Abilities for managing form confirmations (the response shown after submission).', 'gravityforms' ),
			),
			'gravityforms-system'        => array(
				'label'       => __( 'Gravity Forms — System', 'gravityforms' ),
				'description' => __( 'Abilities for plugin settings, system status, and diagnostics.', 'gravityforms' ),
			),
			'gravityforms-addons'        => array(
				'label'       => __( 'Gravity Forms — Add-ons', 'gravityforms' ),
				'description' => __( 'Abilities registered by Gravity Forms add-ons and third-party extensions.', 'gravityforms' ),
			),
		);
	}

	/**
	 * Register services.
	 *
	 * @since 3.1.0
	 *
	 * @param GF_Service_Container $container The service container.
	 *
	 * @return void
	 */
	public function register( GF_Service_Container $container ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-gf-abilities-logger.php';

		$container->add(
			self::ABILITIES_LOGGER,
			static function () {
				return new GF_Abilities_Logger();
			},
			true
		);
	}

	/**
	 * Initialize the service provider.
	 *
	 * @since 3.1.0
	 *
	 * @param GF_Service_Container $container The service container.
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		$logger = $container->get( self::ABILITIES_LOGGER );

		// Registered unconditionally so the subsystem stays visible on the Logging settings page when MCP is off.
		$logger->register_logging_hooks();

		if ( ! \GF_MCP_Settings::is_enabled() ) {
			return;
		}

		$logger->register_execution_hooks();

		add_filter( 'mcp_adapter_tools_list', array( $this, 'filter_tools_by_permission' ) );

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		if ( \GF_MCP_Settings::is_dedicated_endpoint() ) {
			add_action( 'mcp_adapter_init', array( $this, 'register_dedicated_server' ), 20 );
		}
	}

	/**
	 * Remove from the MCP tool list all tools that the current user cannot execute.
	 *
	 * The adapter lists every tool without a permission check. This filter applies only to
	 * Gravity Forms tools and keeps the tools of other plugins.
	 *
	 * @since 3.1.0
	 *
	 * @param array $tools The tool objects that the server returns.
	 *
	 * @return array
	 */
	public function filter_tools_by_permission( $tools ) {
		if ( ! is_array( $tools ) ) {
			return $tools;
		}

		$denied = $this->get_denied_tool_names();

		if ( empty( $denied ) ) {
			return $tools;
		}

		return array_values(
			array_filter(
				$tools,
				static function ( $tool ) use ( $denied ) {
					if ( ! is_object( $tool ) || ! method_exists( $tool, 'getName' ) ) {
						return true;
					}

					return ! isset( $denied[ $tool->getName() ] );
				}
			)
		);
	}

	/**
	 * Get the tool names of the Gravity Forms abilities that the current user cannot execute.
	 *
	 * A tool name is the ability name after the adapter sanitizer runs. The tool list uses
	 * the same transform.
	 *
	 * @since 3.1.0
	 *
	 * @return array Tool name => true.
	 */
	private function get_denied_tool_names() {
		if ( ! function_exists( 'wp_get_abilities' ) || ! class_exists( McpNameSanitizer::class ) ) {
			return array();
		}

		$denied = array();

		foreach ( wp_get_abilities() as $ability ) {
			$name = $ability->get_name();

			if ( ! \GF_MCP_Settings::is_registered_namespace( $name ) || true === $ability->check_permissions( array() ) ) {
				continue;
			}

			$tool_name = McpNameSanitizer::sanitize_name( $name );

			if ( ! is_wp_error( $tool_name ) ) {
				$denied[ $tool_name ] = true;
			}
		}

		return $denied;
	}

	/**
	 * Register ability categories.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function register_categories() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		foreach ( self::get_categories() as $slug => $category ) {
			wp_register_ability_category( $slug, $category );
		}
	}

	/**
	 * Register abilities.
	 *
	 * @since 3.1.0
	 *
	 * @example
	 * add_action( 'gform_abilities_init', function() {
	 *     wp_register_ability( 'gravityforms/myaddon/my-action', [
	 *         'label'               => __( 'My Action', 'gravityforms-myaddon' ),
	 *         'description'         => __( 'Does something useful.', 'gravityforms-myaddon' ),
	 *         'permission_callback' => function() { return GFCommon::current_user_can_any( 'gravityforms_edit_forms' ); },
	 *         'ability_class'       => GF_Ability::class,
	 *         'execute_callback'    => [ $this, 'handle_my_action' ],
	 *         'input_schema'        => [],
	 *         'output_schema'       => [],
	 *         'meta'                => [ 'mcp' => [ 'public' => true ], 'annotations' => [ 'readonly' => false, 'destructive' => false, 'idempotent' => false ] ],
	 *     ] );
	 * } );
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( GF_Abilities_Registry::get_definitions() as $definition ) {
			/**
			 * Filters whether an ability is publicly exposed to MCP clients.
			 *
			 * Per-tool settings take priority. A tool the admin has not enabled
			 * is forced private (and hidden from REST) after this filter is
			 * applied.
			 *
			 * @since 3.1.0
			 *
			 * @param bool  $is_public  Whether the ability is public to MCP clients.
			 * @param array $definition The ability definition.
			 *
			 * @return bool Whether the ability is public.
			 *
			 * @example
			 * add_filter( 'gform_mcp_public_ability', function( $is_public, $definition ) {
			 *     if ( $definition['name'] === 'gravityforms/system-info' ) {
			 *         return false;
			 *     }
			 *     return $is_public;
			 * }, 10, 2 );
			 */
			$is_public = apply_filters( 'gform_mcp_public_ability', $definition['meta']['mcp']['public'] ?? false, $definition );

			if ( ! \GF_MCP_Settings::is_tool_enabled( $definition['name'] ) ) {
				$is_public = false;

				// The per-tool gate must hold on every surface, not just MCP
				// discovery. The core Abilities REST "run" route exposes any
				// ability with show_in_rest=true and ignores mcp.public, so a
				// disabled tool left registered for REST would bypass the
				// setting. Hide it from REST while the tool is disabled.
				$definition['meta']['show_in_rest'] = false;
			}

			$definition['meta']['mcp']['public'] = (bool) $is_public;

			$definition = $this->ensure_registrable_category( $definition );

			wp_register_ability( $definition['name'], $definition );
		}

		/**
		 * Fires after all Gravity Forms abilities are registered.
		 *
		 * Add-ons can register their own abilities here with wp_register_ability(). Set
		 * ability_class to GF_Ability::class for the runtime gates, and permission_callback
		 * to a GFCommon::current_user_can_any() check for the capability gate.
		 *
		 * @since 3.1.0
		 *
		 * @example
		 * add_action( 'gform_abilities_init', function() {
		 *     wp_register_ability( 'gravityforms-myaddon/my-action', [
		 *         'label'               => __( 'My Action', 'gravityforms-myaddon' ),
		 *         'description'         => __( 'Does something useful.', 'gravityforms-myaddon' ),
		 *         'permission_callback' => function() {
		 *             return \GFCommon::current_user_can_any( 'gravityforms_edit_forms' );
		 *         },
		 *         'ability_class'       => \Gravity_Forms\Gravity_Forms\Abilities\GF_Ability::class,
		 *         'execute_callback'    => [ $this, 'handle_my_action' ],
		 *     ] );
		 * } );
		 */
		do_action( 'gform_abilities_init' );
	}

	/**
	 * Ensure a definition carries a category that can actually register.
	 *
	 * Core silently refuses abilities whose category is empty or unregistered
	 * (_doing_it_wrong + null return), which would drop filtered-in add-on
	 * definitions without a trace. Fall back to the add-ons category instead.
	 *
	 * @since 3.1.0
	 *
	 * @param array $definition The normalized ability definition.
	 *
	 * @return array
	 */
	protected function ensure_registrable_category( $definition ) {
		$category = isset( $definition['category'] ) ? $definition['category'] : '';

		if ( empty( $category ) || ! wp_has_ability_category( $category ) ) {
			$definition['category'] = 'gravityforms-addons';
		}

		return $definition;
	}

	/**
	 * Register a dedicated Gravity Forms MCP server.
	 *
	 * Called on 'mcp_adapter_init' at priority 20 (after default server at 10)
	 * to ensure abilities are already registered and resolvable.
	 *
	 * @since 3.1.0
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter The MCP adapter instance.
	 *
	 * @return void
	 */
	public function register_dedicated_server( $adapter ) {
		if ( ! method_exists( $adapter, 'create_server' ) ) {
			return;
		}

		$tool_names = $this->get_registered_ability_names();

		if ( empty( $tool_names ) ) {
			return;
		}

		$adapter->create_server(
			\GF_MCP_Settings::MCP_SERVER_SLUG,
			\GF_MCP_Settings::MCP_ROUTE_NAMESPACE,
			\GF_MCP_Settings::MCP_SERVER_SLUG,
			__( 'Gravity Forms MCP Server', 'gravityforms' ),
			__( 'Dedicated MCP server for Gravity Forms abilities.', 'gravityforms' ),
			\GFForms::$version,
			array( HttpTransport::class ),
			ErrorLogMcpErrorHandler::class,
			NullMcpObservabilityHandler::class,
			$tool_names,
			array(),
			array()
		);
	}

	/**
	 * Get the names of all registered abilities exposed on GF's MCP server.
	 *
	 * Reads from the WordPress abilities registry to ensure only abilities that
	 * were actually registered (respecting per-tool settings) are included, and
	 * admits both core 'gravityforms/*' and any registered add-on namespace
	 * (GF_MCP_Settings::register_addon()) so add-ons ride this one server.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	private function get_registered_ability_names() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		$names             = array();
		$abilities_by_name = array();

		foreach ( wp_get_abilities() as $ability ) {
			$name = $ability->get_name();

			// Core 'gravityforms/*' plus any registered add-on namespace (e.g.
			// 'gravityflow/*'). Add-ons contribute to this one server rather than
			// standing up their own — see GF_MCP_Settings::register_addon().
			if ( \GF_MCP_Settings::is_registered_namespace( $name ) ) {
				$abilities_by_name[ $name ] = $ability;

				if ( $this->is_ability_enabled( $ability ) ) {
					$names[] = $name;
				}
			}
		}

		/**
		 * Filters the ability names exposed by the dedicated MCP server.
		 *
		 * Fail-closed: after filtering, only names under a registered namespace
		 * (core `gravityforms/` or a registered add-on) that are actually
		 * registered and enabled survive — a filter cannot add a disabled,
		 * unregistered, or foreign-namespace name.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $names The Gravity Forms ability names exposed as MCP tools.
		 *
		 * @return string[] The filtered ability names.
		 *
		 * @example
		 * add_filter( 'gform_mcp_server_tools', function( $names ) {
		 *     return array_diff( $names, [ 'gravityforms/forms-delete' ] );
		 * } );
		 */
		$pre_filter_names = $names;
		$names            = apply_filters( 'gform_mcp_server_tools', $names );

		if ( ! is_array( $names ) ) {
			$names = $pre_filter_names;
		}

		$names = array_values(
			array_filter(
				$names,
				function ( $name ) use ( $abilities_by_name ) {
					return is_string( $name )
						&& \GF_MCP_Settings::is_registered_namespace( $name )
						&& isset( $abilities_by_name[ $name ] )
						&& $this->is_ability_enabled( $abilities_by_name[ $name ] );
				}
			)
		);

		return $names;
	}

	/**
	 * Determine if an ability is enabled for exposure by the per-tool MCP settings.
	 *
	 * The MCP adapter does not enforce per-tool settings for custom servers that
	 * receive an explicit tool list, so Gravity Forms must apply the gate before
	 * calling create_server().
	 *
	 * @since 3.1.0
	 *
	 * @param \WP_Ability $ability The registered ability.
	 *
	 * @return bool
	 */
	protected function is_ability_enabled( $ability ) {
		if ( ! method_exists( $ability, 'get_name' ) ) {
			return false;
		}

		return \GF_MCP_Settings::is_tool_enabled( $ability->get_name() );
	}
}
