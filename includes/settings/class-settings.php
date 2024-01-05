<?php

namespace Gravity_Forms\Gravity_Forms\Settings;

use GFAPI;
use GFCommon;
use GF_Fields;
use GFForms;
use GFFormsModel;
use GF_Confirmation;

use WP_Error;

class_exists( '\GFForms' ) or die();

class Settings {

	/**
	 * Prefix for the name of rendered input fields.
	 *
	 * @since 2.5
	 *
	 * @var string $field_prefix Input field name prefix.
	 */
	protected $input_name_prefix = '_gform_setting';

	/**
	 * Capability required for saving settings.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	protected $capability;

	/**
	 * Fields to be rendered.
	 *
	 * @since 2.5
	 *
	 * @var array $fields Settings fields.
	 */
	protected $fields = array();

	/**
	 * Flags whether settings display in tabs.
	 *
	 * @since 2.5
	 *
	 * @var bool
	 */
	protected $is_tabbed = false;

	/**
	 * HTML to be rendered before displaying fields.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	protected $before_fields;

	/**
	 * HTML to be rendered after displaying fields.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	protected $after_fields;

	/**
	 * The current form being modified by the settings.
	 *
	 * @since 2.5
	 *
	 * @var integer
	 */
	protected $current_form;

	/**
	 * Primary Save button to be displayed in header.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	protected $save_button;

	/**
	 * If save postback has been processed.
	 *
	 * @since 2.5
	 *
	 * @var bool
	 */
	private $processed_postback = false;

	/**
	 * The previous field values, used when processing postback.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	private $_previous_values = array();

	/**
	 * The current field values.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	private $_saved_values = array();

	/**
	 * Called when postback values are valid to save submitted settings.
	 *
	 * @since 2.5
	 *
	 * @var string|callable
	 */
	private $_save_callback = '';

	/**
	 * Message to be displayed after save callback has been processed.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	private $postback_message = '';

	/**
	 * Determines validation message should be displayed.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	protected $postback_message_callback;

	/**
	 * Enqueued scripts to register as no-conflict.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	private $no_conflict_scripts = array();

	/**
	 * Enqueued styles to register as no-conflict.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	private $no_conflict_styles = array();

	/**
	 * Initialize Settings instance.
	 *
	 * @since 2.5
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		require_once 'class-fields.php';

		// Set fields.
		if ( rgar( $args, 'fields' ) ) {
			$this->set_fields( $args['fields'] );
		}

		if ( rgar( $args, 'capability' ) ) {
			$this->validate_capability( $args['capability'] );
			$this->capability = $args['capability'];
		}

		if ( rgar( $args, 'before_fields' ) && is_callable( $args['before_fields'] ) ) {
			$this->before_fields = $args['before_fields'];
		}

		if ( rgar( $args, 'after_fields' ) && is_callable( $args['after_fields'] ) ) {
			$this->after_fields = $args['after_fields'];
		}

		if ( rgar( $args, 'save_button' ) && is_array( $args['save_button'] ) ) {
			$this->add_save_button( $args['save_button'] );
		}

		if ( rgar( $args, 'current_form' ) ) {
			$this->current_form = (int) $args['current_form'];
		}

		if ( rgar( $args, 'input_name_prefix' ) ) {
			$this->input_name_prefix = (string) sanitize_html_class( $args['input_name_prefix'] );
		}

		if ( rgar( $args, 'initial_values' ) ) {
			$this->set_values( $args['initial_values'] );
		}

		if ( rgar( $args, 'save_callback' ) ) {
			$this->set_save_setting_callback( $args['save_callback'] );
		}

		if ( ! rgar( $args, 'save_callback' ) && rgar( $args, 'initial_values' ) && is_string( $args['initial_values'] ) && ! is_serialized( $args['initial_values'] ) ) {
			$this->set_save_setting_callback( $args['initial_values'] );
		}

		if ( rgar( $args, 'postback_message_callback' ) ) {
			$this->set_postback_message_callback( $args['postback_message_callback'] );
		}

		// Enqueue registered scripts/styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

		// Register no-conflict scripts/styles.
		add_filter( 'gform_noconflict_scripts', array( $this, 'filter_gform_noconflict_scripts' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'filter_gform_noconflict_styles' ) );

	}

	/**
	 * Capabilities must be either strings or arrays - any other object will should throw an exception.
	 *
	 * @since 2.5
	 *
	 * @param mixed $capability The capability value to validate.
	 */
	private function validate_capability( $capability ) {
		if ( is_array( $capability ) || is_string( $capability ) ) {
			return;
		}

		throw new \InvalidArgumentException( 'Settings page capabilities must be an array or string.' );
	}


	// # SCRIPT ENQUEUEING ---------------------------------------------------------------------------------------------

	/**
	 * Enqueue registered scripts and styles.
	 *
	 * @since 2.5
	 */
	public function action_admin_enqueue_scripts() {

		// Enqueue scripts.
		foreach ( $this->scripts() as $script ) {

			// If conditions are not fulfilled, do not enqueue.
			if ( ! $this->can_enqueue_script( rgar( $script, 'enqueue', array() ) ) ) {
				continue;
			}

			// Add to no-conflict scripts array.
			if ( ! in_array( $script['handle'], $this->no_conflict_scripts ) ) {
				$this->no_conflict_scripts[] = $script['handle'];
			}

			// Enqueue script.
			wp_enqueue_script(
				$script['handle'],
				rgar( $script, 'src', false ),
				rgar( $script, 'deps', array() ),
				rgar( $script, 'version', false ),
				rgar( $script, 'in_footer', false )
			);

			// Localize script strings.
			if ( rgar( $script, 'strings' ) ) {
				wp_localize_script( $script['handle'], $script['handle'] . '_strings', $script['strings'] );
			}

			if ( isset( $script['callback'] ) && is_callable( $script['callback'] ) ) {
				call_user_func( $script['callback'], $this );
			}

		}

		// Enqueue styles.
		foreach ( $this->styles() as $style ) {

			// If conditions are not fulfilled, do not enqueue.
			if ( ! $this->can_enqueue_script( rgar( $style, 'enqueue', array() ) ) ) {
				continue;
			}

			// Add to no-conflict styles array.
			if ( ! in_array( $style['handle'], $this->no_conflict_styles ) ) {
				$this->no_conflict_styles[] = $style['handle'];
			}

			// Enqueue style.
			wp_enqueue_style(
				$style['handle'],
				rgar( $style, 'src', false ),
				rgar( $style, 'deps', array() ),
				rgar( $style, 'version', false ),
				rgar( $style, 'media', 'all' )
			);

		}

	}

	/**
	 * Registered styles to enqueue when displaying settings.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function styles() {

		// Define base styles.
		$styles = array();

		// Register field styles.
		foreach ( $this->get_fields() as $group ) {
			$styles = array_merge( $styles, $this->get_scripts_for_group( $styles, $group, true ) );
			$styles = array_unique( $styles, SORT_REGULAR );
		}

		return $styles;

	}

	/**
	 * Registered scripts to enqueue when displaying settings.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		// Define base scripts.
		$scripts = array(
			array(
				'handle'  => 'gform_settings_dependencies',
				'src'     => GFCommon::get_base_url() . "/includes/settings/js/dependencies{$min}.js",
				'version' => GFForms::$version,
				'enqueue' => array(
					function() {
						$dependencies = $this->get_live_dependencies();
						return ! empty( $dependencies );
					},
				),
			),
			array(
				'handle'  => 'gform_settings_select_custom',
				'src'     => GFCommon::get_base_url() . "/includes/settings/js/select-custom{$min}.js",
				'version' => GFForms::$version,
				'enqueue' => array(
					function() {
						return $this->has_field_type( 'select_custom' );
					}
				),
			),
			array(
				'handle'  => 'gform_settings_tabs',
				'src'     => GFCommon::get_base_url() . "/includes/settings/js/tabs{$min}.js",
				'version' => GFForms::$version,
				'enqueue' => array(
					function() {
						return $this->is_tabbed;
					},
				),
			),
		);

		// Register field scripts.
		foreach ( $this->get_fields() as $group ) {
			$scripts = array_merge( $scripts, $this->get_scripts_for_group( $scripts, $group ) );
			$scripts = array_unique( $scripts, SORT_REGULAR );
		}

		return $scripts;

	}

	/**
	 * Get scripts/styles for a group of sections/fields.
	 *
	 * @since 2.5
	 *
	 * @param array $scripts Array of enqueued scripts.
	 * @param array $group   Group of sections/fields.
	 * @param bool  $styles  Get styles instead of scripts.
	 *
	 * @return array
	 */
	private function get_scripts_for_group( $scripts, $group, $styles = false ) {

		// Get nested key.
		$nested_key = GFCommon::get_nested_key( $group );

		foreach ( rgar( $group, $nested_key, array() ) as $item ) {
			if ( is_object( $item ) ) {
				$scripts = array_merge( $scripts, $styles ? $item->styles() : $item->scripts() );
				$scripts = array_unique( $scripts, SORT_REGULAR );
			}
			if ( rgar( $item, 'fields', array() ) ) {
				$scripts = array_merge( $scripts, $this->get_scripts_for_group( $scripts, $item, $styles ) );
				$scripts = array_unique( $scripts, SORT_REGULAR );
			}
		}

		return $scripts;

	}

