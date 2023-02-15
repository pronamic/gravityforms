<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Field
 *
 * This class provides the base functionality for developers when creating new fields for Gravity Forms. It facilitates the following:
 *  Adding a button for the field to the form editor
 *  Defining the field title to be used in the form editor
 *  Defining which settings should be present when editing the field
 *  Registering the field as compatible with conditional logic
 *  Outputting field scripts to the form editor and front-end
 *  Defining the field appearance on the front-end, in the form editor and on the entry detail page
 *  Validating the field during submission
 *  Saving the entry value
 *  Defining how the entry value is displayed when merge tags are processed, on the entries list and entry detail pages
 *  Defining how the entry value should be formatted when used in csv exports and by framework based add-ons
 */
class GF_Field extends stdClass implements ArrayAccess {

	const SUPPRESS_DEPRECATION_NOTICE = true;

	private static $deprecation_notice_fired = false;

	private $_is_entry_detail = null;

	/**
	 * An array of properties used to help define and determine the context for the field.
	 * As this is private, it won't be available in any json_encode() output and consequently not saved in the Form array.
	 *
	 * @since 2.3
	 *
	 * @private
	 *
	 * @var array
	 */
	private $_context_properties = array();

	/**
	 * @var array $_merge_tag_modifiers An array of modifiers specified on the field or all_fields merge tag being processed.
	 */
	private $_merge_tag_modifiers = array();

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = false;

	public function __construct( $data = array() ) {
		if ( empty( $data ) ) {
			return;
		}
		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Fires the deprecation notice only once per page. Not fired during AJAX requests.
	 *
	 * @param string $offset The array key being accessed.
	 */
	private function maybe_fire_array_access_deprecation_notice( $offset ) {

		if ( self::SUPPRESS_DEPRECATION_NOTICE ) {
			return;
		};

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! self::$deprecation_notice_fired ) {
			_deprecated_function( "Array access to the field object is now deprecated. Further notices will be suppressed. \$field['" . $offset . "']", '2.0', 'the object operator e.g. $field->' . $offset );
			self::$deprecation_notice_fired = true;
		}
	}

	/**
	 * Whether or not an offset exists.
	 *
	 * @since 1.9
	 *
	 * @param mixed $offset The offset to check for.
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$this->maybe_fire_array_access_deprecation_notice( $offset );

		return isset( $this->$offset );
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @since 1.9
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$this->maybe_fire_array_access_deprecation_notice( $offset );
		if ( ! isset( $this->$offset ) ) {
			$this->$offset = '';
		}

		return $this->$offset;
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @since 1.9
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $data   The value to set.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $data ) {
		$this->maybe_fire_array_access_deprecation_notice( $offset );
		if ( $offset === null ) {
			$this[] = $data;
		} else {
			$this->$offset = $data;
		}
	}

	/**
	 * Unsets an offset.
	 *
	 * @since 1.9
	 *
	 * @param mixed $offset The offset to unset.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->maybe_fire_array_access_deprecation_notice( $offset );
		unset( $this->$offset );
	}

	public function __isset( $key ) {
		return isset( $this->$key );
	}

	public function __set( $key, $value ) {
		switch( $key ) {
			case '_context_properties' :
				_doing_it_wrong( '$field->_context_properties', 'Use $field->get_context_property() instead.', '2.3' );
				break;
			case 'adminOnly':
				// intercept 3rd parties trying to set the adminOnly property and convert to visibility property
				$this->visibility = $value ? 'administrative' : 'visible';
				break;
			default:
				$this->$key = $value;
		}
	}

	/**
	 * The getter method of the field property.
	 *
	 * @since unknown
	 * @since 2.4.19  Add whitelist for the size property.
	 *
	 * @param string $key The field property.
	 *
	 * @return bool|mixed
	 */
	public function &__get( $key ) {

		switch ( $key ) {
			case '_context_properties' :
				_doing_it_wrong( '$field->_context_properties', 'Use $field->get_context_property() instead.', '2.3' );
				$value = false;

				return $value;
			case 'adminOnly' :
				// intercept 3rd parties trying to get the adminOnly property and fetch visibility property instead
				$value = $this->visibility == 'administrative'; // set and return variable to avoid notice

				return $value;
			case 'size':
				$value = '';

				if ( isset( $this->size ) ) {
					$value = GFCommon::whitelist( $this->size, array( 'small', 'medium', 'large' ) );
				}

				return $value;
			default:
				if ( ! isset( $this->$key ) ) {
					$this->$key = '';
				}
		}

		return $this->$key;
	}

	public function __unset( $key ) {
		unset( $this->$key );
	}

	/**
	 * Set a context property for this field.
	 *
	 * @since 2.3
	 * @since 2.5.10 - Property key can be an array in order to set a nested value.
	 *
	 *
	 * @param array|string $property_key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function set_context_property( $property_key, $value ) {
		if ( is_array( $property_key ) ) {
			$temp = &$this->_context_properties;
			foreach ( $property_key as $key ) {
				if ( ! isset( $temp[ $key ] ) ) {
					$temp[ $key ] = array();
				}

				$temp = &$temp[ $key ];
			}

			$temp = $value;

			unset( $temp );

			return;
		}

		$this->_context_properties[ $property_key ] = $value;
	}

	public function get_context_property( $property_key ) {
		return isset( $this->_context_properties[ $property_key ] ) ? $this->_context_properties[ $property_key ] : null;
	}

	/**
	 * Set the validation state for a single input within this field.
	 *
	 * @since 2.5.10
	 *
	 * @param string $input_id
	 * @param bool   $is_valid
	 *
	 * @return void
	 */
	public function set_input_validation_state( $input_id, $is_valid ) {
		$input_id = explode( '.', $input_id );
		$input_id = end( $input_id );

		$this->set_context_property( array( 'input_validation_states', $input_id ), $is_valid );
	}

	/**
	 * Determine whether a single input has been marked as invalid via context properties.
	 *
	 * @since 2.5.10
	 *
	 * @param $input_id
	 *
	 * @return bool
	 */
	protected function is_input_valid( $input_id ) {
		if ( empty( $this->get_entry_inputs() ) ) {
			return true;
		}

		$input_id = explode( '.', $input_id );
		$input_id = end( $input_id );

		$validations = $this->get_context_property( 'input_validation_states' );

		return isset( $validations[ $input_id ] ) ? $validations[ $input_id ] : true;
	}


	// # FORM EDITOR & FIELD MARKUP -------------------------------------------------------------------------------------

