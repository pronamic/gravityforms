<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use ArrayAccess;
use Gravity_Forms\Gravity_Forms\Settings\Settings;
use GFCommon;

defined( 'ABSPATH' ) || die();

class Base implements ArrayAccess {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Field name.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Field label.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $label;

	/**
	 * Field CSS class.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $class;

	/**
	 * Field requirement.
	 *
	 * @since 2.5
	 *
	 * @var bool
	 */
	public $required = false;

	/**
	 * Nested fields for field.
	 *
	 * @since 2.5
	 *
	 * @var Base{}
	 */
	public $fields = array();

	/**
	 * Child inputs for field.
	 *
	 * @since 2.5
	 *
	 * @var Base{}
	 */
	public $inputs = array();

	/**
	 * Default value of field.
	 *
	 * @since 2.5
	 *
	 * @var string|array
	 */
	public $default_value;

	/**
	 * Dependencies required to display field.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $dependency;

	/**
	 * Function to run when field is saved.
	 * Returns field value.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	public $save_callback;

	/**
	 * Function to run when field is being validated.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	public $validation_callback;

	/**
	 * Legacy add-on method to call when a field is being validated.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	public $legacy_validation_callback;

	/**
	 * @var Settings
	 */
	public $settings;

	/**
	 * Field error message.
	 *
	 * @since 2.5
	 *
	 * @var string|false
	 */
	private $error = false;

	/**
	 * Current function rendering field.
	 *
	 * @since 2.5
	 *
	 * @var callable
	 */
	protected static $current_render_callback;

	/**
	 * Initialize field.
	 *
	 * @since 2.5
	 *
	 * @param array    $props    Field properties.
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		// Set Settings framework instance.
		$this->settings = $settings;

		// Loop through properties, save to instance.
		foreach ( $props as $prop => $value ) {

			// Handle default value.
			if ( $prop === 'value' ) {
				$this->default_value = $value;
				continue;
			}

			$this->{ $prop } = $value;
		}

	}

	/**
	 * Register scripts to enqueue when displaying field.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function scripts() {

		return array();

	}

	/**
	 * Register styles to enqueue when displaying field.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function styles() {

		return array();

	}





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function prepare_markup() {

		if ( isset( $this->callback ) && is_callable( $this->callback ) && self::$current_render_callback !== $this->callback ) {
			self::$current_render_callback = $this->callback;
			$html             = call_user_func( $this->callback, $this, false );
			self::$current_render_callback = false;
		} else {
			$html = $this->markup();
		}

		// If field has nested fields, display.
		if ( ! empty( $this->fields ) ) {
			$html .= '<fieldset class="gform-settings-nested-fields">';
			foreach ( $this->fields as $field ) {
				ob_start();
				$this->settings->render_field( $field );
				$html .= ob_get_contents();
				ob_end_clean();
			}
			$html .= '</fieldset>';
		}

		return $html;

	}

	/**
	 * Prepare field markup.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		return '';

	}

	/**
	 * Returns a set of HTML attributes to be added to the HTML element.
	 *
	 * @since 2.5
	 *
	 * @param array $default Default set of attributes.
	 *
	 * @return array
	 */
	public function get_attributes( $default = array() ) {

		// Define default attributes.
		$default_atts = array(
			'class'         => '',
			'default_value' => '', // Default value that should be selected or entered for the field.
		);

		// Add additional default attributes.
		switch ( $this->type ) {
			case 'select':
				$default_atts['choices'] = array();
				break;
			case 'checkbox':
				$default_atts['checked']        = false;
				$default_atts['checkbox_label'] = '';
				$default_atts['choices']        = array();
				break;
			default:
				break;
		}

		/**
		 * Each nonstandard property will be extracted from the $props array so it is not auto-output in the field HTML
		 *
		 * @param array $field The current field meta to be parsed.
		 */
		$excluded_atts = apply_filters(
			'gaddon_no_output_field_properties',
			array(
				'default_value', 'label', 'toggle_label', 'choices', 'feedback_callback', 'checked', 'checkbox_label', 'value', 'type',
				'validation_callback', 'hidden', 'tooltip', 'dependency', 'messages', 'name', 'args',
				'exclude_field_types', 'field_type', 'after_input', 'input_type', 'icon', 'save_callback',
				'enable_custom_value', 'enable_custom_key', 'merge_tags', 'key_field', 'value_field', 'callback', 'labels',
				'input_types', 'settings', 'inputs', 'fields', 'no_choices', 'enhanced_ui', 'description'
			), $this
		);

		// Merge field properties with default attributes.
		$atts             = wp_parse_args( $this, $default_atts );
		$atts['id'] = rgempty( 'id', $atts ) ? rgar( $atts, 'name' ) : rgar( $atts, 'id' );
		$atts['id'] = str_replace( '[]', null, $atts['id'] );
		$atts['required'] = ( $atts['required'] === true ) ? 'required' : null;

		// Remove disabled property.
		if ( isset( $atts['disabled'] ) && $atts['disabled'] === false ) {
			unset( $atts['disabled'] );
		}

		// Remove excluded attributes.
		foreach ( $atts as $key => $value ) {
			if ( in_array( $key, $excluded_atts ) ) {
				unset( $atts[ $key ] );
			}
		}

		// Add default attributes.
		foreach ( $default as $key => $value ) {
			if ( isset( $atts[ $key ] ) ) {
				$atts[ $key ] = $value . $atts[ $key ];
			} else {
				$atts[ $key ] = $value;
			}
		}


		// Prepare attributes as strings.
		$return = array();
		foreach ( $atts as $att => $value ) {
			if ( ! in_array( $value, array( 'disabled', 'readonly' ) ) && ( is_callable( $value ) || empty( $value ) ) ) {
				continue;
			}
			$return[ $att ] = "{$att}='" . esc_attr( $value ) . "'";
		}

		return $return;

	}

