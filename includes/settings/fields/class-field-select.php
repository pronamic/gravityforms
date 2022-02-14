<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GF_Fields;
use GFCommon;
use GFFormsModel;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-select.php';

class Field_Select extends Select {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'field_select';

	/**
	 * Drop Down arguments.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * Initialize Field Select field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Force args to array.
		if ( ! is_array( $this->args ) ) {
			$this->args = (array) $this->args;
		}

		// Populate args with defaults.
		$this->args = wp_parse_args(
			$this->args,
			array(
				'append_choices'       => array(),
				'disable_first_choice' => false,
				'input_types'          => array(),
			)
		);

		// Reset choices.
		$this->choices = array();

		// Add a "Select a Field" choice.
		if ( ! $this->args['disable_first_choice'] ) {

			// Prepare first choice label.
			$first_choice_label = __( 'Select a Field', 'gravityforms' );

			// If a singular input type is defined, set field type as first choice label.
			if ( ( ! empty( $this->args['input_types'] ) && is_string( $this->args['input_types'] ) ) || ( is_array( $this->args['input_types'] ) && count( $this->args['input_types'] ) === 1 ) ) {

				// Get input type and field object.
				$input_type = is_array( $this->args['input_types'] ) ? $this->args['input_types'][0] : $this->args['input_types'];
				$field      = GF_Fields::get( $input_type );

				// Prepare first choice label.
				if ( $field ) {
					$first_choice_label = sprintf( __( 'Select a %s Field', 'gravityforms' ), ucwords( $field->get_form_editor_field_title() ) );
				}

			}

			// Add first choice label.
			$this->choices[] = array(
				'value' => '',
				'label' => $first_choice_label,
			);

		}

		// Add form fields, append choices as choices.
		$this->choices = array_merge(
			$this->choices,
			$this->get_form_fields_as_choices( $this->settings->get_current_form() ),
			$this->args['append_choices']
		);

		// Reset choices if only one exists and no choices label is set.
		if ( ! $this->args['disable_first_choice'] && count( $this->choices ) === 1 && rgobj( $this, 'no_choices' ) ) {
			$this->choices = array();
		}

		// Set default value.
		$this->default_value = $this->get_default_choice();

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Returns the field to be selected by default for Field Select fields based on matching labels.
	 *
	 * @since 2.5
	 *
	 * @return string|null
	 */
	public function get_default_choice() {

		// If default value is defined and not an array, return.
		if ( ! empty( $this->default_value ) && ! is_array( $this->default_value ) ) {
			return $this->default_value;
		}

		// If auto population is disabled, return.
		if ( rgobj( $this, 'auto_mapping' ) === false ) {
			return null;
		}

		// Get field label.
		$field_label = $this->label;

		// Initialize array to store auto-population choices.
		$default_value_choices = array( $field_label );

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

		// If one or more global aliases are defined for this particular field label, merge them into auto-population choices.
		if ( isset( $global_aliases[ $field_label ] ) ) {
			$default_value_choices = array_merge( $default_value_choices, $global_aliases[ $field_label ] );
		}

		// If field aliases are defined, merge them into auto-population choices.
		if ( is_array( $this->default_value ) && rgar( $this->default_value, 'aliases' ) ) {
			$default_value_choices = array_merge( $default_value_choices, $this->default_value['aliases'] );
		}

		// Convert all auto-population choices to lowercase.
		$default_value_choices = array_map( 'strtolower', $default_value_choices );

		// Loop through fields.
		foreach ( $this->choices as $choice ) {

			// If choice value is empty, skip.
			if ( rgblank( $choice['value'] ) ) {
				continue;
			}

			// If lowercase field label matches a default value choice, set it to the default value.
			if ( in_array( strtolower( $choice['label'] ), $default_value_choices ) ) {
				return $choice['value'];
			}

		}

		return null;

	}