	/**
	 * Returns the field title.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return $this->type;
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--cog';
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return sprintf( esc_attr__( 'Add a %s field to your form.', 'gravityforms' ), $this->get_form_editor_field_title() );
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array();
	}

	/**
	 * Returns the field button properties for the form editor. The array contains two elements:
	 * 'group' => 'standard_fields' // or  'advanced_fields', 'post_fields', 'pricing_fields'
	 * 'text'  => 'Button text'
	 *
	 * Built-in fields don't need to implement this because the buttons are added in sequence in GFFormDetail
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'standard_fields',
			'text'  => $this->get_form_editor_field_title(),
			'icon'  => $this->get_form_editor_field_icon(),
			'description' => $this->get_form_editor_field_description()
		);
	}

	/**
	 * Returns the class names of the settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array();
	}

	/**
	 * Override to indicate if this field type can be used when configuring conditional logic rules.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return false;
	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {
		return '';
	}

	/**
	 * Returns the scripts to be included with the form init scripts on the front-end.
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_form_inline_script_on_page_render( $form ) {
		return '';
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		return '';
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$form_id = (int) rgar( $form, 'id' );

		$field_label = $this->get_field_label( $force_frontend_label, $value );
		if ( ! in_array( $this->inputType, array( 'calculation', 'singleproduct' ), true ) ) {
			// Calculation and Single Product field add a screen reader text to the label so do not escape it.
			$field_label = esc_html( $field_label );
		}

		$validation_message_id = 'validation_message_' . $form_id . '_' . $this->id;
		$validation_message = ( $this->failed_validation && ! empty( $this->validation_message ) ) ? sprintf( "<div id='%s' class='gfield_description validation_message gfield_validation_message'>%s</div>", $validation_message_id, $this->validation_message ) : '';

		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		$required_div = $this->isRequired ? '<span class="gfield_required">' . $this->get_required_indicator() . '</span>' : '';

		$admin_buttons = $this->get_admin_buttons();

		$target_input_id = $this->get_first_input_id( $form );

		$label_tag = $this->get_field_label_tag( $form );

		$for_attribute = empty( $target_input_id ) || $label_tag === 'legend' ? '' : "for='{$target_input_id}'";

		$admin_hidden_markup = ( $this->visibility == 'hidden' ) ? $this->get_hidden_admin_markup() : '';

		$description = $this->get_description( $this->description, 'gfield_description' );

		if ( $this->is_description_above( $form ) ) {
			$clear         = $is_admin ? "<div class='gf_clear'></div>" : '';
			$field_content = sprintf( "%s%s<$label_tag class='%s' $for_attribute >%s%s</$label_tag>%s{FIELD}%s$clear", $admin_buttons, $admin_hidden_markup, esc_attr( $this->get_field_label_class() ), $field_label, $required_div, $description, $validation_message );
		} else {
			$field_content = sprintf( "%s%s<$label_tag class='%s' $for_attribute >%s%s</$label_tag>{FIELD}%s%s", $admin_buttons, $admin_hidden_markup, esc_attr( $this->get_field_label_class() ), $field_label, $required_div, $description, $validation_message );
		}

		return $field_content;
	}

	/**
	 * Returns the HTML tag for the field label.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_label_tag( $form ) {

		// Get field container tag.
		$container_tag = $this->get_field_container_tag( $form );

		return $container_tag === 'fieldset' ? 'legend' : 'label';

	}

	/**
	 * Returns the HTML markup for the field's containing element.
	 *
	 * @since 2.5
	 *
	 * @param array $atts Container attributes.
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container( $atts, $form ) {

		// Get the field container tag.
		$tag = $this->get_field_container_tag( $form );

		// Parse the provided attributes.
		$atts = wp_parse_args( $atts, array(
			'id'                  => '',
			'class'               => '',
			'style'               => '',
			'tabindex'            => '',
			'aria-atomic'         => '',
			'aria-live'           => '',
			'data-field-class'    => '',
			'data-field-position' => '',
		) );

		$tabindex_string = (rgar( $atts, 'tabindex' ) ) === '' ?  '' : ' tabindex="' . esc_attr( $atts['tabindex'] ) . '"';
		$disable_ajax_reload = $this->disable_ajax_reload();
		$ajax_reload_id      = $disable_ajax_reload === 'skip' || $disable_ajax_reload === 'true' || $disable_ajax_reload === true ? 'true' : esc_attr( rgar( $atts, 'id' ) );
		return sprintf(
			'<%1$s id="%2$s"  class="%3$s" %4$s%5$s%6$s%7$s%8$s%9$s data-js-reload="%10$s">{FIELD_CONTENT}</%1$s>',
			$tag,
			esc_attr( rgar( $atts, 'id' ) ),
			esc_attr( rgar( $atts, 'class' ) ),
			rgar( $atts, 'style' ) ? ' style="' . esc_attr( $atts['style'] ) . '"' : '',
			( rgar( $atts, 'tabindex' ) ) === false ? '' : $tabindex_string,
			rgar( $atts, 'aria-atomic' ) ? ' aria-atomic="' . esc_attr( $atts['aria-atomic'] ) . '"' : '',
			rgar( $atts, 'aria-live' ) ? ' aria-live="' . esc_attr( $atts['aria-live'] ) . '"' : '',
			rgar( $atts, 'data-field-class' ) ? ' data-field-class="' . esc_attr( $atts['data-field-class'] ) . '"' : '',
			rgar( $atts, 'data-field-position' ) ? ' data-field-position="' . esc_attr( $atts['data-field-position'] ) . '"' : '',
			$ajax_reload_id
		);

	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		return GFCommon::is_legacy_markup_enabled( $form ) ? 'li' : 'div';

	}

	/**
	 * Get field label class.
	 *
	 * @since unknown
	 * @since 2.5     Added `screen-reader-text` if the label hasn't been set; added `gfield_label_before_complex` if the field has inputs.
	 * @since 2.7     Added `gform-field-label` for the theme framework.
	 *
	 * @return string
	 */
	public function get_field_label_class() {
		$class = 'gfield_label';
		$class .= ' gform-field-label';

		// Added `screen-reader-text` if the label hasn't been set.
		$class .= ( rgblank( $this->label ) ) ? ' screen-reader-text' : '';

		// Added `gfield_label_before_complex` if the field has inputs.
		$class .= is_array( $this->inputs ) ? ' gfield_label_before_complex' : '';

		return $class;
	}

	/**
	 * Get field CSS class.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function get_field_css_class() {
		return '';
	}

	/**
	 * Return an aria-label for a field action (delete, edit, duplicate).
	 *
	 * @since 2.5
	 *
	 * @param str $action The button action as descriptive text.
	 * @param str $label The field label.
	 *
	 * @return str The passed aria-label or an automatically generated label if it is blank.
	 */
	public function get_field_action_aria_label( $action = '', $label = '' ) {
		if ( $label !== '' ) {
			$label = wp_strip_all_tags( $label );
		} else {
			$label = wp_strip_all_tags( $this->get_field_label( true, '' ) );
		}
		return sprintf( '%1$s - %2$s, %3$s.', esc_attr( $label ), esc_attr( $this->type ), esc_attr( $action ) );
	}

	// # SUBMISSION -----------------------------------------------------------------------------------------------------

	/**
	 * Whether this field expects an array during submission.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function is_value_submission_array() {
		return false;
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {

		$copy_values_option_activated = $this->enableCopyValuesOption && rgpost( 'input_' . $this->id . '_copy_values_activated' );

		if ( is_array( $this->inputs ) ) {
			foreach ( $this->inputs as $input ) {

				if ( $copy_values_option_activated ) {
					$input_id          = $input['id'];
					$input_name        = 'input_' . str_replace( '.', '_', $input_id );
					$source_field_id   = $this->copyValuesOptionField;
					$source_input_name = str_replace( 'input_' . $this->id, 'input_' . $source_field_id, $input_name );
					$value             = rgpost( $source_input_name );
				} else {
					$value = rgpost( 'input_' . str_replace( '.', '_', $input['id'] ) );
				}

				if ( is_array( $value ) && ! empty( $value ) ) {
					return false;
				}

				if ( ! is_array( $value ) && strlen( trim( $value ) ) > 0 ) {
					return false;
				}
			}

			return true;
		} else {
			if ( $copy_values_option_activated ) {
				$value = rgpost( 'input_' . $this->copyValuesOptionField );
			} else {
				$value = rgpost( 'input_' . $this->id );
			}

			if ( is_array( $value ) ) {
				//empty if any of the inputs are empty (for inputs with the same name)
				foreach ( $value as $input ) {
					$input = GFCommon::trim_deep( $input );
					if ( GFCommon::safe_strlen( $input ) <= 0 ) {
						return true;
					}
				}

				return false;
			} elseif ( $this->enablePrice ) {
				list( $label, $price ) = rgexplode( '|', $value, 2 );
				$is_empty = ( strlen( trim( $price ) ) <= 0 );

				return $is_empty;
			} else {
				$is_empty = ( strlen( trim( $value ) ) <= 0 ) || ( $this->type == 'post_category' && $value < 0 );

				return $is_empty;
			}
		}
	}

	/**
	 * Is the given value considered empty for this field.
	 *
	 * @since 2.4
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function is_value_empty( $value ) {
		if ( is_array( $this->inputs ) ) {
			if ( $this->is_value_submission_array() ) {
				foreach ( $this->inputs as $i => $input ) {
					$v = isset( $value[ $i ] ) ?  $value[ $i ] : '';
					if ( is_array( $v ) && ! empty( $v ) ) {
						return false;
					}

					if ( ! is_array( $v ) && strlen( trim( $v ) ) > 0 ) {
						return false;
					}
				}
			} else {
				foreach ( $this->inputs as $input ) {
					$input_id = (string) $input['id'];
					$v = isset( $value[ $input_id ] ) ?  $value[ $input_id ] : '';
					if ( is_array( $v ) && ! empty( $v ) ) {
						return false;
					}

					if ( ! is_array( $v ) && strlen( trim( $v ) ) > 0 ) {
						return false;
					}
				}
			}

		} elseif ( is_array( $value ) ) {
			// empty if any of the inputs are empty (for inputs with the same name)
			foreach ( $value as $input ) {
				$input = GFCommon::trim_deep( $input );
				if ( GFCommon::safe_strlen( $input ) <= 0 ) {
					return true;
				}
			}

			return false;
		} elseif ( empty( $value ) ) {
			return true;
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Override this method to perform custom validation logic.
	 *
	 * Return the result (bool) by setting $this->failed_validation.
	 * Return the validation message (string) by setting $this->validation_message.
	 *
	 * @since 1.9
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		//
	}

	/**
	 * Sets the failed_validation and validation_message properties for a required field error.
	 *
	 * @since 2.6.5
	 *
	 * @param mixed $value                   The field value.
	 * @param bool  $require_complex_message Indicates if the field must have a complex validation message for the error to be set.
	 *
	 * @return void
	 */
	public function set_required_error( $value, $require_complex_message = false ) {
		$complex_message = $this->complex_validation_message( $value, $this->get_required_inputs_ids() );

		if ( $require_complex_message && ! $complex_message ) {
			return;
		}

		$this->failed_validation  = true;
		$this->validation_message = empty( $this->errorMessage ) ? __( 'This field is required.', 'gravityforms' ) : $this->errorMessage;

		if ( $complex_message ) {
			$this->validation_message .= ' ' . $complex_message;
		}
	}

	/**
	 * Override to modify the value before it's used to generate the complex validation message.
	 *
	 * @since 2.6.5
	 *
	 * @param array $value The value to be prepared.
	 *
	 * @return array
	 */
	public function prepare_complex_validation_value( $value ) {
		return $value;
	}

