<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles system ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_System {

	/**
	 * Get system information.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array
	 */
	public static function get_system_info( $input = array() ) {
		// No key saved — skip the connector entirely so keyless sites never make a remote license call.
		if ( \GFCommon::get_key() ) {
			// verify-ignore: readonly -- cached read; the license connector only writes its transient on a cache miss (read-through cache).
			$license = \GFForms::get_service_container()
				->get( \Gravity_Forms\Gravity_Forms\License\GF_License_Service_Provider::LICENSE_API_CONNECTOR )
				->check_license();

			$is_licensed  = $license->is_valid();
			$license_type = self::resolve_license_type( $license );
		} else {
			$is_licensed  = false;
			$license_type = 'none';
		}

		$info = array(
			'site_url'     => get_bloginfo( 'url' ),
			'site_name'    => get_bloginfo( 'name' ),
			'version'      => \GFCommon::$version,
			'is_licensed'  => $is_licensed,
			'license_type' => $license_type,
		);

		if ( \GFCommon::current_user_can_any( array( 'gravityforms_view_addons', 'gravityforms_system_status' ) ) ) {
			$addons = array();

			foreach ( \GFAddOn::get_registered_addons( true, true ) as $slug => $addon ) {
				$addons[] = array(
					'slug'    => $slug,
					'name'    => $addon->get_short_title(),
					'version' => (string) $addon->get_version(),
				);
			}

			$info['active_addons'] = $addons;
		}

		if ( \GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			$info['total_forms'] = count( \GFFormsModel::get_form_ids( null, false ) );
		}

		if ( \GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			$info['total_entries'] = (int) \GFAPI::count_entries( 0 );
		}

		return $info;
	}

	/**
	 * List available field types.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array
	 */
	public static function list_field_types( $input = array() ) {
		$field_types = array();

		// Default input structures for compound fields.
		// These are the sub-inputs that agents need to know about for form submission.
		// Input IDs use the pattern {field_id}.{input_suffix} (e.g., field ID 1 → input_1.3 for First Name).
		$compound_inputs = self::get_compound_field_inputs();

		foreach ( \GF_Fields::get_all() as $type => $field ) {

			// Skip internal field types (submit, honeypot, product sub-types, etc.).
			// The form editor applies the same rule: fields without an editor
			// button group are not addable and should not be offered to agents.
			$editor_button = $field->get_form_editor_button();
			if ( empty( $editor_button ) || empty( $editor_button['group'] ) ) {
				continue;
			}

			$label = $field->get_form_editor_field_title();
			$label = is_string( $label ) ? $label : (string) $type;

			$field_data = array(
				'type'                       => (string) $type,
				'label'                      => $label,
				'description'                => method_exists( $field, 'get_form_editor_field_description' ) ? (string) $field->get_form_editor_field_description() : '',
				'supports_choices'           => ! empty( $field->choices ) || in_array( $type, array( 'checkbox', 'radio', 'select', 'multiselect', 'multi_choice', 'image_choice' ), true ),
				'supports_conditional_logic' => (bool) $field->is_conditional_logic_supported(),
			);

			// Expose sub-field inputs for compound fields (name, address, etc.).
			// Agents need this to know how to structure input_values for submission.
			$settings   = $field->get_form_editor_field_settings();
			$has_inputs = isset( $compound_inputs[ $type ] ) || in_array( 'default_input_values_setting', $settings, true ) || ! empty( $field->inputs );

			$field_data['has_inputs'] = $has_inputs;

			if ( isset( $compound_inputs[ $type ] ) ) {
				$field_data['default_inputs'] = $compound_inputs[ $type ];
			} elseif ( $has_inputs && ! empty( $field->inputs ) ) {
				$inputs = array();
				foreach ( $field->inputs as $sub_input ) {
					$input_entry = array(
						'id'    => (string) rgar( $sub_input, 'id' ),
						'label' => (string) rgar( $sub_input, 'label', rgar( $sub_input, 'customLabel', '' ) ),
					);

					if ( ! empty( $sub_input['isHidden'] ) ) {
						$input_entry['hidden_by_default'] = true;
					}

					$inputs[] = $input_entry;
				}
				$field_data['default_inputs'] = $inputs;
			}

			// Expose the valid phoneFormat values so agents never invent one.
			if ( 'phone' === $type ) {
				$field_data['format_options'] = GF_Ability_Schemas::phone_format_options();
			}

			// Indicate which common properties this field supports.
			$field_data['supports_placeholder']   = in_array( 'placeholder_setting', $settings, true ) || in_array( 'input_placeholders_setting', $settings, true );
			$field_data['supports_default_value'] = in_array( 'default_value_setting', $settings, true ) || in_array( 'default_input_values_setting', $settings, true );
			$field_data['supports_description']   = in_array( 'description_setting', $settings, true );
			$field_data['supports_required']      = in_array( 'rules_setting', $settings, true );

			$field_types[] = $field_data;
		}

		return array_values( $field_types );
	}

	/**
	 * Get default input structures for compound field types.
	 *
	 * Compound fields (name, address, time, etc.) store values across multiple sub-inputs.
	 * When a field has ID=1, its sub-inputs use IDs like 1.3, 1.6, etc.
	 * For form submission, these map to input names: input_1.3, input_1.6.
	 *
	 * The ID values here use a placeholder prefix that gets replaced by the actual field ID.
	 *
	 * This method is public static because it is shared across handlers — the forms
	 * handler uses it in prepare_compound_fields() to auto-populate inputs for
	 * API-created compound fields.
	 *
	 * @since 3.1.0
	 *
	 * @return array Keyed by field type slug.
	 */
	public static function get_compound_field_inputs() {
		return array(
			'name'    => array(
				array( 'id' => '.2', 'label' => 'Prefix', 'hidden_by_default' => true ),
				array( 'id' => '.3', 'label' => 'First' ),
				array( 'id' => '.4', 'label' => 'Middle', 'hidden_by_default' => true ),
				array( 'id' => '.6', 'label' => 'Last' ),
				array( 'id' => '.8', 'label' => 'Suffix', 'hidden_by_default' => true ),
			),
			'address' => array(
				array( 'id' => '.1', 'label' => 'Street Address' ),
				array( 'id' => '.2', 'label' => 'Address Line 2' ),
				array( 'id' => '.3', 'label' => 'City' ),
				array( 'id' => '.4', 'label' => 'State / Province' ),
				array( 'id' => '.5', 'label' => 'ZIP / Postal Code' ),
				array( 'id' => '.6', 'label' => 'Country' ),
			),
			'time'    => array(
				array( 'id' => '.1', 'label' => 'Hour' ),
				array( 'id' => '.2', 'label' => 'Minute' ),
				array( 'id' => '.3', 'label' => 'AM/PM' ),
			),
		);
	}

	/**
	 * Resolve the current license type from the license API response.
	 *
	 * @since 3.1.0
	 *
	 * @param \Gravity_Forms\Gravity_Forms\License\GF_License_API_Response $license The license check response.
	 *
	 * @return string
	 */
	private static function resolve_license_type( $license ) {
		if ( $license->get_data_value( 'is_expired' ) ) {
			return 'expired';
		}

		if ( ! $license->is_valid() ) {
			return 'none';
		}

		$types = array(
			'GFBASIC' => 'basic',
			'GFPRO'   => 'pro',
			'GFELITE' => 'elite',
		);

		$type = rgar( $types, strtoupper( (string) $license->get_data_value( 'product_code' ) ) );

		if ( $type ) {
			return $type;
		}

		// Legacy licenses (Personal/Business/Developer) carry other product codes; match by name.
		$name = strtolower( (string) $license->get_data_value( 'product_name' ) );

		if ( false !== strpos( $name, 'elite' ) || false !== strpos( $name, 'developer' ) ) {
			return 'elite';
		}

		if ( false !== strpos( $name, 'pro' ) || false !== strpos( $name, 'business' ) ) {
			return 'pro';
		}

		return 'basic';
	}
}
