<?php

use Gravity_Forms\Gravity_Forms\Settings\Settings;

class_exists( 'GFForms' ) || die();

/**
 * MCP settings page for Gravity Forms.
 *
 * Registers a settings subpage under GF Settings via hooks.
 * Always loads — shows unavailable message when Abilities API or MCP bridge is missing,
 * hides the toggle section via GF dependency when requirements are not met.
 *
 * @since 3.1.0
 */
class GF_MCP_Settings {

	/**
	 * Option key for the MCP enabled setting.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const OPTION_ENABLED = 'gform_mcp_enabled';

	/**
	 * Option key for the per-tool enablement setting.
	 *
	 * Stores a flat list of the ability names the admin has enabled. Absent
	 * from the list (the default) means the tool is off.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const OPTION_ENABLED_TOOLS = 'gform_mcp_enabled_tools';

	/**
	 * Option key for the endpoint mode setting.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const OPTION_ENDPOINT_MODE = 'gform_mcp_endpoint_mode';

	/**
	 * Endpoint mode: shared site MCP.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const ENDPOINT_SITE = 'site';

	/**
	 * Endpoint mode: dedicated Gravity Forms MCP server (default).
	 *
	 * The dedicated server lists each enabled ability as a first-class MCP tool,
	 * which assistants discover and call more reliably than the shared default
	 * server's generic discovery tools.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const ENDPOINT_DEDICATED = 'dedicated';

	/**
	 * REST API namespace for MCP servers.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const MCP_ROUTE_NAMESPACE = 'mcp';

	/**
	 * Route slug for the dedicated Gravity Forms MCP server.
	 *
	 * Used as both the server ID and route when creating the dedicated server
	 * via McpAdapter::create_server().
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const MCP_SERVER_SLUG = 'gravityforms';

	/**
	 * Route slug for the shared WordPress default MCP server.
	 *
	 * This value is defined in the MCP Adapter package's DefaultServerFactory
	 * as a plain string — no constant or getter is exposed upstream. Hardcoded
	 * here to match. If the adapter changes this, the mcp_adapter_default_server_config
	 * filter would be the signal.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const MCP_DEFAULT_SERVER_SLUG = 'mcp-adapter-default-server';

	/**
	 * Download URL for the Gravity Forms abilities agent skill.
	 *
	 * Distributed separately from the plugin as a release asset; referenced by
	 * the skill download button.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	const SKILL_DOWNLOAD_URL = 'https://github.com/gravityforms/gravityskills/releases/latest/download/gravity-forms-abilities.zip';

	/**
	 * Settings renderer instance.
	 *
	 * @since 3.1.0
	 *
	 * @var Settings|false
	 */
	private static $renderer = false;

	/**
	 * Registered add-on MCP contributions, keyed by namespace.
	 *
	 * Populated by register_addon(); read (through the gform_mcp_registered_addons
	 * filter) by the namespace allowlist and the per-add-on settings metaboxes.
	 *
	 * @since 3.1.0
	 *
	 * @var array<string,array>
	 */
	private static $addons = array();

	/**
	 * Initialize hooks.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'gform_settings_menu', array( __CLASS__, 'add_settings_tab' ) );
		add_action( 'gform_settings_mcp', array( __CLASS__, 'render_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue scripts needed by the MCP settings page.
	 *
	 * The Settings renderer is built at render time (on the gform_settings_mcp
	 * action), which runs after admin_enqueue_scripts, so the framework never
	 * gets a chance to enqueue its own conditional scripts for this subview. The
	 * tool sections use a live dependency, whose inline bootstrap needs the
	 * Settings dependencies script defined in the document head before the page
	 * body runs it — so it is enqueued here directly.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function enqueue_admin_scripts() {
		if ( 'gf_settings' !== rgget( 'page' ) || 'mcp' !== rgget( 'subview' ) || ! self::is_mcp_available() ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'gform_settings_dependencies',
			GFCommon::get_base_url() . "/includes/settings/js/dependencies{$min}.js",
			array(),
			GFForms::$version,
			false
		);
	}

	/**
	 * Add the MCP tab to the GF settings navigation.
	 *
	 * @since 3.1.0
	 *
	 * @param array $tabs Existing settings tabs.
	 *
	 * @return array
	 */
	public static function add_settings_tab( $tabs ) {
		$mcp_settings_tab = array(
			'name'  => 'mcp',
			'label' => __( 'MCP', 'gravityforms' ),
			'icon'  => 'gform-icon--mcp',
		);

		$tabs = array_values( $tabs );

		array_splice( $tabs, 2, 0, array( $mcp_settings_tab ) );

		return $tabs;
	}