	/**
	 * Create a validation message for a required field with multiple inputs.
	 *
	 * The validation message will specify which inputs need to be filled out.
	 *
	 * @since 2.5
	 * @since 2.6.5 Updated to use prepare_complex_validation_value().
	 *
	 * @param array $value            The value entered by the user.
	 * @param array $required_inputs  The required inputs to validate.
	 *
	 * @return string|false
	 */
	public function complex_validation_message( $value, $required_inputs ) {
		if ( empty( $this->inputs ) || empty( $required_inputs ) ) {
			return false;
		}

		$value        = $this->prepare_complex_validation_value( $value );
		$error_inputs = array();

		foreach ( $required_inputs as $input ) {
			if ( rgblank( rgar( $value, $this->id . '.' . $input ) ) && ! $this->get_input_property( $input, 'isHidden' ) ) {
				$custom_label   = $this->get_input_property( $input, 'customLabel' );
				$label          = $custom_label ? $custom_label : $this->get_input_property( $input, 'label' );
				$error_inputs[] = $label;
			}
		}

		if ( empty( $error_inputs ) ) {
			return false;
		}

		$field_list = implode( ', ', $error_inputs );

		// Translators: comma-separated list of the labels of missing fields.
		return sprintf( __( 'Please complete the following fields: %s.', 'gravityforms' ), $field_list );
	}

	/**
	 * Gets a property value from an input.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Name::validate()
	 * @uses    GFFormsModel::get_input()
	 *
	 * @param int    $input_id      The input ID to obtain the property from.
	 * @param string $property_name The property name to search for.
	 *
	 * @return null|string The property value if found. Otherwise, null.
	 */
	public function get_input_property( $input_id, $property_name ) {
		$input = GFFormsModel::get_input( $this, $this->id . '.' . (string) $input_id );

		return rgar( $input, $property_name );
	}

	/**
	 * Retrieve the field value on submission.
	 *
	 * @param array     $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool|true $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$inputs = $this->get_entry_inputs();

		if ( is_array( $inputs ) ) {
			$value = array();
			foreach ( $inputs as $input ) {
				$value[ strval( $input['id'] ) ] = $this->get_input_value_submission( 'input_' . str_replace( '.', '_', strval( $input['id'] ) ), RGForms::get( 'name', $input ), $field_values, $get_from_post_global_var );
			}
		} else {
			$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		}

		return $value;
	}

	/**
	 * Retrieve the input value on submission.
	 *
	 * @param string    $standard_name            The input name used when accessing the $_POST.
	 * @param string    $custom_name              The dynamic population parameter name.
	 * @param array     $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool|true $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @return array|string
	 */
	public function get_input_value_submission( $standard_name, $custom_name = '', $field_values = array(), $get_from_post_global_var = true ) {

		$form_id = $this->formId;
		if ( ! empty( $_POST[ 'is_submit_' . $form_id ] ) && $get_from_post_global_var ) {
			$value = rgpost( $standard_name );
			$value = GFFormsModel::maybe_trim_input( $value, $form_id, $this );

			return $value;
		} elseif ( $this->allowsPrepopulate ) {
			return GFFormsModel::get_parameter_value( $custom_name, $field_values, $this );
		}

	}


	// # ENTRY RELATED --------------------------------------------------------------------------------------------------

	/**
	 * Override and return null if a multi-input field value is to be stored under the field ID instead of the individual input IDs.
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		return $this->inputs;
	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object.
	 *
	 * @param string $value      The value to be saved.
	 * @param array  $form       The Form Object currently being processed.
	 * @param string $input_name The input name used when accessing the $_POST.
	 * @param int    $lead_id    The ID of the Entry currently being processed.
	 * @param array  $lead       The Entry Object currently being processed.
	 *
	 * @return array|string The safe value.
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		if ( rgblank( $value ) ) {

			return '';

		} elseif ( is_array( $value ) ) {

			foreach ( $value as &$v ) {

				if ( is_array( $v ) ) {
					$v = '';
				}

				$v = $this->sanitize_entry_value( $v, $form['id'] );

			}

			return implode( ',', $value );

		} else {

			return $this->sanitize_entry_value( $value, $form['id'] );

		}
	}

	/**
	 * Format the entry value for when the field/input merge tag is processed. Not called for the {all_fields} merge tag.
	 *
	 * Return a value that is safe for the context specified by $format.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $value      The field value. Depending on the location the merge tag is being used the following functions may have already been applied to the value: esc_html, nl2br, and urlencode.
	 * @param string       $input_id   The field or input ID from the merge tag currently being processed.
	 * @param array        $entry      The Entry Object currently being processed.
	 * @param array        $form       The Form Object currently being processed.
	 * @param string       $modifier   The merge tag modifier. e.g. value
	 * @param string|array $raw_value  The raw field value from before any formatting was applied to $value.
	 * @param bool         $url_encode Indicates if the urlencode function may have been applied to the $value.
	 * @param bool         $esc_html   Indicates if the esc_html function may have been applied to the $value.
	 * @param string       $format     The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param bool         $nl2br      Indicates if the nl2br function may have been applied to the $value.
	 *
	 * @return string
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {

		if ( $format === 'html' ) {
			$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : null;
			$allowable_tags = $this->get_allowable_tags( $form_id );

			if ( $allowable_tags === false ) {
				// The value is unsafe so encode the value.
				if ( is_array( $value ) ) {
					foreach ( $value as &$v ) {
						$v = esc_html( $v );
					}
					$return = $value;
				} else {
					$return = esc_html( $value );
				}
			} else {
				// The value contains HTML but the value was sanitized before saving.
				if ( is_array( $raw_value ) ) {
					$return = rgar( $raw_value, $input_id );
				} else {
					$return = $raw_value;
				}
			}

			if ( $nl2br ) {
				if ( is_array( $return ) ) {
					foreach ( $return as &$r ) {
						$r = nl2br( $r );
					}
				} else {
					$return = nl2br( $return );
				}
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value for display on the entries list page.
	 *
	 * Return a value that's safe to display on the page.
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The Entry Object currently being processed.
	 * @param string       $field_id The field or input ID currently being processed.
	 * @param array        $columns  The properties for the columns being displayed on the entry list page.
	 * @param array        $form     The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		$allowable_tags = $this->get_allowable_tags( $form['id'] );

		if ( $allowable_tags === false ) {
			// The value is unsafe so encode the value.
			$return = esc_html( $value );
		} else {
			// The value contains HTML but the value was sanitized before saving.
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * Return a value that's safe to display for the context of the given $format.
	 *
	 * @param string|array $value    The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		if ( is_array( $value ) ) {
			_doing_it_wrong( __METHOD__, 'Override this method to handle array values', '2.0' );
			return $value;
		}

		if ( $format === 'html' ) {
			$value = nl2br( $value );

			$allowable_tags = $this->get_allowable_tags();

			if ( $allowable_tags === false ) {
				// The value is unsafe so encode the value.
				$return = esc_html( $value );
			} else {
				// The value contains HTML but the value was sanitized before saving.
				$return = $value;
			}
		} else {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 *
	 * For CSV export return a string or array.
	 *
	 * @param array      $entry    The entry currently being processed.
	 * @param string     $input_id The field or input ID.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param bool|false $is_csv   Is the value going to be used in the .csv entries export?
	 *
	 * @return string|array
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		return rgar( $entry, $input_id );
	}

	/**
	 * Apply the gform_get_input_value filter to an entry's value.
	 *
	 * @since 2.4.24
	 *
	 * @param mixed  $value    The field or input value to be filtered.
	 * @param array  $entry    The entry currently being processed.
	 * @param string $input_id The ID of the input being processed from a multi-input field type or an empty string.
	 *
	 * @return mixed
	 */
	public function filter_input_value( $value, $entry, $input_id = '' ) {
		/**
		 * Allows the field or input value to be overridden when populating the entry (usually on retrieval from the database).
		 *
		 * @since 1.5.3
		 * @since 1.9.14 Added the form and field specific versions.
		 *
		 * @param mixed    $value    The field or input value to be filtered.
		 * @param array    $entry    The entry currently being processed.
		 * @param GF_Field $this     The field currently being processed.
		 * @param string   $input_id The ID of the input being processed from a multi-input field type or an empty string.
		 */
		return gf_apply_filters(
			array(
				'gform_get_input_value',
				$this->formId,
				$this->id,
			),
			$value,
			$entry,
			$this,
			$input_id
		);
	}

	/**
	 * Prepares the selected choice from the entry for output.
	 *
	 * @since 2.5.11
	 *
	 * @param string $value    The choice value from the entry.
	 * @param string $currency The entry currency code.
	 * @param bool   $use_text Indicates if the choice text should be returned instead of the choice value.
	 *
	 * @return string
	 */
	public function get_selected_choice_output( $value, $currency = '', $use_text = false ) {
		if ( is_array( $value ) ) {
			return '';
		}

		$price = '';

		if ( $this->enablePrice ) {
			$parts = explode( '|', $value );
			$value = $parts[0];

			if ( ! empty( $parts[1] ) ) {
				$price = GFCommon::to_money( $parts[1], $currency );
			}
		}

		$choice = $this->get_selected_choice( $value );

		if ( $use_text && ! empty( $choice['text'] ) ) {
			$value = $choice['text'];
		}

		if ( ! empty( $price ) ) {
			$value .= ' (' . $price . ')';
		}

		return empty( $choice ) ? wp_strip_all_tags( $value ) : wp_kses_post( $value );
	}