	/**
	 * Determine if a script/style can be enqueued.
	 *
	 * @since 2.5
	 *
	 * @param array $conditions Required enqueuing conditions.
	 *
	 * @return bool
	 */
	private function can_enqueue_script( $conditions ) {

		// If no conditions are defined, return.
		if ( empty( $conditions ) ) {
			return true;
		}

		// Get form.
		$form = $this->get_current_form();

		// Loop through conditions and test.
		foreach ( $conditions as $condition ) {

			// If condition is a callable, try it.
			if ( is_callable( $condition ) ) {
				$callback_response = call_user_func( $condition, $form, false );
				if ( $callback_response ) {
					return true;
				}
			} else {
				$query_matches      = isset( $condition['query'] ) ? $this->script_request_condition_matches( $_GET, $condition['query'] ) : true;
				$post_matches       = isset( $condition['post'] ) ? $this->script_request_condition_matches( $_POST, $condition['post'] ) : true;
				$field_type_matches = isset( $condition['field_types'] ) ? $this->script_field_condition_matches( $condition['field_types'], $form ) : true;

				if ( $query_matches && $post_matches && $field_type_matches ) {
					return true;
				}
			}

		}

		return false;

	}

	/**
	 * Test a request condition.
	 *
	 * @since 2.5
	 *
	 * @param array $request Current request.
	 * @param array $query   Condition query.
	 *
	 * @return bool
	 */
	private function script_request_condition_matches( $request, $query ) {

		// Parse condition query.
		parse_str( $query, $query_array );

		foreach ( $query_array as $key => $value ) {

			switch ( $value ) {
				case '_notempty_':
					if ( rgempty( $key, $request ) ) {
						return false;
					}
					break;

				case '_empty_':
					if ( ! rgempty( $key, $request ) ) {
						return false;
					}
					break;

				default:
					if ( rgar( $request, $key ) != $value ) {
						return false;
					}
					break;

			}

		}

		return true;

	}

	/**
	 * Test a field type condition.
	 *
	 * @since 2.5
	 *
	 * @param string|array $field_types Field types to check for.
	 * @param array        $form        Form object.
	 *
	 * @return bool
	 */
	private function script_field_condition_matches( $field_types, $form ) {

		// Force field types to array.
		if ( ! is_array( $field_types ) ) {
			$field_types = array( $field_types );
		}

		// Get fields for form matching type.
		$fields = GFAPI::get_fields_by_type( $form, $field_types );

		if ( count( $fields ) > 0 ) {
			foreach ( $fields as $field ) {
				if ( $field->is_administrative() && ! $field->allowsPrepopulate && ! GFForms::get_page() ) {
					continue;
				}
				return true;
			}
		}

		return false;

	}

	/**
	 * Registers enqueued scripts to the no-conflict scripts whitelist.
	 *
	 * @since 2.5
	 *
	 * @param array $scripts Array of scripts to be whitelisted.
	 *
	 * @return array
	 */
	public function filter_gform_noconflict_scripts( $scripts ) {

		return array_merge( $scripts, $this->no_conflict_scripts );

	}

	/**
	 * Registers enqueued styles to the no-conflict styles whitelist.
	 *
	 * @since 2.5
	 *
	 * @param array $styles Array of styles to be whitelisted.
	 *
	 * @return array
	 */
	public function filter_gform_noconflict_styles( $styles ) {

		return array_merge( $styles, $this->no_conflict_styles );

	}


	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render fields.
	 * Handles enqueueing styles, processing postback.
	 *
	 * @since 2.5
	 *
	 * @param \GFAddOn $addon The Add-On responsible for rendering the settings page.
	 */
	public function render() {

		// Save field values.
		if ( self::is_save_postback() ) {
			$this->process_postback();
		}

		if ( $this->postback_message_callback ) {
			$this->postback_message = call_user_func( $this->postback_message_callback, $this->postback_message );
		}

		// Display validation message.
		if ( ! empty( $this->postback_message ) ) {

			// Get field errors.
			$field_errors = $this->get_field_errors();

			printf(
				'<div class="alert %s" role="alert">%s</div>',
				empty( $field_errors ) ? 'gforms_note_success' : 'gforms_note_error',
				$this->postback_message
			);

		}

		if ( rgget( 'subview' ) === 'confirmation' && rgget( 'duplicatedcid' ) ) {
			GF_Confirmation::output_duplicate_confirmation_notice();
		}

		// Get sections.
		$fields = $this->get_fields();

		?>

		<form id="gform-settings" class="gform_settings_form" data-js="page-loader" action="" method="post" enctype="multipart/form-data" novalidate>
			<?php

				if ( ! empty( $this->before_fields ) && is_callable( $this->before_fields ) ) {
					echo call_user_func( $this->before_fields );
				}

				// If settings are tabbed, render tab navigation and tab content.
				if ( $this->is_tabbed ) {

					$this->render_tab_navigation();

					foreach ( $fields as $tab ) {
						$this->render_tab( $tab );
					}

				} else {

					// Loop through and render each section.
					foreach ( $fields as $section ) {
						$this->render_section( $section );
					}

				}

				// Get save button.
				$save = $this->render_save_button();
				if ( ! empty( $save ) ) {
					printf( '<div class="gform-settings-save-container">%s</div>', $save );
				}

				if ( ! empty( $this->after_fields ) && is_callable( $this->after_fields ) ) {
					echo call_user_func( $this->after_fields );
				}

				wp_nonce_field( 'gform_settings_save', 'gform_settings_save_nonce' );

			?>
		</form>

		<?php

		// Get live dependencies.
		$live_dependencies = $this->get_live_dependencies();

		// Enqueue live dependencies.
		if ( ! empty( $live_dependencies ) ) {
			echo '<script>';
			foreach ( $live_dependencies as $dependency ) {
				echo 'new GF_Settings_Dependencies( ' . wp_json_encode( $dependency ) . ' );';
			}
			echo '</script>';
		}

	}

	/**
	 * Render tab navigation above settings sections.
	 *
	 * @since 2.5
	 */
	public function render_tab_navigation() {

		// If settings are not tabbed, exit.
		if ( ! $this->is_tabbed ) {
			return;
		}

		// Get fields, initialize tabs array.
		$fields = $this->get_fields();
		$tabs   = array();

		// Get tab names, labels.
		foreach ( $fields as $i => $tab ) {

			$tabs[] = array(
				'name'       => $tab['id'],
				'label'      => rgar( $tab, 'label' ) ? $tab['label'] : rgar( $tab, 'title' ),
				'dependency' => rgar( $tab, 'dependency', array() ),
			);

		}

		// Get active tab.
		$active_tab = $this->get_active_tab();

		echo '<nav class="gform-settings-tabs__navigation" role="tablist">';
		foreach ( $tabs as $i => $tab ) {
			printf(
				'<a href="#" role="tab" aria-selected="%3$s" id="gform-settings-tab-%2$s" data-tab="%2$s" class="%4$s"%5$s>%1$s</a>',
				esc_html( $tab['label'] ),
				esc_attr( $tab['name'] ),
				$tab['name'] === $active_tab ? 'true' : 'false',
				$tab['name'] === $active_tab ? 'active' : '',
				! $this->is_dependency_met( $tab['dependency'] ) ? ' style="display:none;"' : ''
			);
		}
		echo '</nav>';

		printf(
			'<input type="hidden" name="gform_settings_tab" value="%s" />',
			esc_attr( $active_tab )
		);

	}

