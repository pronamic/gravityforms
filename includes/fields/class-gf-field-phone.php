<?php

// If Gravity Forms isn't loaded, bail.
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Field_Phone
 *
 * Handles the behavior of Phone fields.
 *
 * @since Unknown
 */
class GF_Field_Phone extends GF_Field {

	/**
	 * Defines the field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var string The field type.
	 */
	public $type = 'phone';

	/**
	 * Defines the field title to be used in the form editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_type_title()
	 *
	 * @return string The field title. Translatable and escaped.
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Phone', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to enter a phone number.', 'gravityforms' );
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
		return 'gform-icon--phone';
	}

	/**
	 * Defines the field settings available within the field editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array The field settings available for the field.
	 */
	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'phone_sub_labels_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'phone_format_setting',
			'default_country_setting',
			'show_country_code_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	/**
	 * Defines if conditional logic is supported in this field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::inline_scripts()
	 * @used-by GFFormSettings::output_field_scripts()
	 *
	 * @return bool true
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Returns the placeholder attribute for the field.
	 *
	 * If no custom placeholder is set and the phone format has a mask, uses the mask as placeholder.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_field_placeholder_attribute() {
		if ( empty( $this->placeholder ) ) {
			$phone_format = $this->get_phone_format();
			if ( rgar( $phone_format, 'mask' ) ) {
				return sprintf( "placeholder='%s'", esc_attr( $phone_format['mask'] ) );
			}
		}

		return parent::get_field_placeholder_attribute();
	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 3.0.0
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return parent::get_field_container_tag( $form );
		}