	/**
	 * Returns the choice array for the entry value.
	 *
	 * @since 2.5.11
	 *
	 * @param string $value The choice value from the entry.
	 *
	 * @return array
	 */
	public function get_selected_choice( $value ) {
		if ( rgblank( $value ) || is_array( $value ) || empty( $this->choices ) ) {
			return array();
		}

		foreach ( $this->choices as $choice ) {
			if ( GFFormsModel::choice_value_match( $this, $choice, $value ) ) {
				return $choice;
			}
		}

		return array();
	}


	// # INPUT ATTRIBUTE HELPERS ----------------------------------------------------------------------------------------

	/**
	 * Maybe return the input attribute which will trigger evaluation of conditional logic rules which depend on this field.
	 *
	 * @since 2.4
	 *
	 * @param string $event The event attribute which should be returned. Possible values: keyup, click, or change.
	 *
	 * @deprecated 2.4 Conditional Logic is now triggered based on .gfield class name. No need to hardcode calls to gf_apply_rules() to every field.
	 *
	 * @return string
	 */
	public function get_conditional_logic_event( $event ) {

		_deprecated_function( __CLASS__ . ':' . __METHOD__, '2.4' );

		if ( empty( $this->conditionalLogicFields ) || $this->is_entry_detail() || $this->is_form_editor() ) {
			return '';
		}

		switch ( $event ) {
			case 'keyup' :
				return "onchange='gf_apply_rules(" . $this->formId . ',' . GFCommon::json_encode( $this->conditionalLogicFields ) . ");' onkeyup='clearTimeout(__gf_timeout_handle); __gf_timeout_handle = setTimeout(\"gf_apply_rules(" . $this->formId . ',' . GFCommon::json_encode( $this->conditionalLogicFields ) . ")\", 300);'";
				break;

			case 'click' :
				return "onclick='gf_apply_rules(" . $this->formId . ',' . GFCommon::json_encode( $this->conditionalLogicFields ) . ");' onkeypress='gf_apply_rules(" . $this->formId . ',' . GFCommon::json_encode( $this->conditionalLogicFields ) . ");'";
				break;

			case 'change' :
				return "onchange='gf_apply_rules(" . $this->formId . ',' . GFCommon::json_encode( $this->conditionalLogicFields ) . ");'";
				break;
		}
	}

	/**
	 * Maybe return the tabindex attribute.
	 *
	 * @return string
	 */
	public function get_tabindex() {
		return GFCommon::$tab_index > 0 ? "tabindex='" . GFCommon::$tab_index ++ . "'" : '';
	}

	/**
	 * If the field placeholder property has a value return the input placeholder attribute.
	 *
	 * @return string
	 */
	public function get_field_placeholder_attribute() {

		$placeholder_value = $this->get_placeholder_value( $this->placeholder );

		return ! rgblank( $placeholder_value ) ? sprintf( "placeholder='%s'", esc_attr( $placeholder_value ) ) : '';
	}

	/**
	 * Process merge tags in the placeholder and return it.
	 *
	 * @since 2.5
	 *
	 * @param string $placeholder The placeholder value.
	 *
	 * @return string
	 */
	public function get_placeholder_value( $placeholder ) {

		$placeholder_value = GFCommon::replace_variables_prepopulate( $placeholder );

		return $placeholder_value;
	}

	/**
	 * If the input placeholder property has a value return the input placeholder attribute.
	 *
	 * @param array $input The input currently being processed.
	 *
	 * @return string
	 */
	public function get_input_placeholder_attribute( $input ) {

		$placeholder_value = $this->get_input_placeholder_value( $input );

		return ! rgblank( $placeholder_value ) ? sprintf( "placeholder='%s'", esc_attr( $placeholder_value ) ) : '';
	}

	/**
	 * If configured retrieve the input placeholder value.
	 *
	 * @param array $input The input currently being processed.
	 *
	 * @return string
	 */
	public function get_input_placeholder_value( $input ) {

		$placeholder = rgar( $input, 'placeholder' );

		return rgblank( $placeholder ) ? '' : $this->get_placeholder_value( $placeholder );
	}

	/**
	 * Return the custom label for an input.
	 *
	 * @since 2.5
	 *
	 * @param array $input The input object.
	 *
	 * @return string
	 */
	public function get_input_label( $input ) {

		$custom_label = rgar( $input, 'customLabel' );

		return ( $custom_label !== '' ) ? esc_html( $custom_label ) : '';

	}

	/**
	 * Get the input label classes. When no custom label and placeholder for an input, we apply the
	 * `screen-reader-text` class to the label.
	 *
	 * @since 2.5
	 *
	 * @param array  $input       The input object.
	 * @param array $label_class The label classes.
	 *
	 * @return string
	 */
	public function get_input_label_class( $input, $label_class ) {

		if ( rgar( $input, 'customLabel' ) === '' && rgar( $input, 'placeholder' ) === '' ) {
			if ( ! in_array( 'screen-reader-text', $label_class, true ) ) {
				$label_class[] = 'screen-reader-text';
			}
		}

		return implode( ' ', $label_class );

	}


	// # BOOLEAN HELPERS ------------------------------------------------------------------------------------------------

	/**
	 * Determine if the current location is the form editor.
	 *
	 * @return bool
	 */
	public function is_form_editor() {
		return GFCommon::is_form_editor();
	}

	/**
	 * Determine if the current location is the entry detail page.
	 *
	 * @return bool
	 */
	public function is_entry_detail() {
		return isset( $this->_is_entry_detail ) ? (bool) $this->_is_entry_detail : GFCommon::is_entry_detail();
	}

	/**
	 * Determine if the current location is the edit entry page.
	 *
	 * @return bool
	 */
	public function is_entry_detail_edit() {
		return GFCommon::is_entry_detail_edit();
	}

	/**
	 * Is this a calculated product field or a number field with a calculation enabled and formula configured.
	 *
	 * @return bool
	 */
	public function has_calculation() {

		$type = $this->get_input_type();

		if ( $type == 'number' ) {
			if ( $this->calculationFormula ) {
				$ids = GFCommon::get_field_ids_from_formula_tag( $this->calculationFormula );

				if ( in_array( $this->id, $ids ) ) {
					return false;
				}
			}

			return $this->enableCalculation && $this->calculationFormula;
		}

		return $type == 'calculation';
	}

	/**
	 * Determines if the field description should be positioned above or below the input.
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return bool
	 */
	public function is_description_above( $form ) {
		$form_label_placement        = rgar( $form, 'labelPlacement' );
		$field_label_placement       = $this->labelPlacement;
		$form_description_placement  = rgar( $form, 'descriptionPlacement' );
		$field_description_placement = $this->descriptionPlacement;
		if ( empty( $field_description_placement ) ) {
			$field_description_placement = $form_description_placement;
		}
		$is_description_above = $field_description_placement == 'above' && ( $field_label_placement == 'top_label' || $field_label_placement == 'hidden_label' || ( empty( $field_label_placement ) && $form_label_placement == 'top_label' ) );

		return $is_description_above;
	}


	public function is_administrative() {
		return $this->visibility == 'administrative';
	}


	// # OTHER HELPERS --------------------------------------------------------------------------------------------------

	/**
	 * Store the modifiers so they can be accessed in get_value_entry_detail() when preparing the content for the {all_fields} output.
	 *
	 * @param array $modifiers An array of modifiers to be stored.
	 */
	public function set_modifiers( $modifiers ) {

		$this->_merge_tag_modifiers = $modifiers;
	}

	/**
	 * Retrieve the merge tag modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {

		return $this->_merge_tag_modifiers;
	}

	/**
	 * Retrieves the field input type.
	 *
	 * @return string
	 */
	public function get_input_type() {

		return empty( $this->inputType ) ? $this->type : $this->inputType;
	}

	/**
	 * Adds the field button to the specified group.
	 *
	 * @param array $field_groups
	 *
	 * @return array
	 */
	public function add_button( $field_groups ) {

		// Check a button for the type hasn't already been added
		foreach ( $field_groups as &$group ) {
			foreach ( $group['fields'] as &$button ) {
				if ( isset( $button['data-type'] ) && $button['data-type'] == $this->type ) {
					$button['data-icon'] = $this->get_form_editor_field_icon();
					$button['data-description'] = $this->get_form_editor_field_description();
					return $field_groups;
				}
			}
		}


		$new_button = $this->get_form_editor_button();
		if ( ! empty( $new_button ) ) {
			foreach ( $field_groups as &$group ) {
				if ( $group['name'] == $new_button['group'] ) {
					$group['fields'][] = array(
						'value'      =>  $new_button['text'],
						'data-icon'       =>  empty($new_button['icon']) ? $this->get_form_editor_field_icon() : $new_button['icon'],
						'data-description' => empty($new_button['description']) ? $this->get_form_editor_field_description() : $new_button['description'],
						'data-type'  => $this->type,
						'onclick'    => "StartAddField('{$this->type}');",
						'onkeypress' => "StartAddField('{$this->type}');",
					);
					break;
				}
			}
		}

		return $field_groups;
	}