	/**
	 * Render a single tab of fields.
	 *
	 * @since 2.5
	 *
	 * @param array $tab Tab properties.
	 */
	public function render_tab( $tab ) {

		// Get active tab.
		$active_tab = $this->get_active_tab();

		// Open tab container.
		printf(
			'<div class="gform-settings-tabs__container%2$s" role="tabpanel" aria-hidden="%3$s" data-tab="%1$s" aria-labelledby="gform-settings-tab-%1$s">',
			esc_attr( $tab['id'] ),
			$tab['id'] === $active_tab ? ' active' : '',
			$tab['id'] === $active_tab ? 'false' : 'true'
		);

		// Loop through and render each section.
		foreach ( $tab['sections'] as $section ) {
			$this->render_section( $section );
		}

		// Close tab container.
		echo '</div>';

	}

	/**
	 * Render a single section of fields.
	 *
	 * @since 2.5
	 *
	 * @param array $section Section properties.
	 */
	public function render_section( $section ) {

		// Prepare section classes.
		$class = array( 'gform-settings-panel' );

		// Add defined classes.
		if ( rgar( $section, 'class' ) ) {
			$section['class'] = explode( ' ', $section['class'] );
			$class            = array_merge( $class, $section['class'] );
		}

		if ( rgar( $section, 'title' ) || ( rgar( $section, 'collapsible' ) && rgar( $section, 'id' ) ) ) {
			$class[] = 'gform-settings-panel--with-title';
		}

		// Add collapsible classes.
		if ( rgar( $section, 'collapsible' ) && rgar( $section, 'id' ) ) {

			$class[] = 'gform-settings-panel--collapsible';

			// Add collapsed class.
			if ( self::is_section_collapsed( $section ) ) {
				$class[] = 'gform-settings-panel--collapsed';
			}

		}

		// Add card layout class.
		if ( self::has_card_layout( $section ) ) {
			$class[] = 'gform-settings-panel--card';
		}

		// If dependency is not met for section, do not render if no live dependency.
		// Otherwise, set section to hidden.
		if ( ! $this->is_dependency_met( rgar( $section, 'dependency' ) ) && ! rgars( $section, 'dependency/live' ) ) {
			return;
		} else if ( ! $this->is_dependency_met( rgar( $section, 'dependency' ) ) ) {
			$section['style'] = rgar( $section, 'style' ) . 'display:none;';
		}

		// Open section container.
		printf(
			'<fieldset id="%s" class="%s"%s>',
			esc_attr( $this->get_section_id( $section ) ),
			implode(' ', $class ),
			rgar( $section, 'style' ) ? sprintf( ' style="%s"', esc_attr( $section['style'] ) ) : ''
		);

		// Add section header.
		if ( rgar( $section, 'title' ) || ( rgar( $section, 'collapsible' ) && rgar( $section, 'id' ) ) ) {

			// Display title.
			if ( rgar( $section, 'title' ) ) {
				printf(
					'<legend class="gform-settings-panel__title gform-settings-panel__title--header">%s %s</legend>',
					esc_html( $section['title'] ),
					self::maybe_get_tooltip( $section )
				);
			}

			// Display collapsible toggle.
			if ( rgar( $section, 'collapsible' ) && rgar( $section, 'id' ) ) {
				?>
				<span class="gform-settings-panel__collapsible-control">
					<input
							type="checkbox"
							id="gform_settings_section_collapsed_<?php echo esc_attr( $section['id'] ); ?>"
							name="gform_settings_section_collapsed_<?php echo esc_attr( $section['id'] ); ?>"
							value="1"
							onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
							<?php checked( true, self::is_section_collapsed( $section ), true ); ?>
					/>
					<label class="gform-settings-panel__collapsible-toggle" for="gform_settings_section_collapsed_uninstall"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle %s Section', 'gravityforms' ), esc_html( $section['title' ]) ); ?></span></label>
				</span>
				<?php
			}
		}

		// Open settings table.
		echo '<div class="gform-settings-panel__content">';

		// Display section description.
		if ( rgar( $section, 'description' ) ) {
			printf( '<div class="gform-settings-description gform-kitchen-sink">%s</div>', $section['description'] );
		}

		/**
		 * Loop through fields and render.
		 *
		 * @var Fields\Base $field
		 */
		foreach ( rgar( $section, 'fields', array() ) as $field ) {

			if ( is_wp_error( $field ) || is_array( $field ) ) {
				continue;
			}

			$this->render_field( $field );

		}

		// Close table and section container.
		echo '</div></fieldset>';

	}

	/**
	 * Render a single field.
	 *
	 * @since 2.5
	 *
	 * @param Fields\Base $field
	 */
	public function render_field( $field ) {

		// If dependency is not met for field, do not render if no live dependency.
		// Otherwise, set field to hidden.
		if ( ! $this->is_dependency_met( rgobj( $field, 'dependency' ) ) && ! rgars( $field, 'dependency/live' ) ) {
			return;
		} else if ( ! $this->is_dependency_met( rgobj( $field, 'dependency' ) ) ) {
			$field->hidden = true;
		}

		// Prepare hidden styling.
		$hidden = rgar( $field, 'hidden' ) === true || rgar( $field, 'type' ) === 'hidden' ? ' style="display:none;"' : '';

		$field_name = $field->name ? str_replace( array( '[', ']' ), array( '_', null ), $field->name ) : '';

		printf(
			'<div id="gform_setting_%s" class="gform-settings-field gform-settings-field__%s" %s>',
			esc_attr( $field_name ),
			$field->type,
			$hidden
		);

		// Display field label.
		if ( rgobj( $field, 'label' ) ) {
			printf(
				'<div class="gform-settings-field__header"><label class="gform-settings-label" for="%s">%s%s</label>%s</div>',
				esc_attr( $field->name ),
				rgobj( $field, 'label' ),
				$field->required ? '<span class="required">(' . __( 'Required', 'gravityforms' ) . ')</span>' : '',
				self::maybe_get_tooltip( $field )
			);
		}


		// Display field input.
		echo $field->prepare_markup();

		echo '</div>';


	}

	/**
	 * Determine if section is collapsed.
	 *    If postback, uses state upon submission.
	 *
	 * @since 2.5
	 *
	 * @param array $props Section properties.
	 *
	 * @return bool
	 */
	private static function is_section_collapsed( $props = array() ) {

		return empty( $_POST ) ? rgar( $props, 'is_collapsed' ) : (bool) rgpost( 'gform_settings_section_collapsed_' . $props['id'] );

	}

	/**
	 * Display Save button in page header.
	 *
	 * @since 2.5
	 *
	 * @param string $html Existing Save button HTML.
	 *
	 * @return string
	 */
	public function render_save_button( $html = '' ) {

		/**
		 * If save button has not been created, initialize it.
		 * Save button has to be initialized separately due to translatable strings.
		 */
		if ( empty( $this->save_button ) ) {
			$this->add_save_button();
		}

		// Get Save button properties.
		$save_props = $this->save_button;

		// Prepare Save button markup.
		$html .= sprintf(
			'<button type="submit" id="gform-settings-save" name="gform-settings-save" value="save" form="gform-settings" class="%2$s"%3$s>%1$s</button>',
			esc_html( rgar( $save_props, 'value' ) ),
			esc_attr( $save_props['class'] ),
			! $this->is_dependency_met( rgar( $save_props, 'dependency' ) ) ? 'style="display:none;"' : ''
		);

		/**
		 * Modify the output of the settings save button.
		 *
		 * @since 2.5
		 *
		 * @param string                               $html HTML of the save button.
		 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $this Current instance of the Settings Framework.
		 */
		$html = apply_filters( 'gform_settings_save_button', $html, $this );

		return $html;

	}