	/**
	 * Render the MCP settings page.
	 *
	 * Fired by do_action( 'gform_settings_mcp' ) from the default case
	 * in GFSettings::settings_page().
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function render_settings_page() {

		wp_enqueue_style( 'gform_admin' );

		if ( ! self::$renderer ) {
			self::initialize_settings();
		}

		self::$renderer->render();
	}

	/**
	 * Initialize the Settings renderer.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	private static function initialize_settings() {

		require_once GFCommon::get_base_path() . '/tooltips.php';

		$fields = array();

		// Build both tool groups' choices from a single registry pass; reused by
		// the tool sections and the initial values below.
		$choices_by_group = self::get_choices_by_group();

		// Registered add-ons contribute their own tool metaboxes, sourced from
		// their live-registered abilities. Resolve once; reused by the metabox
		// fields and the initial values.
		$addons        = self::is_mcp_available() ? self::get_registered_addons() : array();
		$addon_choices = array();
		foreach ( $addons as $addon_namespace => $addon ) {
			$addon_choices[ $addon_namespace ] = self::get_addon_choices_by_group( $addon_namespace );
		}

		// When dependencies are missing, show an informational message.
		if ( ! self::is_mcp_available() ) {
			$fields['mcp_unavailable'] = array(
				'id'     => 'section_mcp_unavailable',
				'title'  => esc_html__( 'MCP Integration', 'gravityforms' ),
				'class'  => 'gform-settings-panel--full',
				'fields' => array(
					array(
						'name' => 'mcp_unavailable_message',
						'type' => 'html',
						'html' => self::get_unavailable_message(),
					),
				),
			);
		}

		// Master switch panel — the Enable MCP toggle lives in its own box, above
		// the per-tool metaboxes, so it is not blended with any tool toggles. The
		// panel keeps the integration's intro copy and is hidden only when
		// requirements are missing.
		$master_fields = array(
			array(
				'name'          => 'mcp_enabled',
				'type'          => 'toggle',
				'toggle_label'  => esc_html__( 'Enable MCP', 'gravityforms' ),
				'save_callback' => function ( $field, $value ) {
					update_option( self::OPTION_ENABLED, (bool) $value );
					return $value;
				},
			),
		);

		// A toggle reports a boolean value to both the server-side dependency
		// check and the live JS, so the rule matches on "not empty" (true)
		// rather than the string '1', which neither path compares equal to a
		// bool. Reused by the tool groups and the Endpoint/Skill panels so they
		// all show/hide together with the master toggle.
		$enabled_dependency = array(
			'live'   => true,
			'fields' => array(
				array( 'field' => 'mcp_enabled' ),
			),
		);

		// Gravity Forms tool metabox — core's own read/write tool groups, in a
		// dedicated box parallel to each add-on's, so the master switch is not
		// blended with the tool toggles. Hidden (live) when the master toggle is
		// off. Every tool is off by default.
		$gravityforms_fields = array();

		if ( self::is_mcp_available() ) {
			foreach ( self::get_tool_section_config() as $field_name => $config ) {
				$group_choices = self::sort_choices_by_label( $choices_by_group[ $config['group'] ] );
				$is_readonly   = 'readonly' === $config['group'];

				// The group heading and blurb use the checkbox field's native
				// label and description. Choices render as a two-column grid above
				// the WP admin breakpoint (see settings/pages/_mcp.pcss); the
				// select-all control follows the grid.
				$gravityforms_fields[] = array(
					'name'          => $field_name,
					'label'         => $config['title'],
					'description'   => $config['description'],
					'type'          => 'checkbox',
					'data_format'   => 'array',
					'choices'       => $group_choices,
					'dependency'    => $enabled_dependency,
					'save_callback' => function ( $field, $value ) use ( $is_readonly ) {
						self::save_tool_group( $is_readonly, $value );
						return $value;
					},
				);

				$gravityforms_fields[] = array(
					'name'       => $field_name . '_select_all',
					'type'       => 'html',
					'dependency' => $enabled_dependency,
					'html'       => static function () use ( $field_name, $group_choices ) {
						return self::get_select_all_html( $field_name, $group_choices );
					},
				);
			}
		}

		$fields['mcp_enable'] = array(
			'id'          => 'section_mcp_enable',
			'title'       => esc_html__( 'MCP Integration', 'gravityforms' ),
			'description' => sprintf(
				/* translators: %s is a "Learn more" link to the MCP documentation. */
				esc_html__( 'Enable AI assistants to interact with Gravity Forms. Read forms and entries, manage submissions, and automate workflows through the Model Context Protocol (MCP). %s about setting up the MCP.', 'gravityforms' ),
				self::get_doc_link(
					'https://docs.gravityforms.com/gravity-forms-mcp-server-overview/',
					__( 'Learn more about setting up the Gravity Forms MCP server (opens in a new tab)', 'gravityforms' )
				)
			),
			'class'       => 'gform-settings-panel--full',
			'dependency'  => array( __CLASS__, 'is_mcp_available' ),
			'fields'      => $master_fields,
		);

		// Gravity Forms tools render in their own metabox directly below the master
		// switch and above the add-on metaboxes.
		if ( self::is_mcp_available() && ! empty( $gravityforms_fields ) ) {
			$fields['mcp_gravityforms'] = array(
				'id'         => 'section_mcp_gravityforms',
				'title'      => esc_html__( 'Gravity Forms', 'gravityforms' ),
				'class'      => 'gform-settings-panel--full gform-mcp-integration',
				'dependency' => $enabled_dependency,
				'fields'     => $gravityforms_fields,
			);
		}

		// Per-add-on tool metaboxes — each registered add-on renders below core's
		// own toggles as its own panel with the same read/write + select-all
		// layout and the gform-mcp-integration two-column grid, sourced from its
		// live-registered abilities and using the add-on's own group descriptions
		// (a generic fallback when it registers none). Hidden (live) whenever the
		// master toggle is off.
		if ( self::is_mcp_available() ) {
			$section_config = self::get_tool_section_config();

			foreach ( $addons as $addon_namespace => $addon ) {
				$groups = $addon_choices[ $addon_namespace ];

				if ( empty( $groups['readonly'] ) && empty( $groups['write'] ) ) {
					continue;
				}

				$addon_fields = array();

				foreach ( $section_config as $config ) {
					$group = $config['group'];

					if ( empty( $groups[ $group ] ) ) {
						continue;
					}

					$field_name    = 'mcp_addon_' . $addon_namespace . '_' . $group . '_tools';
					$is_readonly   = 'readonly' === $group;
					$group_choices = self::sort_choices_by_label( $groups[ $group ] );
					$group_names   = wp_list_pluck( $group_choices, 'name' );

					$addon_fields[] = array(
						'name'          => $field_name,
						'label'         => $config['title'],
						'description'   => self::get_addon_group_description( $addon, $group ),
						'type'          => 'checkbox',
						'data_format'   => 'array',
						'choices'       => $group_choices,
						'dependency'    => $enabled_dependency,
						'save_callback' => function ( $field, $value ) use ( $is_readonly, $group_names ) {
							self::save_tool_group( $is_readonly, $value, $group_names );
							return $value;
						},
					);

					$addon_fields[] = array(
						'name'       => $field_name . '_select_all',
						'type'       => 'html',
						'dependency' => $enabled_dependency,
						'html'       => static function () use ( $field_name, $group_choices ) {
							return self::get_select_all_html( $field_name, $group_choices );
						},
					);
				}

				$fields[ 'mcp_addon_' . $addon_namespace ] = array(
					'id'         => 'section_mcp_addon_' . $addon_namespace,
					'title'      => $addon['label'],
					'class'      => 'gform-settings-panel--full gform-mcp-integration',
					'dependency' => $enabled_dependency,
					'fields'     => $addon_fields,
				);
			}
		}

		// Endpoint and Skill sections — registered only when MCP is available, and
		// hidden (live) whenever the master toggle is off, so the whole page below
		// the toggle collapses together.
		if ( self::is_mcp_available() ) {
			$fields['mcp_endpoint_mode'] = array(
				'id'          => 'section_mcp_endpoint_mode',
				'title'       => esc_html__( 'Endpoint Mode', 'gravityforms' ),
				'description' => sprintf(
					/* translators: %s is a "Learn more" link to the endpoint mode documentation. */
					esc_html__( 'Choose how AI assistants connect. Dedicated Endpoint (recommended) gives Gravity Forms its own connection for faster and more reliable access. Site MCP uses a shared WordPress endpoint for all MCP plugins. %s about endpoint modes.', 'gravityforms' ),
					self::get_doc_link(
						'https://docs.gravityforms.com/mcp-server-settings-reference/#h-endpoint-mode',
						__( 'Learn more about Gravity Forms MCP endpoint modes (opens in a new tab)', 'gravityforms' )
					)
				),
				'class'       => 'gform-settings-panel--full',
				'dependency'  => $enabled_dependency,
				'fields'      => array(
					array(
						'name'          => 'mcp_endpoint_mode',
						'type'          => 'radio',
						'horizontal'    => true,
						'choices'       => array(
							array(
								'label'   => esc_html__( 'Dedicated Endpoint (recommended)', 'gravityforms' ),
								'value'   => self::ENDPOINT_DEDICATED,
								'tooltip' => esc_html__( 'A Gravity Forms-only endpoint. Each enabled tool is listed directly to the AI assistant, which is the most reliable way for it to discover and use them. Other plugins are not reachable through this connection.', 'gravityforms' ),
							),
							array(
								'label'   => esc_html__( 'Site MCP', 'gravityforms' ),
								'value'   => self::ENDPOINT_SITE,
								'tooltip' => esc_html__( 'The shared WordPress endpoint used by all MCP-enabled plugins. One connection covers your whole site, but Gravity Forms tools are reached indirectly through WordPress\'s generic discovery tools rather than listed directly. Choose this only if you specifically want a single shared connection.', 'gravityforms' ),
							),
						),
						'save_callback' => function ( $field, $value ) {
							$valid = array( self::ENDPOINT_SITE, self::ENDPOINT_DEDICATED );
							$value = in_array( $value, $valid, true ) ? $value : self::ENDPOINT_DEDICATED;
							update_option( self::OPTION_ENDPOINT_MODE, $value );
							return $value;
						},
					),
					array(
						'name' => 'mcp_endpoint_preview',
						'type' => 'html',
						'html' => static function () {
							return self::get_endpoint_preview_html();
						},
					),
				),
			);

			$fields['mcp_skill'] = array(
				'id'         => 'section_mcp_skill',
				'title'      => esc_html__( 'Agent Skills', 'gravityforms' ),
				'class'      => 'gform-settings-panel--full',
				'dependency' => $enabled_dependency,
				'fields'     => array(
					array(
						'name' => 'mcp_skill_html',
						'type' => 'html',
						'html' => static function () {
							return self::get_skill_html();
						},
					),
				),
			);
		}

		$initial_values = array(
			'mcp_enabled'        => self::is_enabled(),
			'mcp_endpoint_mode'  => self::get_endpoint_mode(),
			'mcp_readonly_tools' => array_values( array_intersect( self::get_enabled_tools(), wp_list_pluck( $choices_by_group['readonly'], 'name' ) ) ),
			'mcp_write_tools'    => array_values( array_intersect( self::get_enabled_tools(), wp_list_pluck( $choices_by_group['write'], 'name' ) ) ),
		);

		// Reflect each add-on group's saved selection so its checkboxes render checked.
		foreach ( $addon_choices as $addon_namespace => $groups ) {
			foreach ( array( 'readonly', 'write' ) as $group ) {
				if ( empty( $groups[ $group ] ) ) {
					continue;
				}

				$initial_values[ 'mcp_addon_' . $addon_namespace . '_' . $group . '_tools' ] = array_values(
					array_intersect( self::get_enabled_tools(), wp_list_pluck( $groups[ $group ], 'name' ) )
				);
			}
		}

		$renderer = new Settings(
			array(
				'fields'            => $fields,
				'input_name_prefix' => '_gform_setting',
				'capability'        => 'gravityforms_edit_settings',
				'initial_values'    => $initial_values,
				'save_button'       => array(
					'dependency' => array( __CLASS__, 'is_mcp_available' ),
				),
				'save_callback'     => function ( $values ) {
					// Intentionally empty — individual field save_callback handles persistence.
				},
			)
		);

		self::$renderer = $renderer;

		if ( self::$renderer->is_save_postback() ) {
			self::$renderer->process_postback();
		}
	}

	/**
	 * Check if all MCP dependencies are available.
	 *
	 * Requires the WordPress Abilities API (wp_register_ability) — WordPress 6.9+ core.
	 * Requires the canonical WordPress MCP Adapter package loaded by Composer.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function is_mcp_available() {
		return self::has_abilities_api() && class_exists( '\WP\MCP\Core\McpAdapter' );
	}

	/**
	 * Check if the WordPress Abilities API is available (WP 6.9+).
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	private static function has_abilities_api() {
		return defined( 'GF_SUPPORTS_ABILITIES_API' ) && GF_SUPPORTS_ABILITIES_API;
	}

	/**
	 * Check if MCP is enabled via the settings toggle.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Get the list of ability names the admin has enabled.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public static function get_enabled_tools() {
		$tools = get_option( self::OPTION_ENABLED_TOOLS, array() );

		return is_array( $tools ) ? array_values( $tools ) : array();
	}

	/**
	 * Check if a specific ability (tool) is enabled.
	 *
	 * The master MCP setting gates everything: when MCP is disabled no tool is
	 * enabled regardless of the saved list. This is the single source of truth
	 * consulted by every exposure and execution gate.
	 *
	 * @since 3.1.0
	 *
	 * @param string $ability_name The full ability name (e.g. 'gravityforms/forms-get').
	 *
	 * @return bool
	 */
	public static function is_tool_enabled( $ability_name ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		return in_array( $ability_name, self::get_enabled_tools(), true );
	}

	/**
	 * Register an add-on's MCP tool contribution.
	 *
	 * Gravity Forms is the single MCP host for the ecosystem: add-ons (Gravity
	 * Flow, Stripe, …) contribute their abilities to GF's endpoint rather than
	 * running their own MCP server. Abilities are still registered through the
	 * WordPress Abilities API as usual; this contract governs only exposure on
	 * GF's endpoint, settings rendering, and gating.
	 *
	 * Abilities self-describe (label, summary, readonly/destructive annotations,
	 * required capability, mcp.public), so an add-on does NOT re-declare per-tool
	 * metadata here — it declares its namespace, a group label for its settings
	 * metabox, and optionally a skill entry and default-enabled policy.
	 *
	 * @since 3.1.0
	 *
	 * @param array $config {
	 *     @type string $namespace       Required. The add-on's ability namespace, e.g. 'gravityflow'.
	 *                                    Lowercase slug; may not be the core 'gravityforms' namespace.
	 *     @type string $label           Required. Human label for the settings metabox, e.g. 'Gravity Flow'.
	 *     @type array  $skill           Optional. { label, url } surfaced in GF's skills section.
	 *     @type array  $descriptions    Optional. Per-group help text for the metabox, keyed 'readonly'
	 *                                    and/or 'write'; pre-escaped display strings. A generic add-on
	 *                                    fallback is used for any group left unset.
	 *     @type bool   $default_enabled Optional. Whether the add-on's tools default on. Default false.
	 * }
	 *
	 * @return bool True if registered; false if the config was invalid or collides with core.
	 */
	public static function register_addon( $config ) {
		$namespace = isset( $config['namespace'] ) ? trim( (string) $config['namespace'] ) : '';
		$label     = isset( $config['label'] ) ? (string) $config['label'] : '';

		// Namespace must be a slug and must not shadow core; label is required for
		// the metabox heading. Reject anything malformed rather than half-register.
		if ( '' === $namespace || self::MCP_SERVER_SLUG === $namespace || ! preg_match( '/^[a-z0-9\-]+$/', $namespace ) ) {
			return false;
		}

		if ( '' === $label ) {
			return false;
		}

		// Optional per-group metabox copy; only the recognised groups are kept, so
		// the add-on's own wording replaces core's forms/entries-specific text.
		$descriptions = array();
		if ( isset( $config['descriptions'] ) && is_array( $config['descriptions'] ) ) {
			foreach ( array( 'readonly', 'write' ) as $group ) {
				if ( ! empty( $config['descriptions'][ $group ] ) ) {
					$descriptions[ $group ] = (string) $config['descriptions'][ $group ];
				}
			}
		}

		self::$addons[ $namespace ] = array(
			'namespace'       => $namespace,
			'label'           => $label,
			'skill'           => isset( $config['skill'] ) && is_array( $config['skill'] ) ? $config['skill'] : array(),
			'descriptions'    => $descriptions,
			'default_enabled' => ! empty( $config['default_enabled'] ),
		);

		return true;
	}

	/**
	 * Resolve the help text shown above an add-on's tool group.
	 *
	 * Uses the add-on's own copy when it registered one for the group; otherwise
	 * a generic add-on fallback, so no add-on inherits Gravity Forms' own
	 * forms/entries-specific wording.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $addon The stored add-on registration.
	 * @param string $group The tool group, 'readonly' or 'write'.
	 *
	 * @return string
	 */
	private static function get_addon_group_description( $addon, $group ) {
		if ( ! empty( $addon['descriptions'][ $group ] ) ) {
			return $addon['descriptions'][ $group ];
		}

		if ( 'readonly' === $group ) {
			return esc_html__( 'These tools let AI assistants read data and state from this add-on. Enable only the ones you want available.', 'gravityforms' );
		}

		return esc_html__( 'These tools let AI assistants create, update, and delete data managed by this add-on. Enable only the ones you want available.', 'gravityforms' );
	}

	/**
	 * Get all registered add-on contributions, keyed by namespace.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string,array>
	 */
	public static function get_registered_addons() {
		/**
		 * Filters the registered MCP add-on contributions.
		 *
		 * Last-mile hook for a site to add, adjust, or remove an add-on's MCP
		 * contribution. Each entry is keyed by namespace and carries the shape
		 * stored by GF_MCP_Settings::register_addon().
		 *
		 * @since 3.1.0
		 *
		 * @param array<string,array> $addons Registered add-ons keyed by namespace.
		 */
		$addons = apply_filters( 'gform_mcp_registered_addons', self::$addons );

		return is_array( $addons ) ? $addons : self::$addons;
	}

	/**
	 * Get every ability namespace admitted to GF's MCP surface.
	 *
	 * Core ('gravityforms') plus every registered add-on namespace. This is the
	 * allowlist the dedicated server tool-list assembly checks against.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public static function get_registered_namespaces() {
		return array_values(
			array_unique(
				array_merge(
					array( self::MCP_SERVER_SLUG ),
					array_keys( self::get_registered_addons() )
				)
			)
		);
	}

	/**
	 * Whether an ability name's namespace is admitted to GF's MCP surface.
	 *
	 * True for core 'gravityforms/*' abilities and for any ability under a
	 * registered add-on namespace. Used fail-closed by the server tool-list
	 * assembly so a foreign or unregistered namespace can never be exposed.
	 *
	 * @since 3.1.0
	 *
	 * @param string $ability_name Full ability name, e.g. 'gravityflow/inbox-list'.
	 *
	 * @return bool
	 */
	public static function is_registered_namespace( $ability_name ) {
		if ( ! is_string( $ability_name ) ) {
			return false;
		}

		$slash = strpos( $ability_name, '/' );

		if ( false === $slash || 0 === $slash ) {
			return false;
		}

		return in_array( substr( $ability_name, 0, $slash ), self::get_registered_namespaces(), true );
	}

	/**
	 * Get the configuration for the per-tool settings sections.
	 *
	 * Keyed by the checkbox field name; each entry names the registry group it
	 * draws from plus its display copy. Adding a group is a single entry here.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private static function get_tool_section_config() {
		return array(
			'mcp_readonly_tools' => array(
				'group'       => 'readonly',
				'title'       => esc_html__( 'Read-only Tools', 'gravityforms' ),
				'description' => esc_html__( 'These tools let AI assistants read your forms, entries, and settings. Enable only the ones you want available.', 'gravityforms' )
				. '<br><br>',
			),
			'mcp_write_tools'    => array(
				'group'       => 'write',
				'title'       => esc_html__( 'Write Tools', 'gravityforms' ),
				'description' => sprintf(
					// Spans (not divs) because this renders inside the field's inline
					// description span; block elements there are invalid nesting.
					'
						<span class="gform-alert gform-alert--default gform-alert--theme-primary">
							<span aria-hidden="true" class="gform-alert__icon gform-icon gform-icon--circle-notice-fine"></span>
							<span class="gform-alert__message-wrap">
								<span class="gform-alert__message">%s</span>
							</span>
						</span>',
					esc_html__( 'Use with caution. AI assistants can make mistakes. An AI with these tools could modify or delete your forms and entries. Always review AI-suggested changes before applying them, especially on live sites.', 'gravityforms' )
				)
					. esc_html__( 'These tools let AI assistants create, update, and delete your forms, entries, and other data. Enable only the ones you want available.', 'gravityforms' )
					. '<br><br>',
			),
		);
	}

	/**
	 * Build the checkbox choices for every tool group in a single registry pass.
	 *
	 * @since 3.1.0
	 *
	 * @return array {
	 *     @type array $readonly Choice arrays for read-only abilities.
	 *     @type array $write    Choice arrays for write/other abilities.
	 * }
	 */
	private static function get_choices_by_group() {
		$groups = array(
			'readonly' => array(),
			'write'    => array(),
		);

		if ( ! class_exists( '\Gravity_Forms\Gravity_Forms\Abilities\GF_Abilities_Registry' ) ) {
			return $groups;
		}

		foreach ( \Gravity_Forms\Gravity_Forms\Abilities\GF_Abilities_Registry::get_definitions() as $definition ) {
			$group = empty( $definition['meta']['annotations']['readonly'] ) ? 'write' : 'readonly';

			// The tooltip uses the ability's admin-facing `meta.summary` (a short,
			// plain-language line) rather than `description`, which is verbose
			// agent-facing copy. No summary means no tooltip — the label already
			// names the tool, so falling back to the label would be redundant.
			$groups[ $group ][] = array(
				'name'    => $definition['name'],
				'label'   => ! empty( $definition['label'] ) ? $definition['label'] : $definition['name'],
				'tooltip' => ! empty( $definition['meta']['summary'] ) ? $definition['meta']['summary'] : '',
			);
		}

		return $groups;
	}

	/**
	 * Build an add-on's tool-group choices from its live-registered abilities.
	 *
	 * Unlike core's get_choices_by_group() (which reads GF's own definition
	 * registry), an add-on's abilities live only in the WordPress abilities
	 * registry, so they are read back from there and bucketed by their
	 * self-describing read-only annotation. Disabled tools stay registered (just
	 * hidden from REST/MCP), so every declared tool still surfaces as a toggle.
	 *
	 * @since 3.1.0
	 *
	 * @param string $addon_namespace The add-on namespace, e.g. 'gravityflow'.
	 *
	 * @return array {
	 *     @type array $readonly Choice arrays for the add-on's read-only abilities.
	 *     @type array $write    Choice arrays for the add-on's write/other abilities.
	 * }
	 */
	private static function get_addon_choices_by_group( $addon_namespace ) {
		$groups = array(
			'readonly' => array(),
			'write'    => array(),
		);

		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return $groups;
		}

		$prefix = $addon_namespace . '/';

		foreach ( wp_get_abilities() as $ability ) {
			$name = $ability->get_name();

			if ( strpos( $name, $prefix ) !== 0 ) {
				continue;
			}

			if ( ! $ability instanceof \Gravity_Forms\Gravity_Forms\Abilities\GF_Ability ) {
				continue;
			}

			$meta  = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();
			$group = empty( $meta['annotations']['readonly'] ) ? 'write' : 'readonly';

			$groups[ $group ][] = array(
				'name'    => $name,
				'label'   => $ability->get_label() ? $ability->get_label() : $name,
				'tooltip' => ! empty( $meta['summary'] ) ? $meta['summary'] : '',
			);
		}

		return $groups;
	}

	/**
	 * Alpha-sort a group's choices by their displayed label.
	 *
	 * Sorting lives here on the render path rather than in get_choices_by_group()
	 * so the save path — which only needs group membership — does not pay for a
	 * display-only sort. remove_accents() keeps accented labels in their expected
	 * alphabetical slot instead of after all ASCII, which byte-wise strcasecmp
	 * would do in a translated locale.
	 *
	 * @since 3.1.0
	 *
	 * @param array $choices Choice arrays each with a 'label' key.
	 *
	 * @return array
	 */
	private static function sort_choices_by_label( $choices ) {
		usort(
			$choices,
			function ( $a, $b ) {
				return strcasecmp( remove_accents( $a['label'] ), remove_accents( $b['label'] ) );
			}
		);

		return $choices;
	}

	/**
	 * Get the ability names belonging to a settings group (read-only or write).
	 *
	 * @since 3.1.0
	 *
	 * @param bool $readonly_group True for the read-only group, false for the write/other group.
	 *
	 * @return string[]
	 */
	private static function get_group_ability_names( $readonly_group ) {
		$choices = self::get_choices_by_group();

		return wp_list_pluck( $choices[ $readonly_group ? 'readonly' : 'write' ], 'name' );
	}

	/**
	 * Persist a tool settings group, merging it with the other group's saved state.
	 *
	 * Only names that belong to this group (per the registry) are accepted, so a
	 * tampered postback cannot enable an unknown or cross-group ability.
	 *
	 * @since 3.1.0
	 *
	 * @param bool       $readonly_group True for the read-only group, false for the write/other group.
	 * @param mixed      $value          The posted array of checked ability names.
	 * @param string[]|null $group_names Explicit set of names owned by this group. When null (core's
	 *                                   own groups) the names are derived from GF's registry; add-on
	 *                                   groups pass their own names since they are not in that registry.
	 *
	 * @return void
	 */
	private static function save_tool_group( $readonly_group, $value, $group_names = null ) {
		if ( null === $group_names ) {
			$group_names = self::get_group_ability_names( $readonly_group );
		}

		$submitted = is_array( $value ) ? $value : array();
		$accepted  = array_values( array_intersect( $submitted, $group_names ) );

		// Keep the other group's enabled tools; replace this group's selection.
		$retained = array_values( array_diff( self::get_enabled_tools(), $group_names ) );
		$merged   = array_values( array_unique( array_merge( $retained, $accepted ) ) );

		update_option( self::OPTION_ENABLED_TOOLS, $merged );
	}

	/**
	 * Get the "select all" checkbox markup for a tool group.
	 *
	 * Behaviour is wired by the admin JS bundle (assets/js/src/admin/settings/
	 * pages/mcp.js); the localized labels ride on data attributes so the markup
	 * stays script-free here.
	 *
	 * @since 3.1.0
	 *
	 * @param string $field_name The checkbox field name whose inputs the control toggles.
	 * @param array  $choices    The checkbox choices belonging to the field.
	 *
	 * @return string
	 */
	private static function get_select_all_html( $field_name, $choices = array() ) {
		$dom_id      = 'gform-mcp-select-all-' . $field_name;
		$group_names = wp_list_pluck( $choices, 'name' );
		$all_checked = ! empty( $group_names ) && empty( array_diff( $group_names, self::get_enabled_tools() ) );
		$label       = $all_checked ? __( 'Deselect all', 'gravityforms' ) : __( 'Select all', 'gravityforms' );

		return sprintf(
			'<div class="gform-settings-choice gform-mcp-select-all-row">'
			. '<input type="checkbox" id="%1$s" class="gform-mcp-select-all" data-gfmcp-field="%2$s" data-select-label="%3$s" data-deselect-label="%4$s"%5$s />'
			. '<label for="%1$s"><span class="gform-mcp-select-all-label">%6$s</span></label>'
			. '</div>',
			esc_attr( $dom_id ),
			esc_attr( $field_name ),
			esc_attr__( 'Select all', 'gravityforms' ),
			esc_attr__( 'Deselect all', 'gravityforms' ),
			checked( $all_checked, true, false ),
			esc_html( $label )
		);
	}

	/**
	 * Get the configured endpoint mode.
	 *
	 * @since 3.1.0
	 *
	 * @return string 'site' or 'dedicated'.
	 */
	public static function get_endpoint_mode() {
		$mode = get_option( self::OPTION_ENDPOINT_MODE, self::ENDPOINT_DEDICATED );

		return in_array( $mode, array( self::ENDPOINT_SITE, self::ENDPOINT_DEDICATED ), true )
			? $mode
			: self::ENDPOINT_DEDICATED;
	}

	/**
	 * Check if dedicated endpoint mode is active.
	 *
	 * When true, GF abilities are served from a dedicated MCP server
	 * at /wp-json/mcp/gravityforms instead of the shared site MCP.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function is_dedicated_endpoint() {
		return self::get_endpoint_mode() === self::ENDPOINT_DEDICATED;
	}

	/**
	 * Get the MCP endpoint URL based on the current endpoint mode.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private static function get_mcp_endpoint_url() {
		$site_url = untrailingslashit( get_rest_url() );
		if ( self::is_dedicated_endpoint() ) {
			return $site_url . '/' . self::MCP_ROUTE_NAMESPACE . '/' . self::MCP_SERVER_SLUG;
		}
		return $site_url . '/' . self::MCP_ROUTE_NAMESPACE . '/' . self::MCP_DEFAULT_SERVER_SLUG;
	}

	/**
	 * Get the endpoint URL preview HTML.
	 *
	 * Renders a styled URL display whose value the admin JS (settings/pages/mcp.js)
	 * live-updates when the endpoint mode radio changes. The base URL and the
	 * per-mode paths ride on data attributes so PHP stays the single source of
	 * truth for the route slugs — the JS never re-derives them.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private static function get_endpoint_preview_html() {
		$base_url    = untrailingslashit( get_rest_url() );
		$paths       = array(
			self::ENDPOINT_SITE      => '/' . self::MCP_ROUTE_NAMESPACE . '/' . self::MCP_DEFAULT_SERVER_SLUG,
			self::ENDPOINT_DEDICATED => '/' . self::MCP_ROUTE_NAMESPACE . '/' . self::MCP_SERVER_SLUG,
		);
		$current_url = self::get_mcp_endpoint_url();

		return sprintf(
			'<div class="gform-mcp-endpoint-preview" data-gfmcp-base-url="%1$s" data-gfmcp-paths="%2$s">'
			. '<span class="gform-mcp-endpoint-preview__label">%3$s</span>'
			. '<code id="gform-mcp-endpoint-url" class="gform-mcp-endpoint-preview__url">%4$s</code>'
			. '</div>',
			esc_attr( $base_url ),
			esc_attr( wp_json_encode( $paths ) ),
			esc_html__( 'Endpoint URL', 'gravityforms' ),
			esc_html( $current_url )
		);
	}

	/**
	 * Get the skill download section HTML.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private static function get_skill_html() {
		$html = sprintf(
			/* translators: %s is a "Learn more" link to the agent skill documentation. */
			esc_html__( 'Install agent skills in your AI agent to enhance how it works with your site. %s about installing skills.', 'gravityforms' ),
			self::get_doc_link(
				'https://docs.gravityforms.com/mcp-server-settings-reference/#h-agent-skills',
				__( 'Learn more about installing agent skills (opens in a new tab)', 'gravityforms' )
			)
		);
		// Core skill first, then each registered add-on's skill — the unified host
		// is the single place customers collect every skill.
		$buttons = array(
			array(
				'url'   => self::SKILL_DOWNLOAD_URL,
				'label' => esc_html__( 'Download Gravity Forms Skill', 'gravityforms' ),
			),
		);

		foreach ( self::get_registered_addons() as $addon ) {
			$skill = isset( $addon['skill'] ) ? $addon['skill'] : array();
			$url   = isset( $skill['url'] ) ? $skill['url'] : '';

			if ( '' === $url ) {
				continue;
			}

			$label = ! empty( $skill['label'] )
				? $skill['label']
				/* translators: %s: add-on name, e.g. "Gravity Flow". */
				: sprintf( __( 'Download %s Skill', 'gravityforms' ), $addon['label'] );

			$buttons[] = array(
				'url'   => $url,
				'label' => esc_html( $label ),
			);
		}

		// Render the download buttons in a row. Each button carries top spacing so
		// the row sits below the copy and, when the buttons wrap on narrow screens,
		// wrapped buttons keep vertical space; right spacing separates them within a
		// row. Uses the gform component spacer utilities (no custom CSS).
		$html    .= '<div>';
		$last_key = array_key_last( $buttons );

		foreach ( $buttons as $key => $button ) {
			$spacing = 'gform-spacing gform-spacing--top-3';
			if ( $key !== $last_key ) {
				$spacing .= ' gform-spacing--right-3';
			}

			$html .= sprintf(
				'<a href="%s" class="gform-button gform-button--size-r gform-button--white gform-button--width-auto %s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $button['url'] ),
				$spacing,
				$button['label']
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a "Learn more" documentation link that opens in a new tab.
	 *
	 * Centralizes the anchor markup shared by the section intros so the rel/target
	 * attributes and the translated link text stay consistent in one place.
	 *
	 * @since 3.1.0
	 *
	 * @param string $url   The documentation URL.
	 * @param string $title Accessible title describing the link target.
	 *
	 * @return string
	 */
	private static function get_doc_link( $url, $title ) {
		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer" title="%2$s">%3$s<span class="screen-reader-text">%4$s</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_html__( 'Learn more', 'gravityforms' ),
			esc_html__( '(opens in a new tab)', 'gravityforms' )
		);
	}

	/**
	 * Build the unavailable message HTML.
	 *
	 * Shows specific guidance for each missing dependency tier.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private static function get_unavailable_message() {
		$message = esc_html__( 'The WordPress Abilities API is not available. WordPress 6.9 or newer is required.', 'gravityforms' );

		return $message . ' ' . sprintf(
			'<strong>%s</strong>',
			esc_html__( 'All settings are disabled until the required dependencies are available.', 'gravityforms' )
		);
	}
}