	/**
	 * Returns the field admin buttons for display in the form editor.
	 *
	 * @return string
	 */
	public function get_admin_buttons() {

		if ( ! $this->is_form_editor() ) {
			return '';
		}

		$duplicate_disabled   = array(
			'captcha',
			'post_title',
			'post_content',
			'post_excerpt',
			'total',
			'shipping',
			'creditcard',
			'submit',
		);
		$duplicate_field_link = '';
		if(  ! in_array( $this->type, $duplicate_disabled ) ) {
			$duplicate_aria_action = __( 'duplicate this field', 'gravityforms' );
			$duplicate_field_link = "
				<button 
					id='gfield_duplicate_{$this->id}' 
					class='gfield-field-action gfield-duplicate' 
					onclick='StartDuplicateField(this); return false;' 
					onkeypress='StartDuplicateField(this); return false;'
					aria-label='" . esc_html( $this->get_field_action_aria_label( $duplicate_aria_action ) ) . "'
				>
					<svg width='25' height='25'  role='presentation' focusable='false'  fill='none' xmlns='http://www.w3.org/2000/svg'>
						<path class='stroke' d='M6 4.75h14c.69 0 1.25.56 1.25 1.25v14c0 .69-.56 1.25-1.25 1.25H6c-.69 0-1.25-.56-1.25-1.25V6c0-.69.56-1.25 1.25-1.25z' stroke='#242748' stroke-width='1.5'/>
						<path class='stroke fill' d='M10 5L6 9.5V5h4z' fill='#242748' stroke='#242748'/>
						<path class='stroke' d='M9 13h8M13 9v8' stroke='#242748' stroke-width='1.5'/>
						<rect class='fill' x='.254' y='15.027' width='7' height='1.492' rx='.746' transform='rotate(-90 .254 15.027)' fill='#242748'/>
						<path class='stroke' d='M1 14V4c0-1.657 1.34-3 2.997-3H16' stroke='#242748' stroke-width='1.5'/>
					</svg>
					<span class='gfield-field-action__description' aria-hidden='true'>" . esc_html__( 'Duplicate', 'gravityforms' ) . "</span>
				</button>";
		}

		/**
		 * This filter allows for modification of the form field duplicate link. This will change the link for all fields
		 *
		 * @param string $duplicate_field_link The Duplicate Field Link (in HTML)
		 */
		$duplicate_field_link = apply_filters( 'gform_duplicate_field_link', $duplicate_field_link );

		$delete_aria_action = __( 'delete this field', 'gravityforms' );
		$delete_field_link = "
			<button 
				id='gfield_delete_{$this->id}' 
				class='gfield-field-action gfield-delete' 
				onclick='DeleteField(this);' 
				onkeypress='DeleteField(this); return false;'
				aria-label='" . esc_html( $this->get_field_action_aria_label( $delete_aria_action ) ) . "'
			>
				<i class='gform-icon gform-icon--trash'></i>
				<span class='gfield-field-action__description' aria-hidden='true'>" . esc_html__( 'Delete', 'gravityforms' ) . "</span>
			</button>";

		if( 'submit' == $this->type ) {
			$delete_field_link = '';
		}

		/**
		 * This filter allows for modification of a form field delete link. This will change the link for all fields
		 *
		 * @param string $delete_field_link The Delete Field Link (in HTML)
		 */
		$delete_field_link = apply_filters( 'gform_delete_field_link', $delete_field_link );

		$edit_aria_action = __( 'jump to this field\'s settings', 'gravityforms' );
		$edit_field_link = "
			<button 
				id='gfield_edit_{$this->id}' 
				class='gfield-field-action gfield-edit'
				onclick='EditField(this);' 
				onkeypress='EditField(this); return false;'
				aria-label='" . esc_html( $this->get_field_action_aria_label( $edit_aria_action ) ) . "'
			>
				<i class='gform-icon gform-icon--settings'></i>
				<span class='gfield-field-action__description' aria-hidden='true'>" . esc_html__( 'Settings', 'gravityforms' ) . "</span>
			</button>";

		/**
		 * This filter allows for modification of a form field edit link. This will change the link for all fields
		 *
		 * @param string $edit_field_link The Edit Field Link (in HTML)
		 */
		$edit_field_link = apply_filters( 'gform_edit_field_link', $edit_field_link );

		$drag_handle = '
			<span class="gfield-field-action gfield-drag">
				<i class="gform-icon gform-icon--drag-indicator"></i>
				<span class="gfield-field-action__description">' . esc_html__( 'Move', 'gravityforms' ) . '</span>
			</span>';

		if( 'submit' == $this->type ) {
			$drag_handle = '';
		}

		$field_icon = '<span class="gfield-field-action gfield-icon">' . GFCommon::get_icon_markup( array( 'icon' => $this->get_form_editor_field_icon() ) ) . '</span>';

		$admin_buttons = "
			<div class='gfield-admin-icons'>
				{$drag_handle}
				{$duplicate_field_link}
				{$edit_field_link}
				{$delete_field_link}
				{$field_icon}
			</div>";

		return $admin_buttons;
	}

	/**
	 * Get the text that indicates a field is required.
	 *
	 * @since 2.5
	 *
	 * @return string HTML for required indicator.
	 */
	public function get_required_indicator() {
		return GFFormsModel::get_required_indicator( $this->formId );
	}

	/**
	 * Get markup to show that the field is hidden in the form editor
	 *
	 * @since 2.5
	 *
	 * @return string HTML for required indicator.
	 */
	public function get_hidden_admin_markup() {

		 return "<div class='admin-hidden-markup'><i class='gform-icon gform-icon--hidden'></i><span>Hidden</span></div>";

	}

	/**
	 * Retrieve the field label.
	 *
	 * @since unknown
	 * @since 2.5     Move conditions about the singleproduct and calculation fields to their own class.
	 *
	 * @param bool   $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param string $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 *
	 * @return string
	 */
	public function get_field_label( $force_frontend_label, $value ) {
		$label = $force_frontend_label ? $this->label : GFCommon::get_label( $this );

		if ( '' === $label ) {
			if ( '' !== rgar( $this, 'placeholder' ) ) {
				$label = $this->get_placeholder_value( $this->placeholder );
			} elseif ( '' !== $this->description ) {
				$label = wp_strip_all_tags( $this->description );
			}
		}

		return $label;
	}

	/**
	 * Returns the input ID to be assigned to the field label for attribute.
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_first_input_id( $form ) {
		$form_id = (int) rgar( $form, 'id' );

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$field_id        = $is_entry_detail || $is_form_editor || $form_id == 0 ? 'input_' : "input_{$form_id}_";

		if ( is_array( $this->inputs ) ) {
			foreach ( $this->inputs as $input ) {
				// Validate if input id is in x.x format.
				if ( ! is_numeric( $input['id'] ) ) {
					break;
				}

				if ( ! isset( $input['isHidden'] ) || ! $input['isHidden'] ) {
					$field_id .= str_replace( '.', '_', $input['id'] );
					break;
				}
			}
		} else {
			$field_id .= $this->id;
		}

		// The value is used as an HTML attribute, escape it.
		return esc_attr( $field_id );
	}

	/**
	 * Set the aria-describedby attribute for an input if it is the first input in a fieldset
	 *
	 * Since 2.5
	 *
	 * @param array  $input    The current input.
	 * @param string $field_id The ID of the field we're working with.
	 * @param int    $form_id  The ID of the form object.
	 *
	 * @return string The aria-describedby text or a blank string.
	 */
	public function maybe_add_aria_describedby( $input, $field_id, $form_id ) {
		$first_input_for_field = $this->get_first_input_id( GFFormsModel::get_form_meta( $form_id ) );
		$field_id_as_array     = explode( '_', $field_id );
		$first_input_as_array  = explode( '_', $first_input_for_field );
		$subelement_id         = end( $field_id_as_array ) . '.' . end( $first_input_as_array );

		if ( $input['id'] === $subelement_id ) {
			return $this->get_aria_describedby();
		}

		return '';
	}

	/**
	 * Get the autocomplete attribute for the field.
	 *
	 * @since 2.5
	 *
	 * @return string|void $autocomplete The autocomplete attribute for the field.
	 */
	public function get_field_autocomplete_attribute() {

		if ( $this->enableAutocomplete && ! rgblank( $this->autocompleteAttribute ) ) {
			return 'autocomplete="' . $this->parse_autocomplete_attributes( $this->autocompleteAttribute ) . '"';
		} else {
			return;
		}

	}