	/**
	 * Add default save button to a new settings section.
	 *
	 * @since 2.5
	 *
	 * @param array $props Save button properties.
	 */
	private function add_save_button( $props = array() ) {

		$this->save_button = wp_parse_args(
			$props,
			array(
				'name'     => 'save',
				'value'    => esc_html__( 'Save Settings', 'gravityforms' ) . ' &nbsp;&rarr;',
				'class'    => 'primary button large',
				'messages' => array(
					'save'  => esc_html__( 'Settings updated.', 'gravityforms' ),
					'error' => esc_html__( 'There was an error while saving your settings.', 'gravityforms' ),
				),
			)
		);

	}





	// # RENDER HELPER METHODS -----------------------------------------------------------------------------------------

	/**
	 * Returns an array of fields that have a registered live dependency.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	private function get_live_dependencies() {

		// Initialize dependencies array.
		$dependencies = array();

		// Get fields.
		$groups = $this->get_fields();

		// Add save button to groups so its dependencies can be registered.
		$groups[] = array(
			'fields' => array(
				$this->save_button,
			),
		);

		// Loop through sections and fields, get field dependencies.
		foreach ( $groups as $group ) {

			// Get dependencies in group.
			$dependencies = array_merge( $dependencies, $this->get_live_dependencies_for_group( $group ) );

			// If group does not have live dependencies, skip.
			if ( ! rgars( $group, 'dependency/live' ) ) {
				continue;
			}

			// Prepare dependency.
			$dependency = array(
				'prefix'   => $this->input_name_prefix,
				'operator' => rgars( $group, 'dependency/operator' ) ? strtoupper( $group['dependency']['operator'] ) : 'ALL',
				'target'   => $this->get_target_for_live_dependency( $group ),
				'fields'   => rgars( $group, 'dependency/fields' ),
			);

			// If no target field is defined, skip.
			if ( ! rgars( $dependency, 'target/field' ) ) {
				continue;
			}

			// Remove brackets for field target.
			if ( $dependency['target']['type'] === 'field' ) {
				$dependency['target']['field'] = str_replace( array( '[', ']', ), array( '_', null ), $dependency['target']['field'] );
			}

			// Define callback.
			if ( rgars( $group, 'dependency/callback/js' ) ) {
				$dependency['callback'] = $group['dependency']['callback']['js'];
			}

			foreach ( $dependency['fields'] as $f => $_field ) {

				// Get field type.
				$dependency_field                         = $this->get_field( $_field['field'] );
				$dependency['fields'][ $f ]['field_type'] = $dependency_field->type;

				// If field is a checkbox, check options.
				if ( rgar( $dependency_field, 'type' ) === 'checkbox' ) {

					// If no values were provided or only provided value is "1", set to first choice name.
					if ( ! rgar( $_field, 'values' ) || ( rgar( $_field, 'values' ) && is_array( $_field['values'] ) && $_field['values'][0] == 1 ) ) {
						$choices        = array_values( rgar( $dependency_field, 'choices', array() ) );
						$dependency['fields'][ $f ]['values'] = array( rgars( $choices, '0/name' ) );
					}

				}

			}

			// Add to dependencies array.
			$dependencies[ $dependency['target']['field'] ] = $dependency;

		}

		return $dependencies;

	}

	/**
	 * Returns an array of fields that have a registered live dependency for a specific group.
	 *
	 * @since 2.5
	 *
	 * @param array $group Group of sections or fields.
	 *
	 * @return array
	 */
	private function get_live_dependencies_for_group( $group ) {

		$dependencies = array();

		// Get nested key.
		$nested_key = GFCommon::get_nested_key( $group );

		// Loop through fields, add dependencies.
		foreach ( rgar( $group, $nested_key, array() ) as $item ) {

			if ( ! rgar( $item, 'id' ) ) {
				$item['id'] = $this->get_section_id( $item );
			}

			// If field has nested fields, add dependencies.
			if ( rgar( $item, 'sections' ) || rgar( $item, 'fields' ) ) {
				$dependencies = array_merge( $dependencies, $this->get_live_dependencies_for_group( $item ) );
			}

			// If field does not have a live dependency, skip.
			if ( ! rgars( $item, 'dependency/live' ) ) {
				continue;
			}

			// Prepare dependency.
			$dependency = array(
				'prefix'   => $this->input_name_prefix,
				'operator' => rgars( $item, 'dependency/operator' ) ? strtoupper( $item['dependency']['operator'] ) : 'ALL',
				'target'   => $this->get_target_for_live_dependency( $item ),
				'fields'   => rgars( $item, 'dependency/fields' ),
			);

			// Override target type for save button.
			if ( $dependency['target']['type'] === 'field' && rgar( $item, 'type' ) === 'save' ) {
				$dependency['target']['type'] = 'save';
			}

			// If no target field is defined, skip.
			if ( ! rgars( $dependency, 'target/field' ) && rgars( $dependency, 'target/type' ) !== 'save' ) {
				continue;
			}

			// Remove brackets for field target.
			if ( $dependency['target']['type'] === 'field' ) {
				$dependency['target']['field'] = str_replace( array( '[', ']', ), array( '_', null ), $dependency['target']['field'] );
			}

			// Define callback.
			if ( rgars( $item, 'dependency/callback/js' ) ) {
				$dependency['callback'] = $item['dependency']['callback']['js'];
			}

			foreach ( $dependency['fields'] as $f => $_field ) {

				// Get field type.
				$dependency_field                         = $this->get_field( $_field['field'] );
				$dependency['fields'][ $f ]['field_type'] = $dependency_field->type;

				// If field is a checkbox, check options.
				if ( rgar( $dependency_field, 'type' ) === 'checkbox' ) {

					// If no values were provided or only provided value is "1", set to first choice name.
					if ( ! rgar( $_field, 'values' ) || ( rgar( $_field, 'values' ) && is_array( $_field['values'] ) && $_field['values'][0] == 1 ) ) {
						$choices        = array_values( rgar( $dependency_field, 'choices', array() ) );
						$dependency['fields'][ $f ]['values'] = array( rgars( $choices, '0/name' ) );
					}

				}

			}

			// Add to dependencies array.
			$dependencies[ $dependency['target']['field'] ] = $dependency;

		}

		return $dependencies;

	}

	/**
	 * Returns the target type and field name/ID for live dependency.
	 *
	 * @since 2.5.13
	 *
	 * @param array $item Settings tab, section or field.
	 *
	 * @return array
	 */
	private function get_target_for_live_dependency( $item ) {

		$target = array(
			'type'  => 'field',
			'field' => rgar( $item, 'name' ),
		);

		if ( rgar( $item, 'sections' ) ) {
			$target['type']  = 'tab';
			$target['field'] = rgar( $item, 'id' );
		} elseif ( rgar( $item, 'fields' ) && ! rgar( $item, 'type' ) ) {
			$target['type']  = 'section';
			$target['field'] = $this->get_section_id( $item );
		}

		return $target;

	}

	/**
	 * Get the section ID or a fallback if none is set.
	 *
	 * Dependencies rely on a section having an ID, so if a section ID isn't set, we need to generate one.
	 *
	 * @since 2.5.13
	 *
	 * @param array $section The settings section
	 *
	 * @return mixed|string
	 */
	private function get_section_id( $section ) {
		$section_prefix = 'gform-settings-section-';
		if ( rgar( $section, 'id' ) ) {
			return $section_prefix . $section['id'];
		} else {
			return rgar( $section, 'title' ) ? $section_prefix . sanitize_title( $section['title'] ) : '';
		}
	}

