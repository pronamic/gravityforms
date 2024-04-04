<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use GF_Fields;
use GFForms;
use GFFormsModel;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Generic_Map extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'map';

	/**
	 * Number of mappings allowed.
	 *
	 * @since 2.5
	 *
	 * @var int
	 */
	public $limit = 0;

	/**
	 * Required choices that were not selected on save.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $invalid_choices = array();

	/**
	 * Key field properties.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $key_field = array(
		'title'            => '',
		'choices'          => array(),
		'placeholder'      => '',
		'display_all'      => false,
		'allow_custom'     => true,
		'allow_duplicates' => false,
	);

	/**
	 * Value field properties.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $value_field = array(
		'title'        => '',
		'placeholder'  => '',
		'allow_custom' => true,
	);

	protected $excluded_field_types;
	protected $required_field_types;
	protected $merge_tags;
	protected $field_map;


	/**
	 * Initialize Generic Map field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		// Set Settings framework instance.
		$this->settings = $settings;

		// Prepare required and excluded field types.
		$this->excluded_field_types = rgar( $props, 'exclude_field_types' );
		$this->required_field_types = isset( $props['field_types'] ) ? $props['field_types'] : rgar( $props, 'field_type' );

		// Merge Key Field properties.
		if ( rgar( $props, 'key_field' ) ) {

			if ( rgar( $props, 'disable_custom' ) ) {
				$this->key_field['allow_custom'] = ! (bool) $props['disable_custom'];
				unset( $props['disable_custom'] );
			} else if ( rgar( $props, 'enable_custom_key' ) ) {
				$this->key_field['allow_custom'] = (bool) $props['enable_custom_key'];
				unset( $props['enable_custom_key'] );
			} else if ( rgars( $props, 'key_field/custom_value' ) ) {
				$this->key_field['allow_custom'] = (bool) $props['key_field']['custom_value'];
			}

			$this->key_field = array_merge( $this->key_field, $props['key_field'] );
		}

		// Merge value field properties.
		if ( rgar( $props, 'value_field' ) ) {

			if ( rgar( $props, 'enable_custom_value' ) ) {
				$this->value_field['allow_custom'] = (bool) $props['enable_custom_value'];
				unset( $props['enable_custom_value'] );
			} else if ( rgars( $props, 'value_field/custom_value' ) ) {
				$this->value_field['allow_custom'] = (bool) $props['value_field']['custom_value'];
			}

			$this->value_field = array_merge( $this->value_field, $props['value_field'] );

		}

		// Prepare key field choices.
		if ( rgar( $props, 'field_map' ) ) {
			$this->key_field['choices'] = array_values( $props['field_map'] );
		} else if ( rgar( $props, 'key_choices' ) ) {
			$this->key_field['choices'] = $props['key_choices'];
		} else if ( rgars( $props, 'key_field/choices' ) ) {
			$this->key_field['choices'] = $props['key_field']['choices'];
		} else {
			$this->key_field['choices'] = rgar( $this->key_field, 'choices', array() );
		}

		$passed_choices = rgar( $this->value_field, 'choices' );

		if ( ! $passed_choices ) {
			$passed_choices = rgar( $props, 'value_choices' );
		}

		if ( ! $passed_choices ) {
			$passed_choices = 'form_fields';
		}

		$this->value_field['choices']            = array();
		$this->value_field['choices']['default'] = $passed_choices == 'form_fields' ? $this->get_value_choices( $this->required_field_types, $this->excluded_field_types ) : $this->get_prepopulated_choice( 'default', $passed_choices );

		// Assign the correct value field choices per key field choice.
		foreach ( $this->key_field['choices'] as $choice ) {

			// Choice doesn't have a name index; this likely means the choice is non-standard; bail and use default choices.
			if ( ! rgar( $choice, 'name' ) ) {
				continue;
			}

			$required_types = isset( $choice['field_types'] ) ? rgar( $choice, 'field_types', array() ) : rgar( $choice, 'required_types', array() );
			$excluded_types  = isset( $choice['exclude_field_types'] ) ? rgar( $choice, 'exclude_field_types', array() ) : rgar( $choice, 'excluded_types', array() );

			// Convert to array if field type was configured as a string to require only one field type.
			if ( ! is_array( $required_types ) ) {
				$required_types = array( $required_types );
			}

			// Convert to array if excluded field type was configured as string to exclude only one field type.
			if ( ! is_array( $excluded_types ) ) {
				$excluded_types = array( $excluded_types );
			}

			$key = 'filtered_choices_' . implode( '-', $required_types ) . '_' . implode( '-', $excluded_types );
			if ( ! isset( $this->value_field['choices'][ $key ] ) ) {
				$this->value_field['choices'][ $key ] = $passed_choices == 'form_fields' ? $this->get_value_choices( $required_types, $excluded_types ) : $this->get_prepopulated_choice( 'default', $passed_choices );
			}
			$this->value_field['choice_keys'][ $choice['name'] ] = $key;
		}

		// Translate base strings.
		if ( empty( $this->key_field['title'] ) ) {
			$this->key_field['title'] = esc_html__( 'Key', 'gravityforms' );
		}
		if ( empty( $this->key_field['custom'] ) ) {
			$this->key_field['custom'] = esc_html__( 'Custom Key', 'gravityforms' );
		}
		if ( empty( $this->value_field['title'] ) ) {
			$this->value_field['title'] = esc_html__( 'Value', 'gravityforms' );
		}
		if ( empty( $this->value_field['custom'] ) ) {
			$this->value_field['custom'] = esc_html__( 'Custom Value', 'gravityforms' );
		}

		unset( $props['key_field'], $props['value_field'] );

		parent::__construct( $props, $settings );

	}

	/**
	 * Looks into the $passed_choices for the specified $key. If $key is specified and mapped to an array, return the contents of that array. Otherwise return the entired $passed_choices array.
	 *
	 * @since 2.5.16
	 *
	 * @param string $key They filtered key to be used when looking for the child choices.
	 * @param array  $passed_choices The choices array that will be used to pre-populate the value field.
	 *
	 * @return array List of choices to be prepopulated
	 */
	private function get_prepopulated_choice( $key, $passed_choices ) {
		$is_filtered_choice_defined = isset( $passed_choices[ $key ] ) && is_array( $passed_choices[ $key ] );
		return $is_filtered_choice_defined ? $passed_choices[ $key ] : $passed_choices;
	}

	/**
	 * Register scripts to enqueue when displaying field.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function scripts() {

		$min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$dev_min = defined( 'GF_SCRIPT_DEBUG' ) && GF_SCRIPT_DEBUG ? '' : '.min';

		return array(
			array(
				'handle'  => 'gform_settings_field_map',
				'src'     => GFCommon::get_base_url() . "/assets/js/dist/field-map{$dev_min}.js",
				'version' => defined( 'GF_SCRIPT_DEBUG' ) && GF_SCRIPT_DEBUG ? filemtime( GFCommon::get_base_path() . "/assets/js/dist/field-map{$dev_min}.js" ) : GFForms::$version,
				'deps'    => array( 'wp-element', 'wp-i18n' ),
			),
		);

	}





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 * This contains the hidden input used to manage the state of the field and is also updated via react.
	 * This also contains the initializeFieldMap method which inits the react for the field and passes along
	 * the various props to then be used in the react app.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Get input, container names.
		$input_name     = esc_attr( sprintf( '%s_%s', $this->settings->get_input_name_prefix(), $this->name ) );
		$container_name = $input_name . '_container';

		// If no key field choices are provided, return.
		if ( empty( $this->key_field['choices'] ) && $this->type !== 'generic_map' && ! $this->key_field['allow_custom'] ) {
			return esc_html__( 'No mapping fields are available.', 'gravityforms' );
		}

		// Prepare value, key fields.
		$value_field            = $this->value_field;
		$value_field['choices'] = rgar( $value_field, 'choices' ) ? $this->get_choices( $value_field['choices'] ) : array();
		$key_field              = $this->key_field;
		$key_field['choices']   = rgar( $key_field, 'choices' ) ? $this->get_choices( $key_field['choices'] ) : array();
		// Populate default choices only if the feed hasn't been saved yet.
		if ( empty( $_GET['fid'] ) ) {
			$key_field['choices'] = $this->populate_default_key_values( $key_field['choices'] );
		}
		// Prepare JS params.
		$js_params = array(
			'input'           => $input_name,
			'inputType'       => $this->type,
			'keyField'        => $key_field,
			'valueField'      => $value_field,
			'limit'           => $this->limit,
			'invalidChoices'  => $this->invalid_choices,
			'mergeTagSupport' => property_exists( $this, 'merge_tags' ) ? $this->merge_tags : false,
		);

		// Prepare markup.
		// Display description.
		$html = $this->get_description();

		$html .= sprintf(
			'<span class="%1$s"><input type="hidden" name="%2$s" id="%2$s" value=\'%3$s\' />
				<div id="%4$s" class="gform-settings-field-map__container"></div>%5$s</span>
				<script type="text/javascript">initializeFieldMap( \'%4$s\', %6$s );</script></span>',
			esc_attr( $this->get_container_classes() ),
			$input_name, // Input name
			esc_attr( wp_json_encode( $this->get_value() ? $this->get_value() : array() ) ), // Input value
			$container_name, // Container name
			$this->get_error_icon(),
			wp_json_encode( $js_params )// JS params
		);

		return $html;

	}

	/**
	 * Gets the classes to apply to the field container.
	 * Removes invalid class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_container_classes() {

		$classes = parent::get_container_classes();
		$classes = explode( ' ', $classes );

		// Search for and remove invalid class.
		$invalid_key = array_search( 'gform-settings-input__container--invalid', $classes );
		if ( $invalid_key ) {
			unset( $classes[ $invalid_key ] );
		}

		return implode( ' ', $classes );

	}

	/**
	 * Populate key field choices with their default mappings.
	 *
	 * @since 2.5
	 *
	 * @param array $choices Existing choices.
	 *
	 * @return array
	 */
	public function populate_default_key_values( $choices ) {

		// If auto-mapping is disabled, return.
		if ( rgobj( $this, 'auto_mapping' ) === false ) {
			return $choices;
		}

		// Define global aliases to help with the common case mappings.
		$global_aliases = array(
			__( 'First Name', 'gravityforms' ) => array( __( 'Name (First)', 'gravityforms' ) ),
			__( 'Last Name', 'gravityforms' )  => array( __( 'Name (Last)', 'gravityforms' ) ),
			__( 'Address', 'gravityforms' )    => array( __( 'Address (Street Address)', 'gravityforms' ) ),
			__( 'Address 2', 'gravityforms' )  => array( __( 'Address (Address Line 2)', 'gravityforms' ) ),
			__( 'City', 'gravityforms' )       => array( __( 'Address (City)', 'gravityforms' ) ),
			__( 'State', 'gravityforms' )      => array( __( 'Address (State / Province)', 'gravityforms' ) ),
			__( 'Zip', 'gravityforms' )        => array( __( 'Address (Zip / Postal Code)', 'gravityforms' ) ),
			__( 'Country', 'gravityforms' )    => array( __( 'Address (Country)', 'gravityforms' ) ),
		);

		// Loop through choices, set default values.
		foreach ( $choices as &$choice ) {

			// Initialize array to store auto-population choices.
			$default_value_choices = array( $choice['label'] );
			// If one or more global aliases are defined for this particular label, merge them into auto-population choices.
			if ( isset( $global_aliases[ $choice['label'] ] ) ) {
				$default_value_choices = array_merge( $default_value_choices, $global_aliases[ $choice['label'] ] );
			}

			// Convert all auto-population choices to lowercase.
			$default_value_choices = array_map( 'strtolower', $default_value_choices );

			// If choice has specific value choices after evaluating required and excluded types, get them.
			if ( rgar( $choice, 'choices' ) && is_array( $choice['choices'] ) ) {
				$value_choices = $choice['choices'];
			} else {
				$value_choices = $this->value_field['choices'];
			}

			// Each key field potentially has it's own array of potential choices based on required and excluded types, so we need to loop through those.
			foreach ( $value_choices as $key_field_choices ) {

				// Loop through each group for each key field and see if there are choices available.
				foreach ( $key_field_choices as $group ) {
					if ( ! rgar( $group, 'choices' ) || ! is_array( $group['choices'] ) ) {
						continue;
					}

					foreach ( $group['choices']  as $value_choice ) {
						if ( empty( $value_choice['value'] ) ) {
							continue;
						}

						// If lowercase field label matches a default value choice, set it to the default value.
						if ( in_array( strtolower( $value_choice['label'] ), $default_value_choices ) ) {
							$choice['default_value'] = $value_choice['value'];
							break 3;
						}
					}
				}
			}
		}

		return $choices;
	}

	/**
	 * Get choices for value field.
	 *
	 * @since 2.5
	 *
	 * @param array $required_types Required field types.
	 * @param array $excluded_types Excluded field types.
	 *
	 * @return array
	 */
	public function get_value_choices( $required_types = array(), $excluded_types = array() ) {

		// Get form.
		$form = $this->settings->get_current_form();

		// No form found, set up a default array with empty values.
		if ( ! is_array( $form ) ) {
			$form = array(
				'fields' => array(),
			);
		}

		// Initialize choices array and input type.
		$choices    = array();
		$input_type = '';
		$form_id    = isset( $form['id'] ) ? $form['id'] : 0;

		// Force required, excluded types to arrays.
		$required_types = is_array( $required_types ) ? $required_types : array();
		$excluded_types = is_array( $excluded_types ) ? $excluded_types : array();

		$form_field_choices = array();

		/**
		 * Populate form fields.
		 *
		 * @var \GF_Field $field
		 */
		foreach ( $form['fields'] as $field ) {

			// Get input type and available inputs.
			$input_type = $field->get_input_type();
			$inputs     = $field->get_entry_inputs();

			// If field type is excluded, skip.
			if ( ! empty( $excluded_types ) && in_array( $input_type, $excluded_types ) ) {
				continue;
			}

			// If field type is not whitelisted, skip.
			if ( ! empty( $required_types ) && ! in_array( $input_type, $required_types ) ) {
				continue;
			}

			// Handle fields with inputs.
			if ( is_array( $inputs ) ) {

				// Add full, selected choices.
				switch ( $input_type ) {

					case 'address':

						$form_field_choices[] = array(
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full Address', 'gravityforms' ) . ')' ),
							'value' => $field->id,
							'type' => 'address'
						);

						break;

					case 'name':

						$form_field_choices[] = array(
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full Name', 'gravityforms' ) . ')' ),
							'value' => $field->id,
							'type' => 'name'
						);

						break;

					case 'checkbox':

						$form_field_choices[] = array(
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Selected', 'gravityforms' ) . ')' ),
							'value' => $field->id,
							'type' => 'checkbox'
						);

						break;

				}

				// Add inputs as choices.
				foreach ( $inputs as $input ) {
					$form_field_choices[] = array(
						'label' => strip_tags( GFCommon::get_label( $field, $input['id'] ) ),
						'value' => $input['id'],
						'type'  => $field['type'],
					);
				}

				// Handle multi-column List fields.
			} else if ( $input_type === 'list' && $field->enableColumns ) {

				// Add choice for full List value.
				$form_field_choices[] = array(
					'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')' ),
					'value' => $field->id,
					'type'  => 'list',
				);

				// Add choice for each column.
				$col_index = 0;
				foreach ( $field->choices as $column ) {
					$form_field_choices[] = array(
						'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html( rgar( $column, 'text' ) ) . ')' ),
						'value' => $field->id . '.' . $col_index,
						'type'  => 'list',
					);
					$col_index++;
				}

			} else if ( ! $field->displayOnly ) {

				$form_field_choices[] = array(
					'label' => strip_tags( GFCommon::get_label( $field ) ),
					'value' => $field->id,
					'type'  => $field['type'],
				);

			}

		}

		if ( count( $form_field_choices ) ) {
			// Add first choice.
			if ( count( $required_types ) === 0 || count( $required_types ) > 1 ) {

				$choices[] = array(
					'label' => esc_html__( 'Select a Field', 'gravityforms' ),
					'value' => '',
					'type'  => $field['type'],
				);

			} else {

				$choices[] = array(
					'label' => sprintf(
						esc_html__( 'Select a %s Field', 'gravityforms' ),
						GF_Fields::get( $required_types[0] ) ? ucfirst( GF_Fields::get( $required_types[0] )->get_form_editor_field_title() ) : ''
					),
					'value' => '',
					'type'  => $field['type'],
				);

			}

			// Add Form Fields choice.
			$choices['fields'] = array(
				'label'   => esc_html__( 'Form Fields', 'gravityforms' ),
				'choices' => $form_field_choices,
			);
		}

		// If field type is not restricted, add default fields and entry meta.
		if ( empty( $required_types ) ) {

			// Add default fields.
			$choices[] = array(
				'label'   => esc_html__( 'Entry Properties', 'gravityforms' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Entry ID', 'gravityforms' ),
						'value' => 'id',
					),
					array(
						'label' => esc_html__( 'Entry Date', 'gravityforms' ),
						'value' => 'date_created',
					),
					array(
						'label' => esc_html__( 'User IP', 'gravityforms' ),
						'value' => 'ip',
					),
					array(
						'label' => esc_html__( 'Source Url', 'gravityforms' ),
						'value' => 'source_url',
					),
					array(
						'label' => esc_html__( 'Form Title', 'gravityforms' ),
						'value' => 'form_title',
					),
				),
			);

			// Get Entry Meta.
			$entry_meta = GFFormsModel::get_entry_meta( $this->settings->get_current_form_id() );

			// If there is Entry Meta, add as choices.
			if ( ! empty( $entry_meta ) ) {

				$meta_choices = array();

				foreach ( $entry_meta as $key => $meta ) {
					$meta_choices[] = array(
						'label' => rgar( $meta, 'label' ),
						'value' => $key,
					);
				}

				$choices[] = array(
					'label'   => esc_html__( 'Entry Meta', 'gravityforms' ),
					'choices' => $meta_choices,
				);

			}

		}

		/**
		 * Filter the choices available in the field map drop down.
		 *
		 * @since 2.0.7.11
		 *
		 * @deprecated Deprecated since 2.5. Use gform_field_map_choices instead.
		 *
		 * @param array             $fields              The value and label properties for each choice.
		 * @param int               $form_id             The ID of the form currently being configured.
		 * @param null|array        $field_type          Null or the field types to be included in the drop down.
		 * @param null|array|string $exclude_field_types Null or the field type(s) to be excluded from the drop down.
		 */
		$choices = apply_filters( 'gform_addon_field_map_choices', $choices, $form_id, $input_type, $excluded_types );

		/**
		 * Filter the choices available in the field map drop down.
		 *
		 * @since 2.5
		 *
		 * @param array             $fields              The value and label properties for each choice.
		 * @param int               $form_id             The ID of the form currently being configured.
		 * @param null|array        $field_type          Null or the field types to be included in the drop down.
		 * @param null|array|string $exclude_field_types Null or the field type(s) to be excluded from the drop down.
		 */
		$choices = apply_filters( 'gform_field_map_choices', $choices, $form_id, $input_type, $excluded_types );
		return array_values( $choices );

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param array|bool|string $value Posted field value.
	 */
	public function do_validation( $value ) {

		// Get required choices.
		$required_choices = $this->get_required_choices();

		// If no choices are required, exit.
		if ( empty( $required_choices ) ) {
			return;
		}

		if ( ! is_array( $value ) ) {
			return;
		}

		// Loop through the field map, check required choices.
		foreach ( $value as $mapping ) {

			// If this is not a required choice, skip.
			if ( ! in_array( $mapping['key'], $required_choices ) ) {
				continue;
			}

			// Get value.
			$mapping_value = $mapping['value'] === 'gf_custom' ? $mapping['custom_value'] : $mapping['value'];
			$mapping_value = trim( $mapping_value );

			// If mapping value is empty, flag choice.
			if ( empty( $mapping_value ) ) {
				$this->set_error( rgobj( $this, 'error_message' ) );
				$this->invalid_choices[] = $mapping['key'];
			}

		}

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get choices that are required to be filled out.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_required_choices() {

		// Get key field choices.
		$key_choices = $this->get_choices( $this->key_field['choices'] );

		if ( ! $key_choices ) {
			return array();
		}

		// Merge sub-choices.
		foreach ( $key_choices as &$key_choice ) {
			if ( rgar( $key_choice, 'choices' ) ) {
				$key_choices = array_merge( $key_choices, $key_choice['choices'] );
				unset( $key_choice['choices'] );
			}
		}

		// Get required choices.
		$required_choices = array_filter(
			$key_choices,
			function( $choice ) { return rgar( $choice, 'required', false ); }
		);

		// Return only the name.
		return array_map(
			function( $choice ) {
				return rgar( $choice, 'value' ) ? rgar( $choice, 'value' ) : rgar( $choice, 'name' );
			},
			$required_choices
		);

	}

	/**
	 * Modify field value before saving.
	 *
	 * @since 2.5
	 *
	 * @param array|bool|string $value
	 *
	 * @return array|bool|string
	 */
	public function save( $value ) {

		return is_array( $value ) ? $value : json_decode( $value, true );

	}

}

Fields::register( 'generic_map', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Generic_Map' );