	/**
	 * If the input autocomplete property has a value return the input autocomplete attribute.
	 *
	 * @since 2.5
	 *
	 * @param array $input The input currently being processed.
	 *
	 * @return string|void $autocomplete The autocomplete attribute for the input.
	 */
	public function get_input_autocomplete_attribute( $input ) {

		if ( ! $this->enableAutocomplete ) {
			return;
		}

		if ( rgar( $input, 'autocompleteAttribute' ) && ! rgblank( $input['autocompleteAttribute'] ) ) {
			return 'autocomplete="' . $this->parse_autocomplete_attributes( $input['autocompleteAttribute'] ) . '"';
		} else {
			return;
		}
	}

	/**
	 * Parse a comma-separated list of autocomplete attributes.
	 *
	 * In case the user has put commas in between multiple autocomplete attributes, remove the commas.
	 *
	 * @since 2.5
	 *
	 * @param string $attributes
	 *
	 * @return string List of attributes separated by a space.
	 */
	public function parse_autocomplete_attributes( $attributes ) {
		$list = explode( ',', $attributes );
		return implode( '', $list );
	}


	/**
	 * Returns the markup for the field description.
	 *
	 * @param string $description The field description.
	 * @param string $css_class   The css class to be assigned to the description container.
	 *
	 * @return string
	 */
	public function get_description( $description, $css_class ) {
		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;
		$id              = "gfield_description_{$this->formId}_{$this->id}";

		// Strip description tags when on edit page to avoid invalid markup breaking the editor.
		if ( $this->is_form_editor() ) {
			$description = strip_tags( $description );
		}

		return $is_admin || ! empty( $description ) ? "<div class='$css_class' id='$id'>" . $description . '</div>' : '';
	}

	/**
	 * If a field has a description, the aria-describedby attribute for the input field is returned.
	 *
	 * @since unknown
	 * @since 2.5 Add new param $extra_ids.
	 *
	 * @param array|string $extra_ids Any extra ids that should be added to the describedby attribute.
	 *
	 * @return string
	 */
	public function get_aria_describedby( $extra_ids = array() ) {

		$describedby_ids = is_array( $extra_ids ) ? $extra_ids : explode(' ', $extra_ids );

		if ( $this->failed_validation ) {
			$describedby_ids[] = "validation_message_{$this->formId}_{$this->id}";
		}

		if ( ! empty( $this->description ) ) {
			$describedby_ids[] = "gfield_description_{$this->formId}_{$this->id}";
		}

		if ( empty( $describedby_ids ) ) {
			return '';
		}

		return 'aria-describedby="' . implode( ' ', $describedby_ids ) . '"';

	}


	/**
	 * Generates aria-describedby, aria-invalid and aria-required attributes for field inputs.
	 *
	 * @since 2.5
	 *
	 * @param array|string $values   The inputs values.
	 * @param string       $input_id The specific input ID we'd like to get the values from.
	 *
	 * @return string|array          Return the attributes as a string if an input ID is given; otherwise return an array.
	 */
	public function get_aria_attributes( $values, $input_id = '' ) {

		$required_inputs_ids = $this->get_required_inputs_ids();

		$describedby = $this->get_inputs_describedby_attributes( $required_inputs_ids, $values );
		$invalid     = $this->get_inputs_invalid_attributes( $required_inputs_ids, $values );
		$required    = $this->get_inputs_required_attributes( $required_inputs_ids );

		if ( empty( $input_id ) ) {
			return compact( 'describedby', 'invalid', 'required' );
		}

		$required    = empty( $required[ $input_id ] ) ? '' : $required[ $input_id ];
		$invalid     = empty( $invalid[ $input_id ] ) ? '' : $invalid[ $input_id ];
		$describedby = empty( $describedby[ $input_id ] ) ? '' : $describedby[ $input_id ];

		return "{$required} {$invalid} {$describedby}";
	}