	/**
	 * Returns message to display when settings have been successfully saved.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_save_success_message() {

		$success_message = rgars( $this->save_button, 'messages/save' ) ? rgars( $this->save_button, 'messages/save' ) : rgars( $this->save_button, 'messages/success' );

		return $success_message;

	}

	/**
	 * Returns message to display when settings could not be saved.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_save_error_message() {

		return rgars( $this->save_button, 'messages/error' );

	}

	/**
	 * Determines if the conditions to display a field or section have been met.
	 *
	 * @since 2.5
	 *
	 * @param string|array|callable|null $dependency Condition to be met.
	 *
	 * @return bool
	 */
	public function is_dependency_met( $dependency ) {

		// If no dependency was provided, return.
		if ( ! $dependency ) {
			return true;
		}

		// If this is a legacy dependency, process the old way.
		if ( ! rgar( $dependency, 'fields' ) ) {
			return $this->is_legacy_dependency_met( $dependency );
		}

		// Handle callback dependency.
		if ( rgars( $dependency, 'callback/php' ) && is_callable( $dependency['callback']['php'] ) ) {
			return call_user_func( $dependency['callback']['php'], $this );
		}

		// Define operator, evaluated rules, dependency met.
		$operator        = rgar( $dependency, 'operator' ) ? strtoupper( $dependency['operator'] ) : 'ALL';
		$evaluated_rules = 0;
		$dependency_met  = false;

		// Loop through fields, evaluate rules.
		foreach ( rgar( $dependency, 'fields' ) as $rule ) {

			// Get field.
			$field = $this->get_field( $rule['field'] );

			// If field is a checkbox, check options.
			if ( rgar( $field, 'type' ) === 'checkbox' ) {

				// If no values were provided or only provided value is "1", set to first choice name.
				if ( ! rgar( $rule, 'values' ) || ( rgar( $rule, 'values' ) && is_array( $rule['values'] ) && $rule['values'][0] == 1 ) ) {
					$choices        = array_values( rgar( $field, 'choices', array() ) );
					$rule['values'] = array( rgars( $choices, '0/name' ) );
				}

				// Loop through values, check for checked checkbox.
				foreach ( $rule['values'] as $value ) {

					if ( $this->get_value( $value ) == 1 ) {
						$evaluated_rules++;
						continue 2;
					}

				}

				continue;

			}

			// Get field value.
			$field_value = $this->get_value( $rule['field'] );

			// Force values to array.
			if ( rgar( $rule, 'values' ) ) {
				$rule['values'] = is_array( $rule['values'] ) ? $rule['values'] : array( $rule['values'] );
			} else {
				$rule['values'] = array( '_notempty_' );
			}

			// Loop through values and evaluate.
			foreach ( $rule['values'] as $value ) {

				if ( $value === '_notempty_' && ! rgblank( $field_value ) && $field_value != '0' ) {
					$evaluated_rules++;
					continue 2;
				}

				if ( $value === $field_value ) {
					$evaluated_rules++;
					continue 2;
				}

			}

		}

		// Determine if dependency was met based on rules evaluated and operator.
		if ( 'ALL' === $operator && $evaluated_rules === count( $dependency['fields'] ) ) {
			$dependency_met = true;
		} elseif ( 'ANY' === $operator && $evaluated_rules > 0 ) {
			$dependency_met = true;
		}

		return $dependency_met;

	}