	/**
	 * Parses the properties of a field choice and returns a set of HTML attributes to be added to the HTML element.
	 *
	 * @since 2.5
	 *
	 * @param array $choice             Field choice meta.
	 * @param array $field_attributes   Field attributes.
	 * @param array $default_attributes Default attributes for choice.
	 *
	 * @return array
	 */
	public static function get_choice_attributes( $choice, $field_attributes = array(), $default_attributes = array() ) {

		// Use field attributes as a base for the choice attributes.
		$atts = $field_attributes;

		// Loop through each choice property, add to the attributes.
		foreach ( $choice as $prop => $value ) {

			// Choice properties to remove.
			$excluded_atts = array(
				'default_value', 'label', 'checked', 'value', 'type',
				'validation_callback', 'required', 'tooltip', 'icon'
			);

			if ( $prop === 'disabled' && $value === false ) {
				unset( $atts[ $prop ] );
			} elseif ( in_array( $prop, $excluded_atts ) || is_array( $value ) ) {
				unset( $atts[ $prop ] );
			} else {
				$atts[ $prop ] = "{$prop}='" . esc_attr( $value ) . "'";
			}

		}

		// Adding default attributes: create new attribute or prepend to existing.
		foreach ( $default_attributes as $attr_name => $attr_value ) {
			if ( isset( $atts[ $attr_name ] ) ) {
				$atts[ $attr_name ] = str_replace( "{$attr_name}='", "{$attr_name}='{$attr_value}", $atts[ $attr_name ] );
			} else {
				$atts[ $attr_name ] = "{$attr_name}='" . esc_attr( $attr_value ) . "'";
			}
		}

		return $atts;

	}