	/**
	 * Whether this field has been submitted,
	 * is on the current page of a multi-page form,
	 * or is required and should be validated.
	 *
	 * @since 2.5.7
	 *
	 * @return bool
	 */
	public function should_be_validated() {
		if ( empty( rgpost( 'is_submit_' . $this->formId ) ) ) {
			return false;
		}

		if ( GFFormDisplay::get_source_page( $this->formId ) != $this->pageNumber ) {
			return false;
		}

		if ( ! $this->isRequired ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if this field will be processed by the state validation.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function is_state_validation_supported() {
		return $this->_supports_state_validation && $this->validateState && ! $this->allowsPrepopulate;
	}

	/**
	 * Generates an array that contains aria-describedby attribute for each input.
	 *
	 * Depending on each input's validation state, aria-describedby takes the value of the validation message container ID, the description only or nothing.
	 *
	 * @since 2.5
	 *
	 * @param array        $required_inputs_ids IDs of required field inputs.
	 * @param array|string $values              Inputs values.
	 *
	 * @return array
	 */
	public function get_inputs_describedby_attributes( $required_inputs_ids, $values ) {

		if ( ! is_array( $this->inputs ) || empty( $this->inputs ) ) {
			return array();
		}

		$describedby_attributes = array();
		foreach ( $this->inputs as $input ) {
			$input_id = str_replace( $this->id . '.', '', $input['id'] );
			$describedby_attributes[ $input_id ] = '';
		}

		if ( ! $this::should_be_validated() ) {
			return $describedby_attributes;
		}

		foreach ( $this->inputs as $input ) {
			$input_id = str_replace( $this->id . '.', '', $input['id'] );
			$input_value = GFForms::get( $input['id'], $values );
			if ( in_array( $input_id, $required_inputs_ids ) &&  empty( $input_value ) ) {
				$describedby_attributes[ $input_id ] = "aria-describedby='validation_message_{$this->formId}_{$this->id}'";
			}
		}

		return $describedby_attributes;
	}

	/**
	 * Generates an array that contains aria-required attributes for each input.
	 *
	 * @since 2.5
	 *
	 * @param array $required_inputs_ids IDs of required field inputs.
	 *
	 * @return array
	 */
	public function get_inputs_required_attributes( $required_inputs_ids ) {

		if ( ! is_array( $this->inputs ) || empty( $this->inputs ) ) {
			return array();
		}

		$required_attributes = array();

		foreach ( $this->inputs as $input ) {
			$input_id = str_replace( $this->id . '.', '', $input['id'] );
			if ( in_array( $input_id, $required_inputs_ids ) && $this->isRequired ) {
				$required_attributes[ $input_id ] = "aria-required='true'";
			} else {
				$required_attributes[ $input_id ] = "aria-required='false'";
			}
		}

		return $required_attributes;
	}

	/**
	 * Generates an array that contains aria-invalid attributes for each input.
	 *
	 * @since 2.5
	 *
	 * @param array        $required_inputs_ids IDs of required field inputs.
	 * @param array|string $values              Inputs values.
	 *
	 * @return array
	 */
	public function get_inputs_invalid_attributes( $required_inputs_ids, $values ) {

		if ( ! is_array( $this->inputs ) || empty( $this->inputs ) ) {
			return array();
		}

		$invalid_attributes = array();
		foreach ( $this->inputs as $input ) {
			$input_id = str_replace( $this->id . '.', '', $input['id'] );
			$invalid_attributes[ $input_id ] = '';
		}

		if ( ! $this::should_be_validated() ) {
			return $invalid_attributes;
		}

		foreach ( $this->inputs as $input ) {
			$input_id    = str_replace( $this->id . '.', '', $input['id'] );
			$input_value = GFForms::get( $input['id'], $values );
			$is_valid    = $this->is_input_valid( $input['id'] );

			if ( ! $is_valid || ( in_array( $input_id, $required_inputs_ids ) && empty( $input_value ) ) ) {
				$invalid_attributes[ $input_id ] = "aria-invalid='true'";
			} else {
				$invalid_attributes[ $input_id ] = "aria-invalid='false'";
			}
		}

		return $invalid_attributes;
	}

	/**
	 * Returns the field default value if the field does not already have a value.
	 *
	 * @param array|string $value The field value.
	 *
	 * @return array|string
	 */
	public function get_value_default_if_empty( $value ) {

		if ( is_array( $this->inputs ) && is_array( $value ) ) {
			$defaults = $this->get_value_default();
			foreach( $value as $index => &$input_value ) {
				if ( rgblank( $input_value ) ) {
					$input_value = rgar( $defaults, $index );
				}
			}
		}

		if ( ! GFCommon::is_empty_array( $value ) ) {
			return $value;
		}

		return $this->get_value_default();
	}

	/**
	 * Retrieve the field default value.
	 *
	 * @return array|string
	 */
	public function get_value_default() {
		if ( ! is_array( $this->inputs ) ) {
			$default_value = $this->maybe_convert_choice_text_to_value( $this->defaultValue );

			return $this->is_form_editor() ? $default_value : GFCommon::replace_variables_prepopulate( $default_value );
		}

		$value = array();

		foreach ( $this->inputs as $input ) {
			$default_value = $this->maybe_convert_choice_text_to_value( rgar( $input, 'defaultValue' ) );

			$value[ strval( $input['id'] ) ] = $this->is_form_editor() ? $default_value : GFCommon::replace_variables_prepopulate( $default_value );
		}

		return $value;
	}

	/**
	 * Converts the default choice text to its corresponding value.
	 *
	 * For fields like dropdown, the user can enter the choice text or the choice value as the default value but we
	 * should be always using the value for the default choice.
	 *
	 * If there are no choices or the value is already set as the default choice, this method returns the value. Otherwise,
	 * it will return the value for any matching text choice it finds.
	 *
	 * @since 2.5
	 *
	 * @param string $value The default value.
	 *
	 * @return string The choice value.
	 */
	protected function maybe_convert_choice_text_to_value( $value ) {
		if (
			! is_array( $this->choices )
			|| in_array( $value, array_column( $this->choices, 'value' ) )
		) {
			return $value;
		}

		foreach ( $this->choices as $choice ) {
			if ( $choice['text'] === $value ) {
				return $choice['value'];
			}
		}

		return $value;
	}

	/**
	 * Get the appropriate CSS Grid class for the column span of the field.
	 *
	 * @since 2.5
	 * @since 2.6 Added $form parameter
	 *
	 * @param array $form
	 * @return string
	 */
	public function get_css_grid_class( $form = '' ) {
		switch ( $this->layoutGridColumnSpan ) {
			case 12:
				$class = 'gfield--width-full';
				break;
			case 11:
				$class = 'gfield--width-eleven-twelfths';
				break;
			case 10:
				$class = 'gfield--width-five-sixths';
				break;
			case 9:
				$class = 'gfield--width-three-quarter';
				break;
			case 8:
				$class = 'gfield--width-two-thirds';
				break;
			case 7:
				$class = 'gfield--width-seven-twelfths';
				break;
			case 6:
				$class = 'gfield--width-half';
				break;
			case 5:
				$class = 'gfield--width-five-twelfths';
				break;
			case 4:
				$class = 'gfield--width-third';
				break;
			case 3:
				$class = 'gfield--width-quarter';
				break;
			default:
				$class = '';
				break;
		}

		return $class;
	}

	/**
	 * Registers the script returned by get_form_inline_script_on_page_render() for display on the front-end.
	 *
	 * @param array $form The Form Object currently being processed.
	 */
	public function register_form_init_scripts( $form ) {
		GFFormDisplay::add_init_script( $form['id'], $this->type . '_' . $this->id, GFFormDisplay::ON_PAGE_RENDER, $this->get_form_inline_script_on_page_render( $form ) );
	}

	// # SANITIZATION ---------------------------------------------------------------------------------------------------

	/**
	 * Strip unsafe tags from the field value.
	 *
	 * @param string $string The field value to be processed.
	 *
	 * @return string
	 */
	public function strip_script_tag( $string ) {
		$allowable_tags = '<a><abbr><acronym><address><area><area /><b><base><base /><bdo><big><blockquote><body><br><br /><button><caption><cite><code><col><col /><colgroup><command><command /><dd><del><dfn><div><dl><DOCTYPE><dt><em><fieldset><form><h1><h2><h3><h4><h5><h6><head><html><hr><hr /><i><img><img /><input><input /><ins><kbd><label><legend><li><link><map><meta><meta /><noscript><ol><optgroup><option><p><param><param /><pre><q><samp><select><small><span><strong><style><sub><sup><table><tbody><td><textarea><tfoot><th><thead><title><tr><tt><ul><var><wbr><wbr />';

		$string = strip_tags( $string, $allowable_tags );

		return $string;
	}

	/**
	 * Override this if the field should allow html tags to be saved with the entry value. Default is false.
	 *
	 * @return bool
	 */
	public function allow_html() {

		return false;
	}

	/**
	 * Fields should override this method to implement the appropriate sanitization specific to the field type before the value is saved.
	 *
	 * This base method will only strip HTML tags if the field or the gform_allowable_tags filter allows HTML.
	 *
	 * @param string $value   The field value to be processed.
	 * @param int    $form_id The ID of the form currently being processed.
	 *
	 * @return string
	 */
	public function sanitize_entry_value( $value, $form_id ) {

		if ( is_array( $value ) ) {
			return '';
		}

		$allowable_tags = $this->get_allowable_tags( $form_id );

		if ( $allowable_tags === true ) {

			// HTML is expected. Output will not be encoded so the value will stripped of scripts and some tags and encoded.
			$return = wp_kses_post( $value );

		} elseif ( $allowable_tags === false ) {

			// HTML is not expected. Output will be encoded.
			$return = $value;

		} else {

			// Some HTML is expected. Output will not be encoded so the value will stripped of scripts and some tags and encoded.
			$value = wp_kses_post( $value );

			// Strip all tags except those allowed by the gform_allowable_tags filter.
			$return = strip_tags( $value, $allowable_tags );
		}

		return $return;
	}

	/**
	 * Forces settings into expected values while saving the form object.
	 *
	 * No escaping should be done at this stage to prevent double escaping on output.
	 *
	 * Currently called only for forms created after version 1.9.6.10.
	 *
	 */
	public function sanitize_settings() {
		$this->id     = absint( $this->id );
		$this->type   = wp_strip_all_tags( $this->type );
		$this->formId = absint( $this->formId );

		$this->label       = $this->maybe_wp_kses( $this->label );
		$this->adminLabel  = $this->maybe_wp_kses( $this->adminLabel );
		$this->description = $this->maybe_wp_kses( $this->description );

		$this->isRequired = (bool) $this->isRequired;

		if ( isset( $this->validateState ) ) {
			$this->validateState = (bool) $this->validateState;
		}

		$this->allowsPrepopulate = (bool) $this->allowsPrepopulate;

		$this->inputMask      = (bool) $this->inputMask;
		$this->inputMaskValue = wp_strip_all_tags( $this->inputMaskValue );

		if ( $this->inputMaskIsCustom !== '' ) {
			$this->inputMaskIsCustom = (bool) $this->inputMaskIsCustom;
		}

		if ( $this->maxLength ) {
			$this->maxLength = absint( $this->maxLength );
		}

		if ( $this->inputType ) {
			$this->inputType = wp_strip_all_tags( $this->inputType );
		}

		if ( $this->size ) {
			$this->size = GFCommon::whitelist( $this->size, $this->get_size_choices( true ) );
		}

		if ( $this->errorMessage ) {
			$this->errorMessage = sanitize_text_field( $this->errorMessage );
		}

		if ( $this->labelPlacement ) {
			$this->labelPlacement = wp_strip_all_tags( $this->labelPlacement );
		}

		if ( $this->descriptionPlacement ) {
			$this->descriptionPlacement = wp_strip_all_tags( $this->descriptionPlacement );
		}

		if ( $this->subLabelPlacement ) {
			$this->subLabelPlacement = wp_strip_all_tags( $this->subLabelPlacement );
		}

		if ( $this->placeholder ) {
			$this->placeholder = sanitize_text_field( $this->placeholder );
		}

		if ( $this->cssClass ) {
			$this->cssClass = wp_strip_all_tags( $this->cssClass );
		}

		if ( $this->inputName ) {
			$this->inputName = wp_strip_all_tags( $this->inputName );
		}

		$this->visibility = wp_strip_all_tags( $this->visibility );
		$this->noDuplicates = (bool) $this->noDuplicates;

		if ( $this->defaultValue ) {
			$this->defaultValue = $this->maybe_wp_kses( $this->defaultValue );
		}

		$this->enableAutocomplete = (bool) $this->enableAutocomplete;
		$this->autocompleteAttribute = $this->sanitize_autocomplete_attributes( $this->autocompleteAttribute );

		if ( is_array( $this->inputs ) ) {
			foreach ( $this->inputs as &$input ) {
				if ( isset( $input['id'] ) ) {
					$input['id'] = wp_strip_all_tags( $input['id'] );
				}
				if ( isset( $input['customLabel'] ) ) {
					$input['customLabel'] = $this->maybe_wp_kses( $input['customLabel'] );
				}
				if ( isset( $input['label'] ) ) {
					$input['label'] = $this->maybe_wp_kses( $input['label'] );
				}
				if ( isset( $input['name'] ) ) {
					$input['name'] = wp_strip_all_tags( $input['name'] );
				}

				if ( isset( $input['placeholder'] ) ) {
					$input['placeholder'] = sanitize_text_field( $input['placeholder'] );
				}

				if ( isset( $input['defaultValue'] ) ) {
					$input['defaultValue'] = wp_strip_all_tags( $input['defaultValue'] );
				}

				if ( isset( $input['autocompleteAttribute'] ) ) {
					$input['autocompleteAttribute'] = $this->sanitize_autocomplete_attributes( $input['autocompleteAttribute'] );
				}
			}
		}

		$this->sanitize_settings_choices();
		$this->sanitize_settings_conditional_logic();

	}

	/**
	 * Sanitize the field choices property.
	 *
	 * @param array|null $choices The field choices property.
	 *
	 * @return array|null
	 */
	public function sanitize_settings_choices( $choices = null ) {

		if ( is_null( $choices ) ) {
			$choices = &$this->choices;
		}

		if ( ! is_array( $choices ) ) {
			return $choices;
		}

		foreach ( $choices as &$choice ) {
			if ( isset( $choice['isSelected'] ) ) {
				$choice['isSelected'] = (bool) $choice['isSelected'];
			}

			if ( isset( $choice['price'] ) && ! empty( $choice['price'] ) ) {
				$price_number    = GFCommon::to_number( $choice['price'] );
				$choice['price'] = GFCommon::to_money( $price_number );
			}

			if ( isset( $choice['text'] ) ) {
				$choice['text'] = $this->maybe_wp_kses( $choice['text'] );
			}

			if ( isset( $choice['value'] ) ) {
				// Strip scripts but don't encode
				$allowed_protocols = wp_allowed_protocols();
				$choice['value']   = wp_kses_no_null( $choice['value'], array( 'slash_zero' => 'keep' ) );
				$choice['value']   = wp_kses_hook( $choice['value'], 'post', $allowed_protocols );
				$choice['value']   = wp_kses_split( $choice['value'], 'post', $allowed_protocols );
			}
		}

		return $choices;
	}

	/**
	 * Sanitize the field conditional logic object.
	 *
	 * @param array|null $logic The field conditional logic object.
	 *
	 * @return array|null
	 */
	public function sanitize_settings_conditional_logic( $logic = null ) {

		if ( is_null( $logic ) ) {
			$logic = &$this->conditionalLogic;
		}
		$logic = GFFormsModel::sanitize_conditional_logic( $logic );

		return $logic;
	}

	/**
	 * Sanitize autocomplete attributes by checking them against whitelist.
	 *
	 * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofill-field
	 *
	 * @since 2.5
	 *
	 * @param string $autocomplete The user-entered autocomplete attributes in a comma-separated list.
	 *
	 * @return string Comma-separated list of acceptable attributes.
	 */
	public function sanitize_autocomplete_attributes( $autocomplete ) {
		$attributes_array    = explode( ' ', $autocomplete );
		$sanitized_array     = array();
		$accepted_attributes = array(
			'name',
			'honorific-prefix',
			'given-name',
			'additional-name',
			'family-name',
			'honorific-suffix',
			'nickname',
			'organization-title',
			'username',
			'new-password',
			'current-password',
			'one-time-code',
			'organization',
			'street-address',
			'address-line1',
			'address-line2',
			'address-line3',
			'address-level4',
			'address-level3',
			'address-level2',
			'address-level1',
			'country',
			'country-name',
			'postal-code',
			'transaction-currency',
			'transaction-amount',
			'language',
			'bday',
			'bday-day',
			'bday-month',
			'bday-year',
			'sex',
			'url',
			'photo',
			'tel',
			'tel-country-code',
			'tel-national',
			'tel-area-code',
			'tel-local',
			'tel-local-prefix',
			'tel-local-suffix',
			'tel-extension',
			'email',
			'impp',
			'off',
		);

		foreach ( $attributes_array as $attribute ) {
			if ( in_array( trim( $attribute ), $accepted_attributes ) ) {
				$sanitized_array[] = trim( $attribute );
			}
		}

		return implode( ' ', $sanitized_array );
	}

	/**
	 * Applies wp_kses() if the current user doesn't have the unfiltered_html capability
	 *
	 * @param $html
	 * @param string $allowed_html
	 * @param array  $allowed_protocols
	 *
	 * @return string
	 */
	public function maybe_wp_kses( $html, $allowed_html = 'post', $allowed_protocols = array() ) {
		return GFCommon::maybe_wp_kses( $html, $allowed_html, $allowed_protocols );
	}

	/**
	 * Returns the allowed HTML tags for the field value.
	 *
	 * FALSE disallows HTML tags.
	 * TRUE allows all HTML tags allowed by wp_kses_post().
	 * A string of HTML tags allowed. e.g. '<p><a><strong><em>'
	 *
	 * @param null|int $form_id If not specified the form_id field property is used.
	 *
	 * @return bool|string TRUE, FALSE or a string of tags.
	 */
	public function get_allowable_tags( $form_id = null ) {
		if ( empty( $form_id ) ) {
			$form_id = $this->form_id;
		}
		$form_id    = absint( $form_id );
		$allow_html = $this->allow_html();

		/**
		 * Allows the list of tags allowed in the field value to be modified.
		 *
		 * Return FALSE to disallow HTML tags.
		 * Return TRUE to allow all HTML tags allowed by wp_kses_post().
		 * Return a string of HTML tags allowed. e.g. '<p><a><strong><em>'
		 *
		 * @since Unknown
		 *
		 * @param bool     $allow_html
		 * @param GF_Field $this
		 * @param int      $form_id
		 */
		$allowable_tags = apply_filters( 'gform_allowable_tags', $allow_html, $this, $form_id );
		$allowable_tags = apply_filters( "gform_allowable_tags_{$form_id}", $allowable_tags, $this, $form_id );

		return $allowable_tags;
	}

	/**
	 * Actions to be performed after the field has been converted to an object.
	 *
	 * @since 2.1.3  Clear any field validation errors that have been saved to the form in the database.
	 * @since 2.5.11 Set validateState property for back-compat.
	 */
	public function post_convert_field() {
		unset( $this->failed_validation );
		unset( $this->validation_message );

		if (
			! isset( $this->validateState )
			&& $this->_supports_state_validation
			&& ( in_array( $this->type, array( 'consent', 'donation' ) ) || GFCommon::is_product_field( $this->type ) )
		) {
			$this->validateState = true;
		}
	}

	/**
	 * Returns the choices for the Field Size setting.
	 *
	 * @since 2.4.19
	 *
	 * @param bool $values_only Indicates if only the choice values should be returned.
	 *
	 * @return array
	 */
	public function get_size_choices( $values_only = false ) {
		$choices = array(
			array( 'value' => 'small', 'text' => __( 'Small', 'gravityforms' ) ),
			array( 'value' => 'medium', 'text' => __( 'Medium', 'gravityforms' ) ),
			array( 'value' => 'large', 'text' => __( 'Large', 'gravityforms' ) ),
		);

		/**
		 * Allows the choices for Field Size setting to be customized.
		 *
		 * @since 2.4.19
		 *
		 * @param array $choices An array of choices (value and text) to be included in the Field Size setting.
		 */
		$choices = apply_filters( 'gform_field_size_choices', $choices );

		return $values_only ? wp_list_pluck( $choices, 'value' ) : $choices;
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the filter settings for the current field.
	 *
	 * If overriding to add custom settings call the parent method first to get the default settings.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_settings() {
		$filter_settings = array(
			'key'  => $this->id,
			'text' => GFFormsModel::get_label( $this ),
		);

		$sub_filters = $this->get_filter_sub_filters();
		if ( ! empty( $sub_filters ) ) {
			$filter_settings['group']   = true;
			$filter_settings['filters'] = $sub_filters;
		} else {
			$filter_settings['preventMultiple'] = false;
			$filter_settings['operators']       = $this->get_filter_operators();

			$values = $this->get_filter_values();
			if ( ! empty( $values ) ) {
				$filter_settings['values'] = $values;
			}
		}

		return $filter_settings;
	}

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		return array( 'is', 'isnot', '>', '<' );
	}

	/**
	 * Returns the filters values setting for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_values() {
		if ( ! is_array( $this->choices ) ) {
			return array();
		}

		$choices = $this->choices;
		if ( $this->type == 'post_category' ) {
			foreach ( $choices as &$choice ) {
				$choice['value'] = $choice['text'] . ':' . $choice['value'];
			}
		}

		if ( $this->enablePrice ) {
			foreach ( $choices as &$choice ) {
				$price = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );

				$choice['value'] .= '|' . $price;
			}
		}

		return $choices;
	}

	/**
	 * Returns the sub-filters for the current field.
	 *
	 * @since  2.4
	 *
	 * @return array
	 */
	public function get_filter_sub_filters() {
		return array();
	}

	/**
	 * Get the product quantity label.
	 *
	 * @since 2.5
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string
	 */
	public function get_product_quantity_label( $form_id ) {
		/**
		 * Filter for the product quantity label.
		 *
		 * @since unknown
		 *
		 * @param int $form_id  The form ID.
		 * @param int $field_id The field ID.
		 */
		return gf_apply_filters( array(
			'gform_product_quantity',
			$form_id,
			$this->id,
		), esc_html__( 'Quantity', 'gravityforms' ), $form_id );
	}

	/**
	 * Returns an array of key value pairs to be saved to the entry meta after saving/updating the entry.
	 *
	 * @since 2.5.16
	 *
	 * @param array $form  The form object being saved.
	 * @param array $entry The entry object being saved
	 *
	 * @return array The array that contains the key/value pairs to be stored as extra meta data.
	 */
	public function get_extra_entry_metadata( $form, $entry ) {
		return array();
	}

	/**
	 * Decides if the field markup should not be reloaded after AJAX save.
	 *
	 * @since 2.6
	 *
	 * @return boolean
	 */
	public function disable_ajax_reload() {
		return false;
	}

}