	/**
	 * Determines if the conditions to display a field or section have been met.
	 *    Handles legacy dependencies for pre-Gravity Forms 2.5.
	 *
	 * @since 2.5
	 *
	 * @param string|array|callable|null $dependency Condition to be met.
	 *
	 * @return bool
	 */
	public function is_legacy_dependency_met( $dependency ) {

		// If no dependency was provided, return.
		if ( ! $dependency ) {
			return true;
		}

		// If a callback was provided, run it and return the result.
		if ( is_callable( $dependency ) ) {
			return call_user_func( $dependency, $this );
		}

		// Get dependency field and value.
		if ( is_array( $dependency ) ) {
			$dependency_field = rgar( $dependency, 'field' );
			$dependency_value = rgar( $dependency, 'values' );
		} else {
			$dependency_field = $dependency;
			$dependency_value = '_notempty_';
		}

		// Set dependency value to an array.
		if ( ! is_array( $dependency_value ) ) {
			$dependency_value = array( $dependency_value );
		}

		// Get current field value.
		$current_value = $this->get_value( $dependency_field );

		// Loop through dependency values, look for match.
		foreach ( $dependency_value as $value ) {

			// If values are a match, return.
			if ( $current_value == $value ) {
				return true;
			}

			// If value is not empty, return.
			if ( '_notempty_' === $value && ! rgblank( $current_value ) ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Returns the markup for a tooltip, if provided.
	 *
	 * @since 2.5
	 *
	 * @param array|Fields\Base $props Field/Choice properties.
	 *
	 * @return string
	 */
	public static function maybe_get_tooltip( $props ) {

		// Initialize return string.
		$html = '';

		// If a tooltip property exists, prepare tooltip.
		if ( isset( $props['tooltip'] ) ) {

			// If tooltip has already been processed, use it.
			if ( strpos( $props['tooltip'], 'class="gf_tooltip ' ) !== false ) {
				$html = $props['tooltip'];
			} else {
				$html = ' ' . gform_tooltip( $props['tooltip'], rgar( $props, 'tooltip_class' ), true );
			}

		}

		return $html;

	}

	/**
	 * Determine whether a section gets a card layout.
	 *
	 * If a section has one field, and the field type is card, it gets the card layout.
	 *
	 * @since 2.5.7
	 *
	 * @param array $section Settings section
	 *
	 * @return bool
	 */
	public function has_card_layout( $section ) {
		if ( ! rgar( $section, 'fields' ) || 1 !== count( $section['fields'] ) ) {
			return false;
		}

		if ( ! rgars( $section['fields'], '0/type' ) ) {
			return false;
		}

		if ( 'card' !== $section['fields'][0]['type'] ) {
			return false;
		}

		return true;
	}




	// # SUBMISSION PROCESSING -----------------------------------------------------------------------------------------

	/**
	 * Processes the save settings callback.
	 *    Validates values.
	 *    If valid, filters and saves values.
	 *    If invalid, displays error message.
	 *
	 * @since 2.5
	 */
	public function process_postback() {

		global $_gf_settings_posted_values;

		// If postback has already been processed, exit.
		if ( $this->processed_postback ) {
			return;
		}

		// If save dependency is not met, exit.
		if ( ! $this->is_dependency_met( rgar( $this->save_button, 'dependency' ) ) ) {
			return;
		}

		// Verify nonce.
		check_admin_referer( 'gform_settings_save', 'gform_settings_save_nonce' );

		// If user does not have access, exit.
		if ( ! $this->current_user_has_access() ) {
			esc_html_e( 'Access denied.', 'gravityforms' );
			return;
		}

		// Set previous values.
		$this->set_previous_values( $this->_saved_values );

		// Get posted values.
		$values = $this->get_posted_values();

		// Validate settings.
		$is_valid = $this->validate( $values );

		// Set processed postback flag.
		$this->processed_postback = true;

		// If values are valid, filter and save.
		if ( $is_valid ) {

			// Filter values.
			$values = $this->filter_values( $values );

			// Save values.
			$this->save_values( $values );
			$this->set_values( $values );

			// Reset postback values.
			$_gf_settings_posted_values = array();

			// Set validation message.
			$this->postback_message = $this->get_save_success_message();

		} else {

			// Set validation message.
			$this->postback_message = $this->get_save_error_message();

		}

	}

	/**
	 * Set the save success message after a save redirect.
	 *
	 * @since 2.5
	 */
	public function set_save_message_after_redirect() {
		$this->postback_message = $this->get_save_success_message();
	}

	/**
	 * Check if the current user has the capabilities to access these settings.
	 *
	 * @since 2.5
	 *
	 * @return bool
	 */
	public function current_user_has_access() {
		return ! $this->capability || GFCommon::current_user_can_any( $this->capability );
	}

	/**
	 * Filter posted field values.
	 *    Runs `save_callback` when defined for field.
	 *
	 * @since 2.5
	 *
	 * @param array $values Posted field values.
	 *
	 * @return array
	 */
	public function filter_values( $values ) {

		// Get fields.
		$groups = $this->get_fields();

		// Loop through tabs or sections and apply filters.
		foreach ( $groups as $group ) {
			$values = $this->filter_group_values( $values, $group );
		}

		return $values;

	}

	/**
	 * Filter posted field values for section.
	 * Runs `save_callback` when defined for field.
	 *
	 * @since 2.5
	 *
	 * @param array $values  Posted field values.
	 * @param array $group  Array of sections or fields.
	 *
	 * @return array
	 */
	private function filter_group_values( $values, $group ) {

		// If dependency is not met for section, skip.
		if ( ! $this->is_dependency_met( rgar( $group, 'dependency' ) ) ) {
			return $values;
		}

		$nested_key = GFCommon::get_nested_key( $group );

		/**
		 * Loop through items, apply filters.
		 *
		 * @var array|Fields\Base $item
		 */
		foreach ( rgar( $group, $nested_key, array() ) as $item ) {

			// If dependency is not met for field, skip.
			if ( ! $this->is_dependency_met( rgar( $item, 'dependency' ) ) ) {
				continue;
			}

			// If this is a field, filter values.
			if ( is_object( $item ) ) {

				// Get field value.
				$field_value = $this->get_field_value( $item, $values );

				// Filter value.
				$values = $item->save_field( $values, $field_value );

			}

			// Validate nested fields.
			if ( rgar( $item, 'fields' ) ) {
				$values = $this->filter_group_values( $values, array( $item ) );
			}

		}

		return $values;

	}

	/**
	 * Gets the submitted field value.
	 *
	 * Fields with complex names are parsed into an indexed array to facilitate with value lookup.
	 *
	 * @since 2.5
	 *
	 * @see   Fields\Base::get_parsed_name()
	 *
	 * @param Fields\Base $item   A Settings Field instance.
	 * @param array       $values Array of settings values.
	 *
	 * @return array|\ArrayAccess|mixed|string|null
	 */
	private function get_field_value( $item, $values ) {
		$name = $item->get_parsed_name();

		if ( is_array( $name ) ) {
			return rgars( $values, implode( '/', $name ) );
		}

		return rgar( $values, $name );
	}

	/**
	 * Save posted values.
	 *
	 * @since 2.5
	 *
	 * @param array $values Posted values.
	 */
	public function save_values( $values ) {

		// Get save callback.
		$callback = $this->get_save_callback();

		// If callback is callable, call it.
		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $values );
		} else if ( is_string( $callback ) ) {
			update_option( $callback, $values );
		}

	}





	// # FIELD HELPER METHODS ------------------------------------------------------------------------------------------

	/**
	 * Check if defined fields contain a specific field type.
	 *
	 * @since 2.5
	 *
	 * @param string     $type   Field type to search for.
	 * @param array|bool $groups Array of tabs or sections to search through. Defaults to defined fields.
	 *
	 * @return bool
	 */
	public function has_field_type( $type, $groups = false ) {

		// Get fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// If no fields are defined, return.
		if ( empty( $groups ) ) {
			return false;
		}

		// Loop through groups, check for field type.
		foreach ( $groups as $group ) {

			// Determine nested key.
			$nested_key = GFCommon::get_nested_key( $group );

			foreach ( $group[ $nested_key ] as $field ) {

				// If field is a match, return.
				if ( rgobj( $field, 'type' ) === $type ) {
					return true;
				}

				if ( rgobj( $field, 'fields' ) && $this->has_field_type( $type, $field->fields ) ) {
					return true;
				}

			}

		}

		return false;

	}






	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate settings fields.
	 *    Fields can be invalid when marked as required and have a blank value or fails a custom validation check.
	 *    Use the `validation_callback` field property to implement a custom validation check.
	 *
	 * @since 2.5
	 *
	 * @param array $values Posted field values.
	 *
	 * @return bool
	 */
	public function validate( $values ) {

		// Get fields.
		$groups = $this->get_fields();

		// Loop through tabs or sections and validate.
		foreach ( $groups as $group ) {
			$this->validate_group( $values, $group );
		}

		// Get field errors.
		$field_errors = $this->get_field_errors();

		return empty( $field_errors );

	}

	/**
	 * Validate settings field group.
	 *
	 * @since 2.5
	 *
	 * @param array $values Posted field values.
	 * @param array $group  Array of sections or fields.
	 */
	private function validate_group( $values, $group ) {

		// If section dependency is not met, exit.
		if ( ! $this->is_dependency_met( rgar( $group, 'dependency' ) ) ) {
			return;
		}

		$nested_key = GFCommon::get_nested_key( $group );

		/**
		 * Loop through fields and validate.
		 *
		 * @var Fields\Base $field
		 */
		foreach ( rgar( $group, $nested_key, array() ) as $field ) {

			// If field dependency is not met, skip.
			if ( ! $this->is_dependency_met( rgar( $field, 'dependency' ) ) ) {
				continue;
			}

			// Validate nested fields.
			if ( rgar( $field, 'fields' ) ) {
				$this->validate_group( $values, $field );
			}

			// $field needs to be an object to run the subsequent steps, if not, bail.
			if ( ! is_object( $field ) ) {
				continue;
			}

			if ( method_exists( $field, 'get_values_from_post' ) ) {
				// Get field value from field object.
				$field_value = $field->get_values_from_post( $values );
			} else {
				// Get field value.
				$field_value = $this->get_value( $field->name, null, $values );
			}

			// Validate field.
			$field->handle_validation( $field_value );
		}

	}

	/**
	 * Get errors for all fields.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_field_errors() {

		$errors = array();

		// Get fields.
		$groups = $this->get_fields();

		// Loop through tabs or sections, find errors.
		foreach ( $groups as $group ) {
			$errors = array_merge( $errors, $this->get_group_errors( $group ) );
		}

		return $errors;

	}

	/**
	 * Get field errors for a section.
	 *
	 * @since 2.5
	 *
	 * @param array $group Array of sections or fields.
	 *
	 * @return array
	 */
	private function get_group_errors( $group ) {

		$errors     = array();
		$nested_key = GFCommon::get_nested_key( $group );

		/**
		 * Loop through fields in section, find errors.
		 *
		 * @var Fields\Base $field
		 */
		foreach ( rgar( $group, $nested_key, array() ) as $field ) {

			// If field has an error, add it.
			if ( is_object( $field ) && $field->get_error() ) {
				$errors[ $field->name ] = $field->get_error();
			}

			// If field has inputs, check inputs for errors.
			if ( rgobj( $field, 'inputs' ) ) {
				/**
				 * @var Fields\Base $input
				 */
				foreach ( $field->inputs as $input ) {
					if ( $input->get_error() ) {
						$errors[ $input->name ] = $input->get_error();
					}
				}
			}

			// If field has nested fields, find errors.
			if ( rgar( $field, 'fields' ) ) {
				$errors = array_merge( $errors, $this->get_group_errors( $field ) );
			}

		}

		return $errors;

	}





	// # GETTER / SETTER METHODS ---------------------------------------------------------------------------------------

	/**
	 * Get the tab currently being displayed.
	 *
	 * @since 2.5
	 *
	 * @return bool|string
	 */
	public function get_active_tab() {

		// If settings are not tabbed, return.
		if ( ! $this->is_tabbed ) {
			return false;
		}

		// Get tabs, field errors.
		$tabs   = array_values( $this->get_fields() );
		$errors = $this->get_field_errors();

		if ( empty( $errors ) ) {
			return rgpost( 'gform_settings_tab' ) ? sanitize_text_field( $_POST['gform_settings_tab'] ) : rgars( $tabs, '0/id' );
		}

		// Get failed field names.
		$failed_fields = array_keys( $errors );

		// Search for first failed field.
		foreach ( $tabs as $tab ) {
			foreach ( $tab['sections'] as $section ) {
				foreach ( $section['fields'] as $field ) {
					if ( in_array( rgar( $field, 'name' ), $failed_fields ) ) {
						return rgar( $tab, 'id' );
					}
				}
			}
		}

		return rgars( $tabs, '0/id' );

	}

	/**
	 * Get the current form object.
	 *
	 * @since 2.5
	 *
	 * @return bool|array
	 */
	public function get_current_form() {

		static $form;

		if ( isset( $form ) ) {
			return $form;
		}

		// Get current form ID.
		$form_id = $this->current_form ? $this->current_form : rgget( 'id' );

		// If there is no form ID, return.
		if ( ! $form_id ) {
			return false;
		}

		// Get form.
		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return false;
		}

		if ( is_admin() ) {
			$form = gf_apply_filters( array( 'gform_admin_pre_render', $form_id ), $form );
		}

		return $form;

	}

	/**
	 * Get the current form ID.
	 *
	 * @since 2.5
	 *
	 * @return bool|int
	 */
	public function get_current_form_id() {

		// Get the current form.
		$form = $this->get_current_form();

		return $form ? rgar( $form, 'id' ) : false;

	}