		if ( $this->phoneFormat === 'formatted' ) {
			return 'fieldset';
		} else {
			return 'div';
		}

	}

	/**
	 * Validates inputs for the Phone field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDisplay::validate()
	 * @uses    GF_Field_Phone::get_phone_format()
	 * @uses    GF_Field_Phone::$validation_message
	 * @uses    GF_Field_Phone::$errorMessage
	 *
	 * @param array|string $value The field value to be validated.
	 * @param array        $form  The Form Object.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		// Skip validation if value is empty.
		if ( $value === '' || $value === 0 ) {
			return;
		}

		// For formatted international phone, validate JSON structure and E.164 value.
		if ( $this->phoneFormat === 'formatted' ) {
			$decoded = $this->to_array( $value );

			// If it's valid JSON, validate structure
			if ( $decoded ) {
				// Validate required fields exist
				$required_keys = array( 'country', 'national', 'formatted', 'e164' );
				foreach ( $required_keys as $key ) {
					if ( ! isset( $decoded[ $key ] ) ) {
						$this->failed_validation = true;
						$this->validation_message = ! empty( $this->errorMessage ) ? $this->errorMessage : esc_html__( 'Please enter a valid phone number.', 'gravityforms' );
						return;
					}
				}

				// Use E.164 validator on the e164 field
				if ( ! class_exists( 'E164Validator' ) ) {
					require_once GFCommon::get_base_path() . '/includes/validation/class-e164-validator.php';
				}

				$validator         = new E164Validator();
				$validation_result = $validator->validate( $decoded['e164'], true );

				if ( ! $validation_result['valid'] ) {
					$this->failed_validation = true;
					$this->validation_message = ! empty( $this->errorMessage ) ? $this->errorMessage : $validation_result['message'];
				}
				return;
			}

			// If not JSON, treat as invalid
			$this->failed_validation = true;
			$this->validation_message = esc_html__( 'Please enter a valid phone number in the correct format.', 'gravityforms' );
			return;
		}

		// For other formats, use regex validation.
		$phone_format = $this->get_phone_format();
		if ( rgar( $phone_format, 'regex' ) && ! preg_match( $phone_format['regex'], $value ) ) {
			$this->failed_validation  = true;
			$this->validation_message = ! empty( $this->errorMessage )
				? $this->errorMessage
				: sprintf( esc_html__( 'Phone format: %s', 'gravityforms' ), rgar( $phone_format, 'instruction' ) );
		}
	}

	/**
	 * Get field CSS class.
	 *
	 * @since: next
	 *
	 * @return string
	 */
	public function get_field_css_class() {
		return 'gfield--phone-format-' . $this->phoneFormat;
	}

	/**
	 * Returns the field input.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_input()
	 * @uses    GF_Field::is_entry_detail()
	 * @uses    GF_Field::is_form_editor()
	 * @uses    GF_Field_Phone::$failed_validation
	 * @uses    GF_Field_Phone::get_phone_format()
	 * @uses    GF_Field::get_field_placeholder_attribute()
	 * @uses    GF_Field_Phone::$isRequired
	 * @uses    GF_Field::get_tabindex()
	 *
	 * @param array      $form  The Form Object.
	 * @param string     $value The value of the input. Defaults to empty string.
	 * @param null|array $entry The Entry Object. Defaults to null.
	 *
	 * @return string The HTML markup for the field.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( is_array( $value ) ) {
			$value = '';
		}
		if ( $this->phoneFormat !== 'formatted' && ! empty( $value ) && GFCommon::is_json( $value ) ) {
            $decoded = $this->to_array( $value );
            if ( $decoded ) {
                $value = ! empty( $decoded['e164'] ) ? $decoded['e164'] : ( ! empty( $decoded['formatted'] ) ? $decoded['formatted'] : ( ! empty( $decoded['national'] ) ? $decoded['national'] : '' ) );
            }
        }

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size          = $this->size;
		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';
		$class         = $size . $class_suffix;
		$class         = esc_attr( $class );

		$mask_attribute = '';
		$phone_format   = $this->get_phone_format();
		if ( ! empty( $phone_format['mask'] ) ) {
			$mask_attribute = sprintf( 'data-mask="%s"', esc_attr( $phone_format['mask'] ) );
		}

		$html_input_type        = 'tel';
		$placeholder_attribute  = $this->phoneFormat !== 'formatted' ? $this->get_field_placeholder_attribute() : '';
		$required_attribute     = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby       = $this->get_aria_describedby();
		$autocomplete_attribute = $this->enableAutocomplete ? $this->get_field_autocomplete_attribute() : '';

		$tabindex = $this->get_tabindex();

		// Use pre-rendered HTML for formatted phone fields
		if ( $this->phoneFormat === 'formatted' ) {
			// Generate the pre-rendered international phone HTML
			$phone_html = $this->get_formatted_phone_html(
				$field_id,
				$value,
				$class,
				$tabindex,
				$placeholder_attribute,
				$required_attribute,
				$invalid_attribute,
				$aria_describedby,
				$autocomplete_attribute,
				$disabled_text,
				$form
			);

			// Get default country for data attribute
			$default_country = ! empty( $this->defaultCountry ) ? strtolower( $this->defaultCountry ) : 'us';
			$default_country_attr = sprintf( " data-default-country='%s'", esc_attr( $default_country ) );

			// Get show country code setting (defaults to true)
			$show_country_code = isset( $this->showCountryCode ) ? $this->showCountryCode : true;
			$show_country_code_attr = sprintf( " data-show-country-code='%s'", $show_country_code ? 'true' : 'false' );

			// Wrap in container and add hidden input
			$input = sprintf(
				"<div class='ginput_container ginput_container_phone'>%s<input name='input_%d' id='%s' type='hidden' value='%s' class='%s' data-phone-format='formatted'%s%s %s %s %s/></div>",
				$phone_html,
				$id,
				$field_id,
				esc_attr( $value ),
				esc_attr( $class ),
				$default_country_attr,
				$show_country_code_attr,
				$required_attribute,
				$invalid_attribute,
				$aria_describedby
			);
		} else {
			// For non-formatted phones, use the original simple input
			$input = sprintf( "<div class='ginput_container ginput_container_phone'><input name='input_%d' id='%s' type='{$html_input_type}' value='%s' class='%s' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$aria_describedby} {$autocomplete_attribute} {$mask_attribute} %s/></div>", $id, $field_id, esc_attr( $value ), esc_attr( $class ), $disabled_text );
		}

		return $input;
	}

	/**
	 * Get dial codes from the shared countries.json file.
	 *
	 * @since: next
	 *
	 * @return array Associative array of iso2 => dialCode.
	 */
	private static function get_dial_codes() {
		static $dial_codes = null;

		if ( $dial_codes === null ) {
			$dial_codes = array();
			$json_path  = GFCommon::get_base_path() . '/assets/js/src/theme/fields/international-phone/countries.json';

			if ( file_exists( $json_path ) ) {
				$json_content = file_get_contents( $json_path );
				$countries    = json_decode( $json_content, true );

				if ( is_array( $countries ) ) {
					foreach ( $countries as $country ) {
						if ( isset( $country['iso2'], $country['dialCode'] ) ) {
							$dial_codes[ strtolower( $country['iso2'] ) ] = $country['dialCode'];
						}
					}
				}
			}
		}

		return $dial_codes;
	}

	/**
	 * Get dial code for a country ISO2 code.
	 *
	 * @since: next
	 *
	 * @param string $iso2 The country ISO2 code.
	 *
	 * @return string The dial code or empty string if not found.
	 */
	private static function get_dial_code( $iso2 ) {
		$dial_codes = self::get_dial_codes();
		$iso2       = strtolower( $iso2 );
		return isset( $dial_codes[ $iso2 ] ) ? $dial_codes[ $iso2 ] : '';
	}

	/**
	 * Get country data (dial code and placeholder) for a country ISO2 code.
	 *
	 * @since: next
	 *
	 * @param string $iso2 The country ISO2 code.
	 *
	 * @return array Array with 'dialCode' and 'placeholder' keys.
	 */
	private static function get_country_data( $iso2 ) {
		static $countries = null;

		if ( $countries === null ) {
			$json_path = GFCommon::get_base_path() . '/assets/js/src/theme/fields/international-phone/countries.json';
			$countries = file_exists( $json_path ) ? json_decode( file_get_contents( $json_path ), true ) : array();
		}

		$iso2 = strtolower( $iso2 );
		foreach ( $countries as $country ) {
			if ( isset( $country['iso2'] ) && strtolower( $country['iso2'] ) === $iso2 ) {
				return array(
					'dialCode'    => isset( $country['dialCode'] ) ? $country['dialCode'] : '',
					'placeholder' => isset( $country['placeholder'] ) ? $country['placeholder'] : '',
				);
			}
		}

		return array( 'dialCode' => '', 'placeholder' => '' );
	}

	/**
	 * Generates pre-rendered HTML structure for international phone field
	 *
	 * @since 3.0
	 *
	 * @param string $field_id The field ID
	 * @param string $value The field value
	 * @param string $class The CSS classes
	 * @param string $tabindex The tabindex attribute
	 * @param string $placeholder_attribute The placeholder attribute
	 * @param string $required_attribute The required attribute
	 * @param string $invalid_attribute The invalid attribute
	 * @param string $aria_describedby The aria-describedby attribute
	 * @param string $autocomplete_attribute The autocomplete attribute
	 * @param string $disabled_text The disabled attribute
	 * @param array $form The current form
	 *
	 * @return string The pre-rendered HTML
	 */
	private function get_formatted_phone_html( $field_id, $value, $class, $tabindex, $placeholder_attribute, $required_attribute, $invalid_attribute, $aria_describedby, $autocomplete_attribute, $disabled_text, $form ) {
		// Get default country (fallback to 'us' if not set)
		$default_country = ! empty( $this->defaultCountry ) ? strtolower( $this->defaultCountry ) : 'us';

		// Parse value if it's JSON
		$phone_number = '';
		$country_iso = $default_country;
		if ( ! empty( $value ) && GFCommon::is_json( $value ) ) {
			$decoded = $this->to_array( $value );
			if ( $decoded && isset( $decoded['country'] ) ) {
				$country_iso = strtolower( $decoded['country'] );
				$phone_number = isset( $decoded['national'] ) ? $decoded['national'] : ( isset( $decoded['formatted'] ) ? $decoded['formatted'] : '' );
			}
		} elseif ( ! empty( $value ) ) {
			$phone_number = $value;
		}

		// Generate unique ID for this instance
		$unique_id = uniqid();
		$visible_input_id = $field_id . '_visible';
		$dropdown_id = 'gform_phone_dropdown_' . $unique_id;

		// Button ID for aria-controls
		$button_id = 'country_selector_button_' . $this->id;

		// Get country data (dial code and placeholder)
		$country_data = self::get_country_data( $country_iso );
		$dial_code    = $country_data['dialCode'];

		// Get sublabels
		$country_sublabel_text = $this->countrySublabel != '' ? esc_html( $this->countrySublabel ) : esc_html__( 'Country', 'gravityforms' );
		$phone_sublabel_text   = $this->phoneSublabel != '' ? esc_html( $this->phoneSublabel ) : esc_html__( 'Phone Number', 'gravityforms' );
		$country_sublabel      = "<label for='" . esc_attr( $button_id ) . "' id='" . esc_attr( $button_id . '_label' ) . "' class='gform-field-label gform-field-label--type-sub'>{$country_sublabel_text}</label>";
		$phone_sublabel        = "<label for='" . esc_attr( $visible_input_id ) . "' id='" . esc_attr( $visible_input_id . '_label' ) . "' class='gform-field-label gform-field-label--type-sub'>{$phone_sublabel_text}</label>";

		$is_sublabel_above = $this->is_sub_label_above( $form );

		$country_sublabel_above = $is_sublabel_above ? $country_sublabel : '';
		$country_sublabel_below = $is_sublabel_above ? '' : $country_sublabel;
		$phone_sublabel_above   = $is_sublabel_above ? $phone_sublabel : '';
		$phone_sublabel_below   = $is_sublabel_above ? '' : $phone_sublabel;

		// Use country-specific placeholder in form editor
		if ( $this->is_form_editor() && $this->phoneFormat === 'formatted' ) {
			$placeholder           = $country_data['placeholder'] ? $country_data['placeholder'] : '(201) 555-0123';
			$placeholder_attribute = sprintf( 'placeholder="%s"', esc_attr( $placeholder ) );
		}

		// Get i18n strings
		$i18n = array(
			'selectCountryAriaLabel' => __( 'Select country', 'gravityforms' ),
			'searchCountriesPlaceholder' => __( 'Search countries', 'gravityforms' ),
			'searchCountriesAriaLabel' => __( 'Search for a country', 'gravityforms' ),
			'countryListAriaLabel' => __( 'Country list', 'gravityforms' ),
		);
		$show_country_code = isset( $this->showCountryCode ) ? $this->showCountryCode : true;
		$wrapper_class = 'gform-phone';
		if ( $show_country_code ) {
			$wrapper_class .= ' gform-phone--show-dial-code';
		}

		$dial_code_display = $dial_code ? '+' . esc_attr( $dial_code ) : '';

		$html = sprintf( '
			<div class="%1$s" role="application">
				<div class="gform-phone__input-wrapper">
					<span class="ginput_country-selector_container">
						%2$s
						<button type="button" id="%3$s" class="gform-phone__country-selector gform-theme-button gform-theme-button--tertiary" aria-haspopup="listbox" aria-expanded="false" aria-controls="%4$s" aria-label="%5$s" %6$s>
							<span class="gform-phone__flag-icon gform-phone__flag-icon--%7$s"></span>
							<span class="gform-phone__dial-code">%8$s</span>
						</button>
						%9$s
					</span>
					<span class="ginput_phone_container">
						%10$s
						<input type="tel" class="gform-phone__input" id="%11$s" name="" autocomplete="tel" value="%12$s" %13$s %14$s %15$s %16$s %17$s %18$s>
						%19$s
					</span>
				</div>
				<div class="gform-phone__dropdown gform-phone__dropdown--hidden" role="listbox" id="%20$s" tabindex="-1">
					<div class="gform-phone__search-wrapper">
						<input type="text" class="gform-phone__search" placeholder="%21$s" aria-label="%22$s" aria-controls="%23$s_list">
					</div>
					<ul class="gform-phone__country-list gform-ul-reset" id="%24$s_list" role="listbox" aria-label="%25$s">
						<!-- Country items will be populated by JavaScript -->
					</ul>
					<div class="gform-phone__aria-live-search-status" aria-live="polite" aria-atomic="true" style="position: absolute; width: 1px; height: 1px; margin: -1px; border: 0; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); clip-path: inset(50%%); white-space: nowrap;"></div>
				</div>
				<div class="gform-phone__aria-live" aria-live="polite" aria-atomic="true" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); clip-path: inset(50%%); border: 0; white-space: nowrap;"></div>
			</div>',
			esc_attr( $wrapper_class ),                         // 1: wrapper class
			$country_sublabel_above,                            // 2: country sublabel above
			esc_attr( $button_id ),                             // 3: button id
			esc_attr( $dropdown_id ),                           // 4: aria-controls
			esc_attr( $i18n['selectCountryAriaLabel'] ),        // 5: aria-label
			$disabled_text,                                     // 6: disabled
			esc_attr( $country_iso ),                           // 7: flag icon class
			$dial_code_display,                                 // 8: dial code with + prefix or empty
			$country_sublabel_below,                            // 9: country sublabel below
			$phone_sublabel_above,                              // 10: phone sublabel above
			esc_attr( $visible_input_id ),                      // 11: visible input id
			esc_attr( $phone_number ),                          // 12: visible input value
			$placeholder_attribute,                             // 13
			$tabindex,                                          // 14
			$required_attribute,                                // 15
			$invalid_attribute,                                 // 16
			$aria_describedby,                                  // 17
			$disabled_text,                                     // 18
			$phone_sublabel_below,                              // 19: phone sublabel below
			esc_attr( $dropdown_id ),                           // 20: dropdown id
			esc_attr( $i18n['searchCountriesPlaceholder'] ),    // 21
			esc_attr( $i18n['searchCountriesAriaLabel'] ),      // 22
			esc_attr( $dropdown_id ),                           // 23: list id prefix
			esc_attr( $dropdown_id ),                           // 24: list id
			esc_attr( $i18n['countryListAriaLabel'] )           // 25
		);

		return $html;
	}

	/**
	 * Helper method to decode JSON phone value to array.
	 *
	 * @param string $value The JSON string to decode.
	 *
	 * @return array|false The decoded array or false if invalid JSON.
	 */
	private function to_array( $value ) {
		if ( ! GFCommon::is_json( $value ) ) {
			return false;
		}

		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : false;
	}

	/**
	 * Gets the value of the submitted field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::get_field_value()
	 * @uses    GF_Field::get_value_submission()
	 * @uses    GF_Field_Phone::sanitize_entry_value()
	 *
	 * @param array $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool  $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values. Defaults to true.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value = parent::get_value_submission( $field_values, $get_from_post_global_var );
		$value = $this->sanitize_entry_value( $value, $this->formId );

		return $value;
	}

	/**
	 * Sanitizes the entry value.
	 *
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Phone::get_value_save_entry()
	 * @used-by GF_Field_Phone::get_value_submission()
	 *
	 * @param string $value   The value to be sanitized.
	 * @param int    $form_id The form ID of the submitted item.
	 *
	 * @return array|array[]|string[] The sanitized value.
	 */
	public function sanitize_entry_value( $value, $form_id ) {
		if ( is_array( $value ) ) {
			return array_map( function( $v ) use ( $form_id ) {
				return $this->sanitize_entry_value( $v, $form_id );
			}, $value );
		}

		$sanitized = sanitize_text_field( $value );
		$this->post_entry_value_sanitization( $value, $sanitized, 'sanitize_text_field' );

		return $sanitized;
	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value          The value to be saved.
	 * @param array  $form           The Form object currently being processed.
	 * @param string $input_name     The input name used when accessing the $_POST.
	 * @param int    $entry_id        The ID of the entry currently being processed.
	 * @param array  $entry           The entry currently being processed.
	 * @param string $repeater_index The repeater index if the field is inside a repeater.
	 *
	 * @return array|string The sanitized and formatted input value to be saved.
	 */
	public function get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index = '' ) {
		$value = $this->sanitize_entry_value( $value, $form['id'] );

		if ( $this->phoneFormat == 'standard' && preg_match( '/^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/', $value, $matches ) ) {
			$value = sprintf( '(%s) %s-%s', $matches[1], $matches[2], $matches[3] );
		} elseif ( $this->phoneFormat == 'formatted' && ! empty( $value ) ) {
			// Formatted international phone must be JSON
			$decoded = $this->to_array( $value );

			if ( ! $decoded ) {
				return ''; // Not valid JSON
			}

			// Define allowed keys
			$allowed_keys = array( 'country', 'national', 'formatted', 'e164' );

			// Check for extra keys
			$extra_keys = array_diff( array_keys( $decoded ), $allowed_keys );
			if ( ! empty( $extra_keys ) ) {
				return ''; // Reject if extra keys present
			}

			// Validate required fields
			foreach ( $allowed_keys as $key ) {
				if ( ! isset( $decoded[ $key ] ) ) {
					return ''; // Invalid structure
				}
			}

			// Validate country code
			if ( ! is_string( $decoded['country'] ) || strlen( $decoded['country'] ) !== 2 ) {
				return '';
			}

			// Validate E.164 format
			if ( ! preg_match( '/^\+[1-9]\d{1,14}$/', $decoded['e164'] ) ) {
				return '';
			}

			// Sanitize values
			$sanitized = array(
				'country'   => strtoupper( substr( $decoded['country'], 0, 2 ) ),
				'national'  => preg_replace( '/[^\d\s\-\(\).]/', '', $decoded['national'] ),
				'formatted' => wp_kses( $decoded['formatted'], array() ),
				'e164'      => preg_replace( '/[^\d\+]/', '', $decoded['e164'] )
			);

			$value = json_encode( $sanitized );
		}

		return $value;
	}

	/**
	 * Gets the value to be displayed on the entry detail page.
	 *
	 * @since: next
	 * @access public
	 *
	 * @param string $value    The field value.
	 * @param string $currency The entry currency code.
	 * @param bool   $use_text When processing merge tags, etc.
	 * @param string $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string $media    The location where the value will be displayed.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( ! empty( $value ) && GFCommon::is_json( $value ) ) {
			$decoded = $this->to_array( $value );
			if ( $decoded && isset( $decoded['formatted'] ) && isset( $decoded['e164'] ) && isset( $decoded['country'] ) ) {

				// Format: formatted value on first line, E.164 (country) on second line
				$line_break = $format == 'html' ? '<br />' : "\n";
				return $decoded['formatted'] . $line_break . __( 'Raw E.164 value:', 'gravityforms' ) . ' ' . $decoded['e164'] . ' (' . strtoupper( $decoded['country'] ) . ')';
			}
		}

		return $value;
	}

	/**
	 * Gets the value to be displayed on the entries list page.
	 *
	 * @since: next
	 * @access public
	 *
	 * @param string $value    The field value.
	 * @param array  $entry    The Entry Object.
	 * @param string $field_id The field ID.
	 * @param array  $columns  The columns to be displayed.
	 * @param array  $form     The Form Object.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		if ( ! empty( $value ) && GFCommon::is_json( $value ) ) {
			$decoded = $this->to_array( $value );
			if ( $decoded && isset( $decoded['formatted'] ) ) {
				return $decoded['formatted'];
			}
		}

		return $value;
	}

	/**
	 * Gets the merge tag value.
	 *
	 * @since: next
	 * @access public
	 *
	 * @param string $value      The merge tag value to be filtered.
	 * @param string $input_id   The field or input ID from the merge tag.
	 * @param array  $entry      The Entry Object.
	 * @param array  $form       The Form Object.
	 * @param string $modifier   The merge tag modifier.
	 * @param string $raw_value  The raw field value from before formatting.
	 * @param bool   $url_encode Whether to encode the value for URLs.
	 * @param bool   $esc_html   Whether to encode HTML entities.
	 * @param string $format     The format requested for the location the merge is being used.
	 * @param bool   $nl2br      Whether to convert newlines to HTML line breaks.
	 *
	 * @return string
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		if ( $this->phoneFormat == 'formatted' && ! empty( $raw_value ) && GFCommon::is_json( $raw_value ) ) {
			$decoded = $this->to_array( $raw_value );
			if ( $decoded ) {
				// Support modifiers for object keys, like {Phone:1:country} or {Phone:1:e164}
				if ( $modifier && isset( $decoded[ $modifier ] ) ) {
					return $decoded[ $modifier ];
				}
				// Default to formatted value
				if ( isset( $decoded['formatted'] ) ) {
					return $decoded['formatted'];
				}
			}
		}

		return $value;
	}

	/**
	 * Gets the value to be used when exporting the entry.
	 *
	 * @since: next
	 * @access public
	 *
	 * @param array  $entry    The Entry Object.
	 * @param string $input_id The field or input ID.
	 * @param bool   $use_text Whether to use the text value.
	 * @param bool   $is_csv   Whether the export is for CSV.
	 *
	 * @return string
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );

		if ( $this->phoneFormat == 'formatted' && ! empty( $value ) && GFCommon::is_json( $value ) ) {
			$decoded = $this->to_array( $value );
			if ( $decoded && isset( $decoded['e164'] ) ) {
				return isset( $decoded['e164'] ) ? $decoded['e164'] : $decoded['formatted'];
			}
		}

		return $value;
	}

	/**
	 * Sanitizes the field settings.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::add_field()
	 * @used-by GFFormsModel::sanitize_settings()
	 * @uses    GF_Field::sanitize_settings()
	 * @uses    GF_Field_Phone::get_phone_format()
	 * @uses    GF_Field_Phone::$phoneFormat
	 *
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();

		if ( ! $this->get_phone_format() ) {
			$this->phoneFormat = 'formatted';
		}

		if ( $this->phoneFormat === 'formatted' ) {
			$this->storageType = 'json';
		}
	}

	/**
	 * Get an array of phone formats.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Phone::get_phone_format()
	 *
	 * @param null|int $form_id The ID of the current form or null to use the value from the current fields form_id property. Defaults to null.
	 *
	 * @return array The phone formats available.
	 */
	public function get_phone_formats( $form_id = null ) {

		if ( empty( $form_id ) ) {
			$form_id = $this->formId;
		}
		$form_id = absint( $form_id );

		$phone_formats = array(
			'formatted' => array(
				'label'       => __( 'International (formatted)', 'gravityforms' ),
				'mask'        => false,
				'regex'       => false,
				'instruction' => false,
			),
			'international' => array(
				'label'       => __( 'International (unformatted)', 'gravityforms' ),
				'mask'        => false,
				'regex'       => false,
				'instruction' => false,
			),
			'standard'      => array(
				'label'       => __( 'US Standard', 'gravityforms' ),
				'mask'        => '(999) 999-9999',
				'regex'       => '/^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/',
				'instruction' => '(###) ###-####',
			),
		);

		/**
		 * Allow custom phone formats to be defined.
		 *
		 * @since 2.0.0
		 *
		 * @param array $phone_formats The phone formats.
		 * @param int   $form_id       The ID of the current form.
		 */
		return gf_apply_filters( array( 'gform_phone_formats', $form_id ), $phone_formats, $form_id );
	}

	/**
	 * Get the properties for the fields selected phone format.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Phone::get_field_input()
	 * @used-by GF_Field_Phone::sanitize_settings()
	 * @used-by GF_Field_Phone::validate()
	 * @uses    GF_Field_Phone::get_phone_formats()
	 * @uses    GF_Field_Phone::$phoneFormat
	 *
	 * @return array The phone format.
	 */
	public function get_phone_format() {
		$phone_formats = $this->get_phone_formats();

		return rgar( $phone_formats, $this->phoneFormat );
	}

	/**
	 * Actions to be performed after the field has been converted to an object.
	 *
	 * @since 3.0.2
	 *
	 * @return void
	 */
	public function post_convert_field() {
		parent::post_convert_field();
		if ( ! $this->get_phone_format() ) {
			$this->phoneFormat = 'formatted';
		}
	}

}

// Register the phone field with the field framework.
GF_Fields::register( new GF_Field_Phone() );