	/**
	 * Prepares a form's fields as choices.
	 *
	 * @since 2.5
	 *
	 * @param array $form Form object.
	 *
	 * @return array
	 */
	public function get_form_fields_as_choices( $form ) {

		// Parse arguments, add defaults.
		$args = wp_parse_args(
			$this->args,
			array(
				'field_types' => array(),
				'input_types' => array(),
				'callback'    => false,
			)
		);

		// Initialize choices array.
		$choices = array();
		$fields  = $this->get_form_fields_to_process( $form, $args );

		/**
		 * Loop through form fields, add as choices.
		 *
		 * @var \GF_Field $field
		 */
		foreach ( $fields as $field ) {

			// Get inputs for field.
			$inputs = $field->get_entry_inputs();

			// Add choices based on field inputs.
			if ( is_array( $inputs ) ) {

				// Add full value for certain fields.
				switch ( $field->get_input_type() ) {

					case 'address':
					case 'name':
						$choices[] = array(
							'value' => $field->id,
							'label' => sprintf(
								'%s (%s)',
								strip_tags( GFCommon::get_label( $field ) ),
								esc_html__( 'Full', 'gravityforms' )
							),
						);
						break;

					case 'checkbox':
						$choices[] = array(
							'value' => $field->id,
							'label' => sprintf(
								'%s (%s)',
								strip_tags( GFCommon::get_label( $field ) ),
								esc_html__( 'Selected', 'gravityforms' )
							),
						);
						break;

				}

				// Loop through inputs, add as choices.
				foreach ( $inputs as $input ) {

					$choices[] = array(
						'value' => $input['id'],
						'label' => strip_tags( GFCommon::get_label( $field, $input['id'] ) ),
					);

				}
			} elseif ( ! $field->displayOnly || $field->get_input_type() === 'password' ) {

				$choices[] = array(
					'value' => $field->id,
					'label' => strip_tags( GFCommon::get_label( $field ) ),
				);

			}
		}

		return $choices;

	}

	/**
	 * Get the allowed form fields to process as choice values.
	 *
	 * @since 2.5.7
	 *
	 * @param array $form The form currently being configured.
	 * @param array $args A selection of field configuration arguments.
	 *
	 * @return array|false|mixed
	 */
	private function get_form_fields_to_process( $form, $args ) {
		$fields      = array();
		$form_fields = array();

		if ( GFCommon::form_has_fields( $form ) ) {
			$form_fields = $form['fields'];
		}

		foreach ( $form_fields as $field ) {
			// If field is not in the whitelisted field types, skip.
			if ( ! empty( $args['field_types'] ) && is_array( $args['field_types'] ) && ! in_array( $field->type, $args['field_types'] ) ) {
				continue;
			}

			// Determine if field is a matching input type.
			$input_type         = GFFormsModel::get_input_type( $field );
			$allowed_input_type = empty( $args['input_types'] ) || ( is_array( $args['input_types'] ) && in_array( $input_type, $args['input_types'] ) );

			// Apply callback to input type check.
			if ( is_callable( $args['callback'] ) ) {
				$allowed_input_type = call_user_func( $args['callback'], $allowed_input_type, $field, $form );
			}

			// If field's input type is not allowed, skip.
			if ( ! $allowed_input_type ) {
				continue;
			}

			// Test for field property value.
			if ( rgar( $args, 'property' ) && ( ! isset( $field->{$args['property']} ) || $field->{$args['property']} != rgar( $args, 'property_value' ) ) ) {
				continue;
			}

			$fields[] = $field;
		}

		// If the field as a valid fields_callback, pass $fields through it to allow modifications.
		$fields_callback = rgobj( $this, 'fields_callback' );

		if ( is_callable( $fields_callback ) ) {
			$fields = call_user_func_array( $fields_callback, array( $fields, $this->settings->get_current_form() ) );
		}

		return $fields;
	}

}

Fields::register( 'field_select', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Select' );