	/**
	 * Add a field to existing defined fields.
	 *
	 * @since 2.5
	 *
	 * @param string              $name     Name of field to insert before/after.
	 * @param array|Fields\Base[] $fields   Field(s) to add.
	 * @param string              $position Insert field "before" or "after" existing field.
	 * @param array|false         $groups   Array of sections/fields.
	 *
	 * @return array
	 */
	public function add_field( $name, $fields, $position, $groups = false ) {

		// If only one Field object is provided, push to array.
		if ( rgar( $fields, 'name' ) ) {
			$fields = array( $fields );
		}

		// Initialize fields.
		$fields = $this->initialize_fields( $fields );

		// Determine position.
		$position = $position === 'before' ? 0 : 1;

		// Get fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// Loop through groups, add field.
		foreach ( $groups as &$group ) {
			$group = $this->add_field_to_group( $group, $name, $fields, $position );
		}

		// Update fields.
		$this->fields = $groups;

		return $this->fields;

	}

	/**
	 * Add field to a group of fields.
	 *
	 * @since 2.5
	 *
	 * @param array|Fields\Base $group    Group of sections/fields.
	 * @param string            $name     Name of field to insert before/after.
	 * @param Fields\Base[]     $fields   Field(s) to add.
	 * @param int               $position Insert field before or after existing field.
	 *
	 * @return array|Fields\Base
	 */
	private function add_field_to_group( &$group, $name, $fields, $position ) {

		// Get nested key.
		$nested_key = GFCommon::get_nested_key( $group );

		// If nested key does not exist or is empty, return.
		if ( ! isset( $group[ $nested_key ] ) || empty( $group[ $nested_key ] ) ) {
			return $group;
		}

		foreach ( $group[ $nested_key ] as $i => &$item ) {

			// If item name matches, add field(s).
			if ( rgar( $item, 'name' ) === $name ) {
				array_splice( $group[ $nested_key ], $i + $position, 0, $fields );
				return $group;
			}

			// If field has its own fields, search within.
			if ( rgar( $item, 'fields' ) ) {
				$item = $this->add_field_to_group( $item, $name, $fields, $position );
			}

		}

		return $group;

	}

	/**
	 * Remove a field from existing defined fields.
	 *
	 * @since 2.5
	 *
	 * @param string      $name   Name of field to remove.
	 * @param array|false $groups Array of sections/fields.
	 *
	 * @return array
	 */
	public function remove_field( $name, $groups = false ) {

		// Get fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// Loop through groups, add field.
		foreach ( $groups as &$group ) {
			$group = $this->remove_field_from_group( $group, $name );
		}

		// Update fields.
		$this->fields = $groups;

		return $this->fields;

	}

	/**
	 * Remove field from a group of fields.
	 *
	 * @since 2.5
	 *
	 * @param array|Fields\Base $group    Group of sections/fields.
	 * @param string            $name     Name of field to remove.
	 *
	 * @return array|Fields\Base
	 */
	private function remove_field_from_group( &$group, $name ) {

		// Get nested key.
		$nested_key = GFCommon::get_nested_key( $group );

		// If nested key does not exist or is empty, return.
		if ( ! isset( $group[ $nested_key ] ) || empty( $group[ $nested_key ] ) ) {
			return $group;
		}

		foreach ( $group[ $nested_key ] as $i => &$item ) {

			// If item name matches, add field(s).
			if ( rgar( $item, 'name' ) === $name ) {
				array_splice( $group[ $nested_key ], $i, 1 );
				return $group;
			}

			// If field has its own fields, search within.
			if ( rgar( $item, 'fields' ) ) {
				$item = $this->remove_field_from_group( $item, $name );
			}

		}

		return $group;

	}

	/**
	 * Replace an existing defined field.
	 *
	 * @since 2.5
	 *
	 * @param string              $name   Name of field to replace.
	 * @param array|Fields\Base[] $fields Field(s) to replace.
	 * @param array|false         $groups Array of sections/fields.
	 *
	 * @return array
	 */
	public function replace_field( $name, $fields, $groups = false ) {

		// If only one Field object is provided, push to array.
		if ( rgar( $fields, 'name' ) ) {
			$fields = array( $fields );
		}

		// Initialize fields.
		$fields = $this->initialize_fields( $fields );

		// Get fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// Loop through groups, add field.
		foreach ( $groups as &$group ) {
			$group = $this->replace_field_in_group( $group, $name, $fields );
		}

		// Update fields.
		$this->fields = $groups;

		return $this->fields;

	}

	/**
	 * Replace field in a group of fields.
	 *
	 * @since 2.5
	 *
	 * @param array|Fields\Base $group  Group of sections/fields.
	 * @param string            $name   Name of field to replace.
	 * @param Fields\Base[]     $fields Field(s) to replace.
	 *
	 * @return array|Fields\Base
	 */
	private function replace_field_in_group( &$group, $name, $fields ) {

		// Get nested key.
		$nested_key = GFCommon::get_nested_key( $group );

		// If nested key does not exist or is empty, return.
		if ( ! isset( $group[ $nested_key ] ) || empty( $group[ $nested_key ] ) ) {
			return $group;
		}

		foreach ( $group[ $nested_key ] as $i => &$item ) {

			// If item name matches, replace field(s).
			if ( rgar( $item, 'name' ) === $name ) {
				array_splice( $group[ $nested_key ], $i, 1, $fields );
				return $group;
			}

			// If field has its own fields, search within.
			if ( rgar( $item, 'fields' ) ) {
				$item = $this->replace_field_in_group( $item, $name, $fields );
			}

		}

		return $group;

	}

	/**
	 * Get a specific settings field.
	 *
	 * @since 2.5
	 *
	 * @param string     $name   Name of field to retrieve.
	 * @param array|bool $groups Array of tabs or sections to search through. Defaults to defined fields.
	 *
	 * @return Fields\Base|bool
	 */
	public function get_field( $name, $groups = false ) {

		// If fields were not provided, use defined fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// Loop through groups, look for field.
		foreach ( $groups as $group ) {

			// Determine nested key.
			$nested_key = GFCommon::get_nested_key( $group );

			foreach ( rgar( $group, $nested_key ) as $field ) {

				// If field is a match, return.
				if ( rgar( $field, 'name' ) === $name ) {
					return $field;
				}

				// If field has nested fields, search within.
				if ( rgar( $field, 'fields' ) ) {
					$found = $this->get_field( $name, array( $field ) );
					if ( $found ) {
						return $found;
					}
				}

				// If field has nested inputs, search within.
				if ( rgar( $field, 'inputs' ) ) {
					$found = $this->get_field( $name, array( $field ) );

					if ( $found ) {
						return $found;
					}
				}
			}
		}

		return false;

	}

	/**
	 * Returns fields of a specific field type.
	 *
	 * @since 2.5
	 *
	 * @param string     $type   Field type to search for.
	 * @param array|bool $groups Array of tabs or sections to search through. Defaults to defined fields.
	 *
	 * @return array
	 */
	public function get_fields_by_type( $type, $groups = false ) {

		// Initialize return array.
		$fields_for_type = array();

		// Get fields.
		if ( ! $groups ) {
			$groups = $this->get_fields();
		}

		// If no fields are defined, return.
		if ( empty( $groups ) ) {
			return $fields_for_type;
		}

		// Loop through groups, check for field type.
		foreach ( $groups as $group ) {

			// Determine nested key.
			$nested_key = GFCommon::get_nested_key( $group );

			foreach ( rgar( $group, $nested_key, array() ) as $field ) {

				// If field type matches, add to return array.
				if ( is_object( $field ) && $field->type === $type ) {
					$fields_for_type[] = $field;
				}

				// If field has its own fields, search within.
				if ( rgar( $field, 'fields' ) ) {
					$fields_for_type = array_merge( $fields_for_type, $this->get_fields_by_type( $type, $field['fields'] ) );
				}

			}
		}

		return $fields_for_type;

	}

