<?php

use Gravity_Forms\Gravity_Forms\Settings\Settings;

class_exists( 'GFForms' ) || die();

/**
 * Handles Integration with the WordPress personal data export and erase tools.
 *
 * @since 2.4
 *
 * Class GF_Personal_Data
 */
class GF_Personal_Data {

	/**
	 * The cached form array.
	 *
	 * @since 2.4
	 *
	 * @var array $form
	 */
	private static $_form;

	/**
	 * The cached array of forms.
	 *
	 * @since 2.4
	 *
	 * @var array $forms
	 */
	private static $_forms;

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Renders the form settings.
	 *
	 * @since 2.4
	 *
	 * @param $form_id
	 */
	public static function form_settings( $form_id ) {

		if ( ! self::get_settings_renderer() ) {
			self::initialize_settings_renderer();
		}

		self::get_settings_renderer()->render();

	}

	/**
	 * Get Personal Data settings fields.
	 *
	 * @since 2.5
	 *
	 * @param int $form_id The current Form ID.
	 *
	 * @return array
	 */
	private static function settings_fields( $form_id ) {

		// Get form object.
		$form = self::get_form( $form_id );

		// Get Identification Field choices.
		$identification_field_choices = self::get_identification_fields_choices( $form );

		return array(
			array(
				'class'  => 'gform-settings-panel--full',
				'title'  => esc_html__( 'General Settings', 'gravityforms' ),
				'fields' => array(
					array(
						'name'    => 'preventIP',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Prevent the storage of IP addresses during form submission', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'personal_data_prevent_ip', null, true ),
					),
					array(
						'name'          => 'retention[policy]',
						'type'          => 'radio',
						'label'         => esc_html__( 'Retention Policy', 'gravityforms' ),
						'tooltip'       => gform_tooltip( 'personal_data_retention_policy', null, true ),
						'default_value' => 'retain',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Retain entries indefinitely', 'gravityforms' ),
								'value' => 'retain',
							),
							array(
								'label'   => esc_html__( 'Trash entries automatically', 'gravityforms' ),
								'value'   => 'trash',
								'onclick' => sprintf(
									'alert( %s );',
									json_encode( __( 'Warning: this will affect all entries that are older than the number of days specified.', 'gravityforms' ) )
								),
							),
							array(
								'label'   => esc_html__( 'Delete entries permanently automatically', 'gravityforms' ),
								'value'   => 'delete',
								'onclick' => sprintf(
									'alert( %s );',
									json_encode( __( 'Warning: this will affect all entries that are older than the number of days specified.', 'gravityforms' ) )
								),
							),
						),
					),
					array(
						'name'                => 'retention[retain_entries_days]',
						'label'               => esc_html__( 'Number of days to retain entries before trashing/deleting:', 'gravityforms' ),
						'type'                => 'text',
						'input_type'          => 'number',
						'default_value'       => 1,
						'dependency'          => array(
							'live'   => true,
							'fields' => array(
								array(
									'field'  => 'retention[policy]',
									'values' => array( 'trash', 'delete' ),
								),
							),
						),
						'validation_callback' => function( $field, $value ) {

							// If value is not numeric or less than one day, set error.
							if ( ! is_numeric( $value ) || ( is_numeric( $value ) && floatval( $value ) < 1 ) ) {
								$field->set_error( esc_html__( 'Form entries must be retained for at least one day.', 'gravityforms' ) );
							}

						},
					),
				),
			),
			array(
				'class'  => 'gform-settings-panel--full',
				'title'  => esc_html__( 'Exporting and Erasing Data', 'gravityforms' ),
				'fields' => array(
					array(
						'name'        => 'exportingAndErasing[enabled]',
						'type'        => 'toggle',
						'label'       => esc_html__( 'Enable integration with the WordPress tools for exporting and erasing personal data.', 'gravityforms' ),
						'tooltip'     => gform_tooltip( 'personal_data_enable', null, true ),
						'disabled'    => empty( $identification_field_choices ),
						'after_input' => ! empty( $identification_field_choices ) ? '' : sprintf(
							'<div class="notice-error gf-notice alert error">%s</div>',
							esc_html__( 'You must add an email address field to the form in order to enable this setting.', 'gravityforms' )
						),
					),
					array(
						'name'       => 'exportingAndErasing[identificationField]',
						'type'       => 'select',
						'label'      => esc_html__( 'Identification Field', 'gravityforms' ),
						'tooltip'    => gform_tooltip( 'personal_data_identification', null, true ),
						'choices'    => $identification_field_choices,
						'dependency' => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'exportingAndErasing[enabled]',
								),
							),
						),
					),
					array(
						'name'       => 'exportingAndErasing[columns]',
						'type'       => 'columns',
						'label'      => esc_html__( 'Personal Data', 'gravityforms' ),
						'tooltip'    => gform_tooltip( 'personal_data_field_settings', null, true ),
						'callback'   => array( 'GF_Personal_Data', 'settings_columns' ),
						'dependency' => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'exportingAndErasing[enabled]',
								),
							),
						),
					),
				),
			),
		);

	}

	/**
	 * Get identification fields as choices.
	 *
	 * @since 2.5
	 *
	 * @param array $form Form object.
	 *
	 * @return array
	 */
	private static function get_identification_fields_choices( $form = array() ) {

		static $choices;

		// If choices have already been defined, return.
		if ( isset( $choices ) ) {
			return $choices;
		}

		// Initialize choices.
		$choices = array();

		// Get Email fields.
		$email_fields = GFAPI::get_fields_by_type( $form, 'email' );

		// Add Email fields as choices.
		foreach ( $email_fields as $email_field ) {
			$choices[ (string) $email_field->id ] = $email_field->label;
		}

		/**
		 * Allows the list of personal data identification field choices to be modified. Fields values
		 * will be treated as user IDs.
		 *
		 * For example, add the created_by field by returning:
		 * $identification_field_choices['created_by'] = 'Created By';
		 *
		 * @since 2.4
		 *
		 * @param array $identification_field_choices An associative array with the field id as the key and the value as the label.
		 * @param array $form                         The current form.
		 */
		$choices = gf_apply_filters( array(
			'gform_personal_data_identification_fields',
			$form['id'],
		), $choices, $form );

		// Update choices formatting.
		array_walk( $choices, function( &$label, $value ) {
			$label = array( 'label' => $label, 'value' => $value );
		} );

		// If choices exist, return.
		if ( ! empty( $choices ) ) {
			return $choices;
		}

		// Get current identification field value.
		$current_value = rgars( $form, 'personalData/exportingAndErasing/identificationField' );

		// Add look up choice.
		if ( $current_value === 'created_by' ) {

			$choices[] = array(
				'label' => esc_html__( 'Created By', 'gravityforms' ),
				'value' => 'created_by',
			);

		} else if ( $selected_field = GFAPI::get_field( $form, $current_value ) ) {

			// Set admin label context.
			$selected_field->set_context_property( 'use_admin_label', true );

			$choices[] = array(
				'label' => GFFormsModel::get_label( $selected_field ),
				'value' => $current_value,
			);

		}

		return $choices;

	}

	/**
	 * Renders a Personal Data columns table field.
	 *
	 * @since 2.5
	 *
	 * @param array $props Field properties.
	 * @param bool  $echo  Output the field markup directly.
	 *
	 * @return string
	 */
	public static function settings_columns( $props = array(), $echo = true ) {

		// Get form object.
		$form_id = absint( rgget( 'id' ) );
		$form    = self::get_form( $form_id );

		// Open table.
		$html = sprintf(
			'<table id="gf_personal_data_field_settings" class="form-table">
				<thead>
					<tr>
						<th class="gf_personal_data_field_label_title">%s</th>
						<th class="gf_personal_data_cb_title">%s</th>
						<th class="gf_personal_data_cb_title">%s</th>
					</tr>
				</thead>
				<tbody>',
			esc_html__( 'Fields', 'gravityforms' ),
			esc_html__( 'Export', 'gravityforms' ),
			esc_html__( 'Erase', 'gravityforms' )
		);

		// Add Select/Deselect All row.
		$html .= sprintf(
		'<tr>
				<td>%s</td>
				<td class="gf_personal_data_cb_cell">
					<div class="gform-settings-choice">
						<input id="gf_personal_data_export_all" type="checkbox" />
					</div>
				</td>
				<td class="gf_personal_data_cb_cell">
					<div class="gform-settings-choice">
						<input id="gf_personal_data_erase_all" type="checkbox" />
					</div>
				</td>
			</tr>',
			esc_html__( 'Select/Deselect All', 'gravityforms' )
		);

		// Initialize Personal Data fields array.
		$pd_fields = array();

		// Loop through columns, add to Personal Data fields array.
		foreach ( self::get_columns() as $key => $label ) {
			$column_settings = rgars( $form, 'personalData/exportingAndErasing/columns/' . $key );
			$pd_fields[]     = array(
				'key'            => $key,
				'label'          => $label,
				'default_values' => array(
					'export' => rgar( $column_settings, 'export' ),
					'erase'  => rgar( $column_settings, 'erase' ),
				),
			);
		}

		/**
		 * Loop through form fields, add to Personal Data fields array.
		 *
		 * @var GF_Field $field
		 */
		foreach ( $form['fields'] as $field ) {

			// Skip display only fields.
			if ( $field->displayOnly ) {
				continue;
			}

			// Set label context.
			$field->set_context_property( 'use_admin_label', true );

			// Add to Personal Data fields.
			$pd_fields[] = array(
				'key'            => absint( $field->id ),
				'label'          => GFFormsModel::get_label( $field ),
				'default_values' => array(
					'export' => $field->personalDataExport,
					'erase'  => $field->personalDataErase,
				),
			);

		}

		// Render Personal Data fields.
		foreach ( $pd_fields as $pd_field ) {

			$export_field = $erase_field = null;

			// Prepare export checkbox.
			$export_field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
				array(
					'name'    => sprintf( 'export_fields[%s]', esc_attr( $pd_field['key'] ) ),
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'class'         => 'gf_personal_data_cb_export',
							'name'          => sprintf( 'export_fields[%s]', esc_attr( $pd_field['key'] ) ),
							'default_value' => $pd_field['default_values']['export'],
						),
					),
				),
				self::get_settings_renderer()
			);

			// Prepare erase checkbox.
			$erase_field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
				array(
					'name'    => sprintf( 'erase_fields[%s]', esc_attr( $pd_field['key'] ) ),
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'class'         => 'gf_personal_data_cb_erase',
							'name'          => sprintf( 'erase_fields[%s]', esc_attr( $pd_field['key'] ) ),
							'default_value' => $pd_field['default_values']['erase'],
						),
					),
				),
				self::get_settings_renderer()
			);

			// Render field.
			$html .= sprintf(
				'<tr>
					<td>%s</td>
					<td class="gf_personal_data_cb_cell">%s</td>
					<td class="gf_personal_data_cb_cell">%s</td>
				</tr>',
				esc_html( $pd_field['label'] ),
				$export_field->markup(),
				$erase_field->markup()
			);

		}

		// Get custom items.
		$custom_items = self::get_custom_items( $form );

		// Display custom items.
		if ( ! empty( $custom_items ) ) {

			// Add Other Data heading.
			$html .= sprintf(
				'<tr><th class="gf_personal_data_field_label_title" colspan="3">%s</th></tr>',
				esc_html__( 'Other Data', 'gravityforms' )
			);

			// Loop through custom items, render.
			foreach ( $custom_items as $key => $custom_item_details ) {

				$export_field = $erase_field = null;

				// Get custom items settings.
				$custom_settings = rgars( $form, 'personalData/exportingAndErasing/custom/' . $key );

				// Prepare export checkbox.
				if ( isset( $custom_item_details['exporter_callback'] ) && is_callable( $custom_item_details['exporter_callback'] ) ) {
					$export_field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
						array(
							'name'    => sprintf( 'export_fields[%s]', esc_attr( $key ) ),
							'type'    => 'checkbox',
							'choices' => array(
								array(
									'class'         => 'gf_personal_data_cb_export',
									'name'          => sprintf( 'export_fields[%s]', esc_attr( $key ) ),
									'default_value' => rgar( $custom_settings, 'export' ),
								),
							),
						),
						self::get_settings_renderer()
					);
				}

				// Prepare erase checkbox.
				if ( isset( $custom_item_details['eraser_callback'] ) && is_callable( $custom_item_details['eraser_callback'] ) ) {
					$erase_field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
						array(
							'name'    => sprintf( 'erase_fields[%s]', esc_attr( $key ) ),
							'type'    => 'checkbox',
							'choices' => array(
								array(
									'class'         => 'gf_personal_data_cb_erase',
									'name'          => sprintf( 'erase_fields[%s]', esc_attr( $key ) ),
									'default_value' => rgar( $custom_settings, 'erase' ),
								),
							),
						),
						self::get_settings_renderer()
					);
				}

				// Render field.
				$html .= sprintf(
					'<tr>
						<td>%s</td>
						<td class="gf_personal_data_cb_cell">%s</td>
						<td class="gf_personal_data_cb_cell">%s</td>
					</tr>',
					esc_html( rgar( $custom_item_details, 'label' ) ),
					$export_field ? $export_field->markup() : null,
					$erase_field ? $erase_field->markup() : null
				);

			}

		}

		// Close table.
		$html .= '</tbody></table>';

		return $html;

	}

	/**
	 * Saves the form settings.
	 *
	 * @since 2.4
	 *
	 * @param array $values Submitted settings values.
	 */
	public static function process_form_settings( $values ) {

		// Get form object.
		$form = self::get_form( rgget( 'form_id' ) );

		// Prevent IP address storage.
		$form['personalData']['preventIP'] = (bool) rgar( $values, 'preventIP' );

		// Retention Policy
		$form['personalData']['retention'] = rgar( $values, 'retention' );

		// Exporting and Erasing
		$form['personalData']['exportingAndErasing']['enabled']             = (bool) rgars( $values, 'exportingAndErasing/enabled' );
		$form['personalData']['exportingAndErasing']['identificationField'] = sanitize_key( rgars( $values, 'exportingAndErasing/identificationField' ) );

		// Exporting and Erasing: Columns
		foreach ( self::get_columns() as $column => $label ) {
			$form['personalData']['exportingAndErasing']['columns'][ $column ] = array(
				'export' => (bool) rgars( $values, 'export_fields/' . $column ),
				'erase'  => (bool) rgars( $values, 'erase_fields/' . $column ),
			);
		}

		/**
		 * Exporting and Erasing: Fields
		 *
		 * @var GF_Field $field
		 */
		foreach ( $form['fields'] as $f => $field ) {
			$form['fields'][ $f ]->personalDataExport = (bool) rgars( $values, 'export_fields/' . absint( $field->id ) );
			$form['fields'][ $f ]->personalDataErase  = (bool) rgars( $values, 'erase_fields/' . absint( $field->id ) );
		}

		// Exporting and Erasing: Custom Items
		$custom_items = self::get_custom_items( $form );
		if ( ! empty( $custom_items ) ) {
			foreach ( $custom_items as $custom_item => $custom_item_meta ) {
				$form['personalData']['exportingAndErasing']['custom'][ $custom_item ] = array(
					'export' => (bool) rgars( $values, 'export_fields/' . $custom_item ),
					'erase'  => (bool) rgars( $values, 'erase_fields/' . $custom_item ),
				);
			}
		}

		// Save form.
		GFAPI::update_form( $form );

		// Update cached form object.
		self::$_form = $form;

	}





	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Initializes the Settings renderer at the beginning of page load.
	 */
	public static function initialize_settings_renderer() {

		// Get form object.
		$form_id = absint( rgget( 'id' ) );
		$form    = self::get_form( $form_id );

		$renderer = new Settings(
			array(
				'header'         => array(
					'icon'  => 'fa fa-lock',
					'title' => esc_html__( 'Personal Data', 'gravityforms' ),
				),
				'fields'         => self::settings_fields( $form_id ),
				'initial_values' => rgar( $form, 'personalData' ),
				'save_callback'  => array( 'GF_Personal_Data', 'process_form_settings' ),
				'after_fields'   => function() {
					?>
					<script>
						jQuery( document ).ready( function ( $ ) {
							$( '#gf_personal_data_export_all' ).change( function () {
								var checked = $( this ).is( ':checked' );
								$( '.gf_personal_data_cb_export' ).each( function() {
									if ( ( ! $( this ).is( ':checked' ) && checked ) || ( $( this ).is( ':checked' ) && ! checked ) ) {
										$( this ).trigger( 'click' );
									}
								} );
							} );
							$( '#gf_personal_data_erase_all' ).change( function () {
								var checked = $( this ).is( ':checked' );
								$( '.gf_personal_data_cb_erase' ).each( function() {
									if ( ( ! $( this ).is( ':checked' ) && checked ) || ( $( this ).is( ':checked' ) && ! checked ) ) {
										$( this ).trigger( 'click' );
									}
								} );
							} );
						} );
					</script>
					<?php
				}
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}

	}

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|\Gravity_Forms\Gravity_Forms\Settings
	 */
	private static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}





	/**
	 * Returns the form array for use in the form settings.
	 *
	 * @since 2.4
	 *
	 * @param int $form_id
	 *
	 * @return array|mixed
	 */
	public static function get_form( $form_id ) {
		if ( empty( self::$_form ) ) {
			self::$_form = GFAPI::get_form( $form_id );
		}

		return self::$_form;
	}

	/**
	 * Returns an assoiative array of the database columns that may contain personal data.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public static function get_columns() {
		$columns = array(
			'ip'         => esc_html__( 'IP Address', 'gravityforms' ),
			'source_url' => esc_html__( 'Embed URL', 'gravityforms' ),
			'user_agent' => esc_html__( 'Browser details', 'gravityforms' ),
		);

		return $columns;
	}

	/**
	 * Returns an array with the custom personal data items configurations.
	 *
	 * @since 2.4
	 *
	 * @param array $form
	 *
	 * @return array
	 */
	public static function get_custom_items( $form ) {

		$custom_items = array();

		/**
		 * Allows custom exporter and erasers to be registered.
		 *
		 * Example:
		 *
		 * add_filter( 'gform_personal_data', 'filter_gform_personal_data', 10, 2 );
		 * function filter_gform_personal_data( $items, $form ) {
		 *       $items['test'] = array(
		 *          'label'             => 'A custom item',
		 *          'exporter_callback' => 'gf_custom_data_exporter',
		 *          'eraser_callback'   => 'gf_custom_data_eraser',
		 *      );
		 *
		 *      return $items;
		 * }
		 *
		 * function gf_custom_data_exporter( $form, $entry ) {
		 *       $data = array(
		 *        'name'  => 'My Custom Value',
		 *          'value' => 'ABC123',
		 *      );
		 *      return $data;
		 * }
		 *
		 * function gf_custom_data_eraser( $form, $entry ) {
		 *      // Delete or anonymize some data
		 * }
		 *
		 * @since 2.4
		 *
		 * @param array $custom_items
		 * @param array $form
		 */
		$custom_items = apply_filters( 'gform_personal_data', $custom_items, $form );

		return $custom_items;
	}

	/**
	 * Returns an associative array of all the form metas with the form ID as the key.
	 *
	 * @since 2.4
	 *
	 * @return array|null
	 */
	public static function get_forms() {

		if ( is_null( self::$_forms ) ) {
			$form_ids = GFFormsModel::get_form_ids( null );

			if ( empty( $form_ids ) ) {
				return array(
					'data' => array(),
					'done' => true,
				);
			}

			$forms_by_id = GFFormsModel::get_form_meta_by_id( $form_ids );

			self::$_forms = array();
			foreach ( $forms_by_id as $form ) {
				self::$_forms[ $form['id'] ] = $form;
			}
		}

		return self::$_forms;
	}

	/**
	 * Returns all the entries across all forms for the specified email address.
	 *
	 * @since 2.4
	 *
	 * @param string    $email_address
	 * @param int $page
	 * @param int $limit
	 *
	 * @return array
	 */
	public static function get_entries( $email_address, $page = 1, $limit = 50 ) {

		$user = get_user_by( 'email', $email_address );

		$forms = self::get_forms();

		$form_ids = array();

		$query = new GF_Query();

		$conditions = array();

		foreach ( $forms as $form ) {

			if ( ! rgars( $form, 'personalData/exportingAndErasing/enabled' ) ) {
				continue;
			}

			$form_ids[] = $form['id'];

			$identification_field = rgars( $form, 'personalData/exportingAndErasing/identificationField' );

			$field = GFAPI::get_field( $form, $identification_field );

			if ( $field && $field->get_input_type() == 'email' ) {

				$conditions[] = new GF_Query_Condition(
					new GF_Query_Column( $identification_field, intval( $form['id'] ) ),
					GF_Query_Condition::EQ,
					new GF_Query_Literal( $email_address )
				);

			} else {

				if ( ! $field && $identification_field != 'created_by' ) {
					continue;
				}

				if ( ! $user ) {
					continue;
				}

				$conditions[] = new GF_Query_Condition(
					new GF_Query_Column( $identification_field, intval( $form['id'] ) ),
					GF_Query_Condition::EQ,
					new GF_Query_Literal( $user->ID )
				);
			}
		}

		if ( empty( $conditions ) ) {
			return array();
		}

		$all_conditions = call_user_func_array( array( 'GF_Query_Condition', '_or' ), $conditions );

		$entries = $query->from( $form_ids )->where( $all_conditions )->limit( $limit )->page( $page )->get();

		return $entries;
	}

	/**
	 * Exports personal data specified in the form settings.
	 *
	 * @since 2.4
	 *
	 * @param string    $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public static function data_exporter( $email_address, $page = 1 ) {

		$export_items = array(
			'done' => true,
		);

		$export_data = array();

		if ( $page == 1 ) {
			$export_data = self::get_draft_submissions_export_items( $email_address );
		}

		$export_items['data'] = $export_data;

		$limit = 50;

		$columns = self::get_columns();

		$forms = self::get_forms();

		$entries = self::get_entries( $email_address, $page, $limit );

		if ( empty( $entries ) ) {
			return $export_items;
		}

		foreach ( $entries as $entry ) {

			$data = array();

			$form_id = $entry['form_id'];

			$form = $forms[ $form_id ];

			$item_id = "gf-entry-{$entry['id']}";

			$group_id = 'gravityforms-entries';

			$group_label = __( 'Forms', 'gravityforms' );

			$columns_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/columns' );

			if ( is_array( $columns_settings ) ) {
				foreach ( $columns_settings as $column_key => $column_settings ) {
					if ( rgar( $column_settings, 'export' ) ) {
						$data[] = array(
							'name'  => $columns[ $column_key ],
							'value' => $entry[ $column_key ],
						);
					}
				}
			}

			foreach ( $form['fields'] as $field ) {
				/* @var GF_Field $field */
				if ( $field->personalDataExport ) {
					$value  = GFFormsModel::get_lead_field_value( $entry, $field );
					$data[] = array(
						'name'  => $field->get_field_label( false, $value ),
						'value' => $field->get_value_entry_detail( $value, rgar( $entry, 'currency' ), true, 'text' ),
					);
				}
			}

			$custom_items = self::get_custom_items( $form );

			if ( ! empty( $custom_items ) ) {
				$all_custom_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/custom' );
				foreach ( $custom_items as $custom_item_key => $custom_item_details ) {
					$custom_settings = rgar( $all_custom_settings, $custom_item_key );
					if ( rgars( $custom_settings, 'export' ) && isset( $custom_item_details['exporter_callback'] ) && is_callable( $custom_item_details['exporter_callback'] ) ) {
						$data[] = call_user_func( $custom_item_details['exporter_callback'], $form, $entry );
					}
				}
			}

			if ( ! empty( $data ) ) {
				$export_data[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);
			}
		}

		$done = count( $entries ) < $limit;

		$export_items = array(
			'data' => $export_data,
			'done' => $done,
		);

		return $export_items;
	}

	/**
	 * Returns the export items for draft submissions.
	 *
	 * @since 2.4
	 *
	 * @param $email_address
	 *
	 * @return array
	 */
	public static function get_draft_submissions_export_items( $email_address ) {
		$export_items = array();

		$forms = self::get_forms();

		$columns = self::get_columns();

		$draft_submissions = self::get_draft_submissions( $email_address );

		foreach ( $draft_submissions as $i => $draft_submission ) {
			$data = array();

			$form_id = $draft_submission['form_id'];

			$form = $forms[ $form_id ];

			$submission_json = $draft_submission['submission'];

			$submission = json_decode( $submission_json, true );

			$entry = $submission['partial_entry'];

			$item_id = "gf-draft-submission-{$i}";

			$group_id = 'gravityforms-draft-submissions';

			$group_label = __( 'Draft Forms (Save and Continue Later)', 'gravityforms' );

			$columns_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/columns' );

			if ( is_array( $columns_settings ) ) {
				foreach ( $columns_settings as $column_key => $column_settings ) {
					if ( rgar( $column_settings, 'export' ) && isset( $draft_submission[ $column_key ] ) ) {
						$data[] = array(
							'name'  => $columns[ $column_key ],
							'value' => $draft_submission[ $column_key ],
						);
					}
				}
			}

			foreach ( $form['fields'] as $field ) {
				/* @var GF_Field $field */
				if ( $field->personalDataExport ) {
					$value  = GFFormsModel::get_lead_field_value( $entry, $field );
					$data[] = array(
						'name'  => $field->get_field_label( false, $value ),
						'value' => $field->get_value_entry_detail( $value, rgar( $entry, 'currency' ), true, 'text' ),
					);
				}
			}

			if ( ! empty( $data ) ) {
				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);
			}
		}

		return $export_items;
	}

	/**
	 * Erases personal data specified in the form settings.
	 *
	 * @since 2.4
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public static function data_eraser( $email_address, $page = 1 ) {

		$limit = 50;

		$items_removed = $page == 1 ? self::erase_draft_submissions_data( $email_address ) : false;

		$forms = self::get_forms();

		$entries = self::get_entries( $email_address, $page, $limit );

		foreach ( $entries as $entry ) {

			$form_id = $entry['form_id'];

			$form = $forms[ $form_id ];

			$columns_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/columns' );

			if ( is_array( $columns_settings ) ) {
				foreach ( $columns_settings as $column_key => $column_settings ) {
					if ( rgar( $column_settings, 'erase' ) ) {
						GFAPI::update_entry_property( $entry['id'], $column_key, '' );
						$items_removed = true;
					}
				}
			}

			$has_product_field = false;

			foreach ( $form['fields'] as $field ) {
				/* @var GF_Field $field */

				if ( $field->personalDataErase ) {

					$input_type = $field->get_input_type();

					if ( $input_type == 'fileupload' ) {
						GFFormsModel::delete_files( $entry['id'] );
						GFAPI::update_entry_field( $entry['id'], $field->id, '' );
						continue;
					}

					if ( $field->type == 'product' ) {
						$has_product_field = true;
					}

					$value = GFFormsModel::get_lead_field_value( $entry, $field );

					if ( is_array( $value ) ) {
						self::erase_field_values( $value, $entry['id'], $field->id );
						$items_removed = true;
					} else {
						switch ( $input_type ) {
							case 'email':
								$anonymous = 'deleted@site.invalid';
								break;
							case 'website':
								$anonymous = 'https://site.invalid';
								break;
							case 'date':
								$anonymous = '0000-00-00';
								break;
							case 'text':
							case 'textarea':
								/* translators: deleted text */
								$anonymous = __( '[deleted]' );
								break;
							default:
								$anonymous = '';
						}
						GFAPI::update_entry_field( $entry['id'], $field->id, $anonymous );
						$items_removed = true;
					}
				}
			}

			if ( $has_product_field ) {
				GFFormsModel::refresh_product_cache( $form, $entry );
			}

			$custom_items = self::get_custom_items( $form );

			if ( ! empty( $custom_items ) ) {
				$all_custom_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/custom' );
				foreach ( $custom_items as $custom_item_key => $custom_item_details ) {
					$custom_settings = rgar( $all_custom_settings, $custom_item_key );
					if ( rgars( $custom_settings, 'erase' ) && isset( $custom_item_details['eraser_callback'] ) && is_callable( $custom_item_details['eraser_callback'] ) ) {
						call_user_func( $custom_item_details['eraser_callback'], $form, $entry );
						$items_removed = true;
					}
				}
			}
		}

		$done = count( $entries ) < $limit;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $done,
		);
	}

	public static function erase_field_values( $value, $entry_id, $input_id, $item_index = '' ) {
		if ( is_array( $value ) ) {
			$i = 0;
			foreach ( $value as $key => $val ) {
				if ( is_array( $val ) ) {
					foreach ( $val as $k => $v ) {
						$new_index = $item_index . '_' . $i;
						self::erase_field_values( $v, $entry_id, $k, $new_index );
					}
					$i++;
				} else {
					GFAPI::update_entry_field( $entry_id, $key, '', $item_index );
				}
			}
		} else {
			GFAPI::update_entry_field( $entry_id, $input_id, '', $item_index );
		}

	}

	/**
	 * Returns the draft submissions (save and continue) for the given email address.
	 *
	 * @since 2.4
	 *
	 * @param $email_address
	 *
	 * @return array
	 */
	public static function get_draft_submissions( $email_address ) {

		$draft_submissions = GFFormsModel::get_draft_submissions();

		if ( empty( $draft_submissions ) ) {
			return array();
		}

		$user = get_user_by( 'email', $email_address );

		$return = array();

		$forms = self::get_forms();

		foreach ( $draft_submissions as $i => $draft_submission ) {

			$form_id = $draft_submission['form_id'];

			$form = $forms[ $form_id ];

			if ( ! rgars( $form, 'personalData/exportingAndErasing/enabled' ) ) {
				continue;
			}

			$submission_json = $draft_submission['submission'];

			$submission = json_decode( $submission_json, true );

			$entry = $submission['partial_entry'];

			$identification_field = rgars( $form, 'personalData/exportingAndErasing/identificationField' );

			$field = GFAPI::get_field( $form, $identification_field );

			if ( ( $field && $field->get_input_type() == 'email' && $entry[ (string) $identification_field ] === $email_address )
			     || ( $user && $user->ID == rgar( $entry, $identification_field ) )
			) {
				$return[] = $draft_submission;
			}
		}

		return $return;
	}

	/**
	 * Erases the data in the draft submissions.
	 *
	 * @since 2.4
	 *
	 * @param $email_address
	 *
	 * @return bool
	 */
	public static function erase_draft_submissions_data( $email_address ) {
		$items_removed = false;

		$forms = self::get_forms();

		$draft_entries = self::get_draft_submissions( $email_address );

		foreach ( $draft_entries as $draft_entry ) {

			$entry_dirty = false;

			$form_id = $draft_entry['form_id'];

			$resume_token = $draft_entry['uuid'];

			$date_created = $draft_entry['date_created'];

			$form = $forms[ $form_id ];

			$columns_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/columns' );

			$submission_json = $draft_entry['submission'];

			$submission = json_decode( $submission_json, true );

			$entry = $submission['partial_entry'];

			$submitted_values = $submission['submitted_values'];

			if ( is_array( $columns_settings ) ) {
				foreach ( $columns_settings as $column_key => $column_settings ) {
					if ( rgar( $column_settings, 'erase' ) ) {
						if ( isset( $draft_entry[ $column_key ] ) ) {
							$draft_entry[ $column_key ] = '';
						}

						if ( isset( $entry[ $column_key ] ) ) {
							$entry[ $column_key ] = '';
						}

						$entry_dirty = true;
					}
				}
			}

			foreach ( $form['fields'] as $field ) {
				/* @var GF_Field $field */

				if ( $field->personalDataErase ) {

					$input_type = $field->get_input_type();

					$value = GFFormsModel::get_lead_field_value( $entry, $field );

					if ( is_array( $value ) ) {
						foreach ( $value as $k => $v ) {
							$entry[ $k ] = '';
							$submitted_values[ $field->id ][ $k ] = '';
						}
						$entry_dirty = true;
					} else {
						switch ( $input_type ) {
							case 'email':
								$anonymous = 'deleted@site.invalid';
								break;
							case 'website':
								$anonymous = 'https://site.invalid';
								break;
							case 'date':
								$anonymous = '0000-00-00';
								break;
							case 'text':
							case 'textarea':
								/* translators: deleted text */
								$anonymous = __( '[deleted]', 'gravityforms' );
								break;
							default:
								$anonymous = '';
						}
						$submitted_values[ (string) $field->id ] = $anonymous;
						$entry[ (string) $field->id ] = $anonymous;
						$entry_dirty = true;
					}
				}
			}

			$custom_items = self::get_custom_items( $form );

			if ( ! empty( $custom_items ) ) {
				$all_custom_settings = rgars( $forms, $form_id . '/personalData/exportingAndErasing/custom' );
				foreach ( $custom_items as $custom_item_key => $custom_item_details ) {
					$custom_settings = rgar( $all_custom_settings, $custom_item_key );
					if ( rgars( $custom_settings, 'erase' ) && isset( $custom_item_details['eraser_callback'] ) && is_callable( $custom_item_details['eraser_callback'] ) ) {
						call_user_func( $custom_item_details['eraser_callback'], $form, $entry );
						$items_removed = true;
					}
				}
			}

			if ( $entry_dirty ) {
				$submission['submitted_values'] = $submitted_values;
				$submission['partial_entry'] = $entry;
				$submission_json                = json_encode( $submission );
				GFFormsModel::update_draft_submission( $resume_token, $form, $date_created, $draft_entry['ip'], $draft_entry['source_url'], $submission_json );
				$items_removed = true;
			}
		}


		return $items_removed;
	}

	/**
	 * Deletes and trashes entries according to the retention policy in each of the form settings.
	 *
	 * @since 2.4
	 */
	public static function cron_task() {

		self::log_debug( __METHOD__ . '(): starting personal data cron task' );

		$forms = self::get_forms();

		$trash_form_ids   = array();
		$trash_conditions = array();

		$delete_form_ids   = array();
		$delete_conditions = array();

		foreach ( $forms as $form ) {

			$retention_policy = rgars( $form, 'personalData/retention/policy', 'retain' );

			if ( $retention_policy == 'retain' ) {
				continue;
			}

			$form_conditions = array();

			$retention_days = rgars( $form, 'personalData/retention/retain_entries_days' );

			$delete_timestamp = time() - ( DAY_IN_SECONDS * $retention_days );

			$delete_date = date( 'Y-m-d H:i:s', $delete_timestamp );

			$form_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'date_created' ),
				GF_Query_Condition::LT,
				new GF_Query_Literal( $delete_date )
			);

			$form_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'form_id' ),
				GF_Query_Condition::EQ,
				new GF_Query_Literal( $form['id'] )
			);

			if ( ! empty( $form_conditions ) ) {
				if ( $retention_policy == 'trash' ) {
					$trash_form_ids[] = $form['id'];
					$trash_conditions[] = call_user_func_array( array(
						'GF_Query_Condition',
						'_and',
					), $form_conditions );
				} elseif ( $retention_policy == 'delete' ) {
					$delete_form_ids[] = $form['id'];
					$delete_conditions[] = call_user_func_array( array(
						'GF_Query_Condition',
						'_and',
					), $form_conditions );
				}
			}
		}

		if ( ! empty( $trash_conditions ) ) {

			$query = new GF_Query();

			$all_trash_conditions = array();

			$all_trash_conditions[] = call_user_func_array( array( 'GF_Query_Condition', '_or' ), $trash_conditions );

			$all_trash_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'status' ),
				GF_Query_Condition::NEQ,
				new GF_Query_Literal( 'trash' )
			);

			$all_trash_conditions = call_user_func_array( array( 'GF_Query_Condition', '_and' ), $all_trash_conditions );

			$entry_ids = $query->from( $trash_form_ids )->where( $all_trash_conditions )->get_ids();

			self::log_debug( __METHOD__ . '(): trashing entries: ' . join( ', ', $entry_ids ) );

			foreach ( $entry_ids as $entry_id ) {
				GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
			}
		}

		if ( ! empty( $delete_conditions ) ) {

			$query = new GF_Query();

			$all_delete_conditions = call_user_func_array( array( 'GF_Query_Condition', '_or' ), $delete_conditions );

			$entry_ids = $query->from( $delete_form_ids )->where( $all_delete_conditions )->get_ids();

			self::log_debug( __METHOD__ . '(): deleting entries: ' . join( ', ', $entry_ids ) );

			/**
			 * Allows the array of entry IDs to be modified before automatically deleting according to the
			 * personal data retention policy.
			 *
			 * @since 2.4
			 *
			 * @param int[] $entry_ids The array of entry IDs to delete.
			 */
			$entry_ids = apply_filters( 'gform_entry_ids_automatic_deletion', $entry_ids );

			foreach ( $entry_ids as $entry_id ) {
				GFAPI::delete_entry( $entry_id );
			}
		}

		self::log_debug( __METHOD__ . '(): done' );

	}

	/**
	 * Writes a message to the debug log
	 *
	 * @since 2.4
	 *
	 * @param $message
	 */
	public static function log_debug( $message ) {
		GFCommon::log_debug( $message );
	}

	/**
	 * Flushes the forms
	 *
	 * @since 2.4
	 */
	public static function flush_current_forms() {
		self::$_forms = null;
	}
}