	/**
	 * Get describer element(s) for field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_describer() {

		// Prepare error and description IDs.
		$error_id       = sprintf( 'error-%s', esc_attr( $this->name ) );
		$description_id = $this->get_description() ? sprintf( 'description-%s', esc_attr( $this->name ) ) : '';

		return $this->get_error() ? implode( ', ', array( $error_id, $description_id ) ) : $description_id;

	}

	/**
	 * Renders the description text for a field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_description() {

		return isset( $this->description ) ? sprintf( '<span class="gform-settings-description" id="description-%s">%s</span>', $this->name, $this->description ) : '';

	}

	/**
	 * Gets the classes to apply to the field container.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_container_classes() {

		// Prepare feedback.
		$error             = $this->get_error();
		$is_invalid        = ! empty( $error );
		$value             = $this->get_value();
		$is_feedback_valid = null;

		// Prepare container classes.
		$feedback_callback = ( $this->type == 'text' ) ? rgar( $this, 'feedback_callback' ) : null;
		if ( is_callable( $feedback_callback ) ) {
			$is_feedback_valid = call_user_func_array( $feedback_callback, array( $value, $this ) );
		}

		$container_classes = array( 'gform-settings-input__container' );

		if ( is_bool( $is_feedback_valid ) ) {
			$container_classes[] = $is_feedback_valid ? 'gform-settings-input__container--feedback-success' : 'gform-settings-input__container--feedback-error';
		}

		if ( is_string( $is_feedback_valid ) ) {
			$container_classes[] = sprintf( 'gform-settings-input__container--feedback-%s', $is_feedback_valid );
		}

		if ( $is_invalid ) { $container_classes[] = 'gform-settings-input__container--invalid'; }
		if ( isset( $this->append ) ) { $container_classes[] = 'gform-settings-input__container--with-append'; }
		if ( strpos( $this->class, 'merge-tag-support' ) !== false ) { $container_classes[] = 'gform-settings-input__container--with-merge-tag'; }

		return implode( ' ', $container_classes );

	}

	/**
	 * Determines if any of the available field choices have an icon.
	 *
	 * @since 2.5
	 *
	 * @param array $choices
	 *
	 * @return bool
	 */
	public static function has_icons( $choices = array() ) {

		foreach ( $choices as $choice ) {
			if ( rgar( $choice, 'icon' ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Determines if any of the available field choices have a tag.
	 *
	 * @since 2.5.6.4
	 *
	 * @param array $choices
	 *
	 * @return bool
	 */
	public static function has_tag( $choices = array() ) {

		foreach ( $choices as $choice ) {
			if ( rgar( $choice, 'tag' ) ) {
				return true;
			}
		}

		return false;

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 * Run defined callback.
	 *
	 * @since 2.5
	 *
	 * @deprecated Deprecated since 2.5-beta-3. Use handle_validation() instead.
	 *
	 * @param array|bool|string $value Posted field value.
	 */
	public function validate( $value ) {
		_deprecated_function( __METHOD__, '2.5-beta-3', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Base::handle_validation()' );

		$this->handle_validation( $value );
	}

	/**
	 * Validate posted field value.
	 * Run defined callback.
	 *
	 * @since 2.5-beta-3
	 *
	 * @param array|bool|string $value Posted field value.
	 */
	public function handle_validation( $value ) {

		// If field has a custom validation callback, call it.
		if ( is_callable( $this->validation_callback ) ) {
			call_user_func( $this->validation_callback, $this, $value );
			return;
		}

		if ( is_callable( $this->legacy_validation_callback ) ) {
			call_user_func( $this->legacy_validation_callback, $this, $this->settings->get_posted_values() );
			return;
		}

		// Validate field.
		$this->do_validation( $value );

	}

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @deprecated Deprecated since 2.5-beta-3 - use do_validation() instead.
	 *
	 * @param array|bool|string $value Posted field value.
	 */
	public function is_valid( $value ) {

		_deprecated_function( __METHOD__, '2.5-beta-3', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Base::do_validation()' );

		$this->do_validation( $value );
	}

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5-beta-3
	 *
	 * @param array|bool|string $value Posted field value.
	 */
	public function do_validation( $value ) {

		// If field is required and value is empty, set error.
		if ( $this->required && rgblank( $value ) ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}

	}

	/**
	 * Determine if the current choice is a match for the submitted field value.
	 *
	 * @since 2.5
	 *
	 * @param array        $choice The choice properties.
	 * @param string|array $value  The submitted field value.
	 *
	 * @return bool
	 */
	public static function is_choice_valid( $choice, $value ) {

		$choice_value = isset( $choice['value'] ) ? $choice['value'] : $choice['label'];

		return is_array( $value ) ? in_array( $choice_value, $value ) : $choice_value == $value;

	}

	/**
	 * Get error message for field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_error() {

		return $this->error;

	}

	/**
	 * Gets the validation error icon.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_error_icon() {

		// Get field error.
		$error = $this->get_error();

		return $error ? sprintf( '<div class="gform-settings-validation__error" id="error-%s">%s</div>', $this->name, $error ) : '';

	}

	/**
	 * Set error message for field.
	 *
	 * @since 2.5
	 *
	 * @param string $error_message Error message.
	 */
	public function set_error( $error_message = '' ) {

		// If no error message is provided, use default.
		if ( ! $error_message ) {
			$error_message = esc_html__( 'This field is required.', 'gravityforms' );
		}

		$this->error = $error_message;

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Helper method to return choices for field.
	 *
	 * @since 2.5
	 *
	 * @param array|callable|bool $choices Existing choices. Defaults to field property.
	 *
	 * @return array
	 */
	public function get_choices( $choices = false ) {

		// Get choices property, if not provided.
		if ( ! $choices ) {
			$choices = rgobj( $this, 'choices' );
		}

		// If no choices exist, return.
		if ( ! $choices ) {
			return array();
		}

		// If this is a callable, get the return value.
		if ( is_callable( $choices ) ) {
			return call_user_func( $choices, $this );
		} elseif ( is_array( $choices ) ) {
			return $choices;
		}

		return array();

	}

	/**
	 * Helper method to retrieve field value.
	 *
	 * @since 2.5
	 *
	 * @return array|bool|string
	 */
	public function get_value() {

		return $this->settings->get_value( $this->name, $this->default_value );

	}

	/**
	 * Get field value before saving.
	 *
	 * @since 2.5
	 *
	 * @param array             $field_values Posted field values.
	 * @param array|bool|string $field_value  Posted value for field.
	 *
	 * @return array
	 */
	public function save_field( $field_values, $field_value ) {

		// If field has a save callback, call it.
		if ( is_callable( $this->save_callback ) ) {
			$field_value = call_user_func( $this->save_callback, $this, $field_value );
		} else {
			$field_value = $this->save( $field_value );
		}

		$name = $this->get_parsed_name();

		if ( is_array( $name ) ) {
			return GFCommon::set_array_value( $field_values, $name, $field_value );
		}

		$field_values[ $name ] = $field_value;

		return $field_values;

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

		return $value;

	}




	// # ARRAY ACCESS METHODS ------------------------------------------------------------------------------------------

	/**
	 * Whether or not an offset exists
	 *
	 * @since 2.5
	 *
	 * @param mixed $offset An offset to check for.
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {

		return isset( $this->$offset );

	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @since 2.5
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {

		if ( ! isset( $this->$offset ) ) {
			$this->$offset = '';
		}

		return $this->$offset;

	}

	/**
	 * Assigns a value to the specified offset
	 *
	 * @since 2.5
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $data   The value to set.
	 */
	public function offsetSet( $offset, $data ) {

		if ( $offset === null ) {
			$this[] = $data;
		} else {
			$this->$offset = $data;
		}

	}

	/**
	 * Unset an offset
	 *
	 * @since 2.5
	 *
	 * @param mixed $offset The offset to unset.
	 */
	public function offsetUnset( $offset ) {

		unset( $this->$offset );

	}

	/**
	 * Parses the field's name to determine whether it's a simple string or a multi-dimensional array.
	 *
	 * Examples:
	 * - A string of gravityformsapi will return gravityformsapi.
	 * - A string of gravityformsapi[log_level] will return an array of [ 'gravityformsapi', 'log_level' ].
	 *
	 * @since 2.5
	 *
	 * @return string|array
	 */
	public function get_parsed_name() {
		if ( ! is_string( $this->name ) ) {
			return '';
		}

		// String doesn't have any psuedo array elements, which means it's just a normal string. Return it.
		if ( false === strpos( $this->name, '[' ) ) {
			return $this->name;
		}

		// Run a regex match to find all the values from the string.
		preg_match_all( '/([^\[]+)?\[([^\[]*?)\]/s', $this->name, $matches );

		// No matches found for some reason, return a blank string.
		if ( empty( $matches ) ) {
			return '';
		}

		// Sometimes, the array of bracket matches is just a single empty value. If so, return the first match.
		if ( ! empty( $matches[1] ) && count( $matches[2] ) <= 1 && empty( $matches[2][0] ) ) {
			return array_shift( $matches[1] );
		}

		/**
		 * The setting name (non-brackets) is stored in a different match array than the bracket values. Merge
		 * them together in order to return all of them (and filter any empty values).
		 */
		$results = array_merge( array_filter( $matches[1] ), array_filter( $matches[2] ) );

		return array_values( $results );
	}

}