	/**
	 * Get registered settings fields.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_fields() {

		return $this->fields;

	}

	/**
	 * Define fields to be rendered.
	 *
	 * @since 2.5
	 *
	 * @param array $fields Array of settings fields.
	 */
	public function set_fields( $fields = array() ) {

		// If fields are tabbed, set flag.
		$this->is_tabbed = false;
		foreach ( $fields as $group ) {
			if ( array_key_exists( 'sections', $group ) ) {
				$this->is_tabbed = true;
			}
		}

		// Loop through tabs or sections, initialize fields.
		if ( $this->is_tabbed ) {

			foreach ( $fields as $i => &$tab ) {

				// Set tab ID.
				if ( ! rgar( $tab, 'id' ) ) {
					$tab['id'] = rgar( $tab, 'name' ) ? $tab['name'] : sprintf( 'tab_%02d', $i );
				}

				// Initialize fields.
				foreach ( $tab['sections'] as $s => &$section ) {
					$section['fields'] = $this->initialize_fields( $section['fields'] );
					if ( empty( $section['fields'] ) ) {
						unset( $fields[ $i ]['sections'][ $s ] );
					}
				}

			}

		} else {

			foreach ( $fields as $s => &$section ) {
				$section['fields'] = $this->initialize_fields( $section['fields'] );
				if ( empty( $section['fields'] ) ) {
					unset( $fields[ $s ] );
				}
			}

		}

		$this->fields = $fields;

		// Set save button.
		if ( ! $this->save_button ) {
			$this->add_save_button();
		}

	}

	/**
	 * Initialize fields as field objects.
	 *
	 * @since 2.5
	 *
	 * @param array $fields Array of settings fields.
	 *
	 * @return array
	 */
	private function initialize_fields( $fields = array() ) {

		foreach ( $fields as $f => &$field ) {

			// Handle save button separately.
			if ( $field['type'] === 'save' ) {
				$this->add_save_button( $field );
				unset( $fields[ $f ] );
			}

			// If field is already initialized, skip.
			if ( is_object( $field ) ) {
				continue;
			}

			// Initialize field object.
			$f = Fields::create( $field, $this );

			// If field was created, save.
			if ( ! is_wp_error( $f ) ) {
				$field = $f;
			}

			// If field has fields, initialize them.
			if ( rgobj( $field, 'fields' ) ) {
				$field->fields = $this->initialize_fields( $field->fields );
			}

		}

		return $fields;

	}

	/**
	 * Returns the input name prefix.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_input_name_prefix() {

		return $this->input_name_prefix;

	}

	/**
	 * Set the input name prefix.
	 *
	 * @since 2.5
	 *
	 * @param string $input_name_prefix Input name prefix.
	 */
	public function set_input_name_prefix( $input_name_prefix = '_gform_setting' ) {

		$this->input_name_prefix = $input_name_prefix;

	}

	/**
	 * Returns the current field values.
	 *   If this is a postback request, returns posted values.
	 *   Otherwise, returns saved values passed in to constructor.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_current_values() {

		// Get posted values.
		$values = $this->get_posted_values();

		// If no values have been posted, get saved values.
		if ( empty( $values ) ) {
			$values = $this->_saved_values;
		}

		return $values;

	}

	/**
	 * Get previous field values.
	 *
	 * @since 2.5
	 *
	 * @return bool|array
	 */
	public function get_previous_values() {

		return $this->_previous_values;

	}

	/**
	 * Returns posted field values.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_posted_values() {

		global $_gf_settings_posted_values;

		// If posted values have already been retrieved, return.
		if ( isset( $_gf_settings_posted_values ) ) {
			return $_gf_settings_posted_values;
		}

		// Initialize posted values array.
		$_gf_settings_posted_values = array();

		// If no values have been posted, return.
		if ( count( $_POST ) <= 0 ) {
			return $_gf_settings_posted_values;
		}

		// Strip input name prefix from keys.
		foreach ( $_POST as $key => $value ) {
			if ( preg_match( '|' . $this->input_name_prefix . '_(.*)|', $key, $matches ) ) {
				$_gf_settings_posted_values[ $matches[1] ] = GFCommon::maybe_decode_json( stripslashes_deep( $value ) );
				if ( is_string( $_gf_settings_posted_values[ $matches[1] ] ) ) {
					$_gf_settings_posted_values[ $matches[1] ] = trim( $_gf_settings_posted_values[ $matches[1] ] );
				}
			}
		}

		return $_gf_settings_posted_values;

	}

	/**
	 * Get current value for field.
	 *   Use default value if not found.
	 *
	 * @since 2.5
	 *
	 * @param string     $name          Field name.
	 * @param string     $default_value Default value.
	 * @param array|bool $values        Current field values.
	 *
	 * @return bool|array|string
	 */
	public function get_value( $name, $default_value = '', $values = false ) {

		if ( empty( $name ) ) {
			return '';
		}

		// Get current values.
		if ( ! $values || ! is_array( $values ) ) {
			$values = $this->get_current_values();
		}

		// If no default value was provided, get default value for field.
		if ( ! $default_value ) {

			// Get field.
			$field = $this->get_field( $name );

			// If field was found, get default value.
			if ( $field && rgobj( $field, 'default_value' ) ) {
				$default_value = $field['default_value'];
			}
		}

		// If no values are defined, return default value.
		if ( false === $values ) {
			return $default_value;
		}

		// If field name contains a bracket, get value from array.
		if ( false !== strpos( $name, '[' ) ) {

			// Explode field name.
			$name_parts = explode( '[', $name );

			// Loop through field name parts, look for value.
			foreach ( $name_parts as $part ) {

				// Get current part.
				$part = trim( $part, ']' );

				if ( '0' != $part ) {
					if ( empty( $part ) ) {
						return $values;
					}
				}

				if ( false === isset( $values[ $part ] ) ) {
					return $default_value;
				}

				$values = rgar( $values, $part );

			}

			$value = $values;

		} else {

			// If field is not found in values array, return default value.
			if ( false === isset( $values[ $name ] ) ) {
				return $default_value;
			}

			$value = $values[ $name ];

		}

		return $value;

	}

	/**
	 * Save current field values.
	 *
	 * @since 2.5
	 *
	 * @param array|string $values Field values to be saved.
	 */
	public function set_values( $values = array() ) {

		// If values is a string and serialized, unserialize.
		if ( is_string( $values ) && is_serialized( $values ) ) {
			$values = unserialize( $values );
		} else if ( is_string( $values ) && ! is_serialized( $values ) ) {
			$values = get_option( $values, array() );
		}

		$this->_saved_values = $values;

	}

	/**
	 * Save previous field values.
	 *
	 * @since 2.5
	 *
	 * @param array $values Field values to be saved.
	 */
	public function set_previous_values( $values = array() ) {

		$this->_previous_values = $values;

	}

	/**
	 * Get the save callback.
	 *
	 * @since 2.5
	 *
	 * @return string|callable
	 */
	public function get_save_callback() {

		return $this->_save_callback;

	}

	/**
	 * Set the save callback.
	 *
	 * @since 2.5
	 * @deprecated 2.6.1
	 *
	 * @param string|callable $callback Option name or callable function values will be saved to.
	 */
	public function set_save_callback( $callback = '' ) {
		_deprecated_function( 'set_save_callback', '2.6', 'set_save_setting_callback' );

		$this->set_save_setting_callback( $callback );

	}

	/**
	 * Set the save callback.
	 *
	 * @since 2.6.1
	 *
	 * @param string|callable $callback Option name or callable function values will be saved to.
	 */
	protected function set_save_setting_callback( $callback = '' ) {

		$this->_save_callback = $callback;

	}

	/**
	 * Set the postback message callback.
	 *
	 * @since 2.5
	 *
	 * @param callable $callback Callable function to use when displaying success message.
	 *
	 * @return bool|WP_Error
	 */
	public function set_postback_message_callback( $callback ) {

		// If callback is not callable, return.
		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'not_callable', 'Provided callback is not callable.' );
		}

		$this->postback_message_callback = $callback;

		return true;

	}





	// # MISC HELPER METHODS -------------------------------------------------------------------------------------------

	/**
	 * Determines if Save button was pressed.
	 *
	 * @since 2.5
	 *
	 * @return bool
	 */
	public static function is_save_postback() {

		return ! rgempty( 'gform-settings-save' );

	}

}
