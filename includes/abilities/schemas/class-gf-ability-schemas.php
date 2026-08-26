<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Provides reusable schemas for Gravity Forms abilities.
 *
 * @since 3.1.0
 */
class GF_Ability_Schemas {

	/**
	 * Get the entry search criteria schema.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function entry_search_criteria_schema() {
		return array(
			'$id'                  => 'gravityforms-search-criteria',
			'type'                 => 'object',
			'properties'           => array(
				'status'             => array(
					'type'        => 'string',
					'enum'        => array( 'active', 'spam', 'trash' ),
					'description' => __( 'Entry status to match. Defaults to active; spam and trash are excluded unless explicitly requested.', 'gravityforms' ),
					'default'     => 'active',
				),
				'field_filters'      => array(
					'type'        => 'array',
					'description' => __( 'Field-level filters used to narrow entry results.', 'gravityforms' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'key'      => array(
								'type'        => 'string',
								'description' => __( 'Field or property key to filter on.', 'gravityforms' ),
							),
							'value'    => array(
								'type'        => 'string',
								'description' => __( 'Value to compare against.', 'gravityforms' ),
							),
							'operator' => array(
								'type'        => 'string',
								'enum'        => array( 'is', 'isnot', 'contains', '>', '<', '>=', '<=' ),
								'description' => __( 'Comparison operator for the filter.', 'gravityforms' ),
								'default'     => 'is',
							),
						),
						'additionalProperties' => false,
						'required'             => array( 'key', 'value' ),
					),
				),
				'start_date'         => array(
					'type'        => 'string',
					'description' => __( 'Inclusive start date for matching entries. Format: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.', 'gravityforms' ),
				),
				'end_date'           => array(
					'type'        => 'string',
					'description' => __( 'Inclusive end date for matching entries. Format: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.', 'gravityforms' ),
				),
				'field_filters_mode' => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'any' ),
					'default'     => 'all',
					'description' => __( 'How to combine field_filters: "all" requires every filter to match (AND), "any" requires at least one to match (OR). Defaults to "all".', 'gravityforms' ),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the sorting schema.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function sorting_schema() {
		return array(
			'$id'                  => 'gravityforms-sorting',
			'type'                 => 'object',
			'properties'           => array(
				'key'        => array(
					'type'        => 'string',
					'description' => __( 'Property name used for sorting.', 'gravityforms' ),
				),
				'direction'  => array(
					'type'        => 'string',
					'enum'        => array( 'ASC', 'DESC' ),
					'description' => __( 'Sort direction.', 'gravityforms' ),
					'default'     => 'DESC',
				),
				'is_numeric' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the sort key should be treated as numeric.', 'gravityforms' ),
					'default'     => false,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the paging schema.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function paging_schema() {
		return array(
			'$id'                  => 'gravityforms-paging',
			'type'                 => 'object',
			'properties'           => array(
				'offset'    => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'description' => __( 'Zero-based starting offset.', 'gravityforms' ),
					'default'     => 0,
				),
				'page_size' => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'description' => __( 'Maximum number of results to return.', 'gravityforms' ),
					'default'     => 20,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the form summary schema properties.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function form_summary_properties() {
		return array(
			'id'           => array( 'type' => 'integer', 'description' => __( 'Form ID.', 'gravityforms' ) ),
			'title'        => array( 'type' => 'string', 'description' => __( 'Form title.', 'gravityforms' ) ),
			'is_active'    => array( 'type' => 'string', 'description' => __( 'Whether the form is active.', 'gravityforms' ) ),
			'date_created' => array( 'type' => 'string', 'description' => __( 'Date the form was created.', 'gravityforms' ) ),
			'is_trash'     => array( 'type' => 'string', 'description' => __( 'Whether the form is in the trash.', 'gravityforms' ) ),
			'field_count'  => array( 'type' => 'integer', 'description' => __( 'Number of fields in the form.', 'gravityforms' ) ),
		);
	}

	/**
	 * Get the input schema for an ability whose only required input is a single integer ID.
	 *
	 * @since 3.1.0
	 *
	 * @param string $property    The input property name (e.g. 'form_id', 'entry_id').
	 * @param string $description The property description.
	 *
	 * @return array
	 */
	public static function single_id_input_schema( $property, $description ) {
		return array(
			'type'                 => 'object',
			'required'             => array( $property ),
			'properties'           => array(
				$property => array(
					'type'        => 'integer',
					'description' => $description,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the form_ids input property accepting a single integer or an array of integers.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function form_ids_property() {
		return array(
			'type'        => array( 'integer', 'array' ),
			'items'       => array( 'type' => 'integer' ),
			'maxItems'    => 100,
			'description' => __( 'The form ID(s) to target. Pass a single integer or an array of form IDs (max 100).', 'gravityforms' ),
		);
	}

	/**
	 * Get the output schema for abilities that only report success.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function success_output_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean', 'description' => __( 'Whether the operation succeeded.', 'gravityforms' ) ),
			),
		);
	}

	/**
	 * Get the output schema for abilities that produce a new form (create, duplicate).
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function form_mutation_output_schema() {
		return array(
			'type'       => 'object',
			'required'   => array( 'success', 'form_id', 'edit_url' ),
			'properties' => array(
				'success'  => array( 'type' => 'boolean', 'description' => __( 'Whether the operation succeeded.', 'gravityforms' ) ),
				'form_id'  => array( 'type' => 'integer', 'description' => __( 'The ID of the new form.', 'gravityforms' ) ),
				'edit_url' => array( 'type' => 'string', 'description' => __( 'Admin URL to open and edit the new form.', 'gravityforms' ) ),
			),
		);
	}

	/**
	 * Get the items schema for a form field definition array.
	 *
	 * Shared by forms-create and forms-update. The update variant includes the
	 * field id property so existing fields can be matched.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $include_field_id Whether to include the 'id' property (update variant).
	 *
	 * @return array
	 */
	public static function form_field_item_schema( $include_field_id = false ) {
		$properties = array(
			'type'                 => array( 'type' => 'string', 'description' => __( 'Field type slug. Call gravityforms/system-field-types to get the full list of available types and their capabilities.', 'gravityforms' ) ),
			'label'                => array( 'type' => 'string', 'description' => __( 'Field label displayed to users.', 'gravityforms' ) ),
			'isRequired'           => array( 'type' => 'boolean', 'description' => __( 'Whether the field is required.', 'gravityforms' ) ),
			'choices'              => array(
				'type'        => 'array',
				'description' => __( 'For select/radio/checkbox fields — array of {text, value} choice objects.', 'gravityforms' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'text'  => array( 'type' => 'string', 'description' => __( 'The display label for the choice.', 'gravityforms' ) ),
						'value' => array( 'type' => 'string', 'description' => __( 'The submitted value for the choice.', 'gravityforms' ) ),
					),
				),
			),
			'defaultValue'         => array( 'type' => 'string', 'description' => __( 'Default field value.', 'gravityforms' ) ),
			'productField'         => array(
				'type'        => 'integer',
				'description' => __( 'Pricing fields only (quantity, option): the ID of the product field this belongs to. If omitted, it links to the nearest preceding product field, or to the first product on the form when none precedes it — set it explicitly on multi-product forms.', 'gravityforms' ),
			),
			'phoneFormat'          => array(
				'type'        => 'string',
				'enum'        => self::phone_format_options(),
				'description' => __( 'Phone fields only — always set it explicitly. \'standard\' is the US format (###) ###-####, \'international\' is a plain unformatted input, \'formatted\' renders the international phone UI with a country selector (pair with defaultCountry) and stores the value as JSON.', 'gravityforms' ),
			),
			'placeholder'          => array( 'type' => 'string', 'description' => __( 'Placeholder text.', 'gravityforms' ) ),
			'emailConfirmEnabled'  => array( 'type' => 'boolean', 'description' => __( 'Email fields only — render a second Confirm Email input that must match. The two sub-inputs are added automatically.', 'gravityforms' ) ),
			'description'          => array( 'type' => 'string', 'description' => __( 'Field description / help text.', 'gravityforms' ) ),
			'cssClass'             => array( 'type' => 'string', 'description' => __( 'Custom CSS class. Do NOT use deprecated Ready Classes (gf_left_half, gf_right_half, gf_left_third, etc.) — they are removed automatically and reported in the response. Use layoutGroupId and layoutGridColumnSpan for layout instead.', 'gravityforms' ) ),
			'layoutGroupId'        => array( 'type' => 'string', 'description' => __( 'Layout group identifier. Fields sharing the same layoutGroupId render on the same row. Use any readable name (e.g. "row1", "name-row") — the server normalizes to an internal ID.', 'gravityforms' ) ),
			'layoutGridColumnSpan' => array( 'type' => 'integer', 'description' => __( 'Number of grid columns this field spans (1–12). Fields default to 12 (full width). Use 6 for half-width (two per row), 4 for third-width (three per row), etc. Fields in the same layoutGroupId render side-by-side.', 'gravityforms' ), 'minimum' => 1, 'maximum' => 12 ),
			'inputs'               => array(
				'type'        => 'array',
				'description' => __( 'Sub-input definitions for compound fields (name, address, time). Each input has an id suffix (e.g., .3 for First Name) and label. Use gravityforms/system-field-types to see default_inputs for compound field types.', 'gravityforms' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'string', 'description' => __( 'Input ID suffix (e.g., \'.3\' for First Name on a name field). The full input ID is {field_id}{suffix}.', 'gravityforms' ) ),
						'label'    => array( 'type' => 'string', 'description' => __( 'Input label.', 'gravityforms' ) ),
						'isHidden' => array( 'type' => 'boolean', 'description' => __( 'Whether this sub-input is hidden.', 'gravityforms' ) ),
					),
				),
			),
		);

		if ( $include_field_id ) {
			$properties = array( 'id' => array( 'type' => 'integer', 'description' => __( 'Field ID. Required when updating existing fields; omit for new fields being added.', 'gravityforms' ) ) ) + $properties;
		}

		return array(
			'type'       => 'object',
			'required'   => array( 'type' ),
			'properties' => $properties,
		);
	}

	/**
	 * Get the valid phone format keys from the registered phone field.
	 *
	 * Built from the live field so formats added via the gform_phone_formats
	 * filter validate instead of being rejected by the schema enum.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public static function phone_format_options() {
		$phone_field = \GF_Fields::get( 'phone' );

		if ( $phone_field instanceof \GF_Field_Phone ) {
			return array_keys( $phone_field->get_phone_formats() );
		}

		return array( 'standard', 'international', 'formatted' );
	}

	/**
	 * Get the form scheduling and entry limit settings properties.
	 *
	 * Shared by forms-create and forms-update.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function form_settings_properties() {
		return array(
			'scheduleForm'              => array( 'type' => 'boolean', 'description' => __( 'Enable form scheduling. When true, the form only accepts entries between scheduleStart and scheduleEnd dates.', 'gravityforms' ) ),
			'scheduleStart'             => array( 'type' => 'string', 'description' => __( 'Schedule start date (MM/DD/YYYY). Form shows schedulePendingMessage before this date. Omit to start immediately.', 'gravityforms' ) ),
			'scheduleEnd'               => array( 'type' => 'string', 'description' => __( 'Schedule end date (MM/DD/YYYY). Form shows scheduleMessage after this date. To pause a form immediately, set to a past date.', 'gravityforms' ) ),
			'scheduleMessage'           => array( 'type' => 'string', 'description' => __( 'Message displayed after the schedule end date. Defaults to "Sorry. This form is no longer available."', 'gravityforms' ) ),
			'schedulePendingMessage'    => array( 'type' => 'string', 'description' => __( 'Message displayed before the schedule start date. Defaults to "This form is not yet available."', 'gravityforms' ) ),
			'limitEntries'              => array( 'type' => 'boolean', 'description' => __( 'Enable entry limit. When true, form stops accepting entries after limitEntriesCount is reached.', 'gravityforms' ) ),
			'limitEntriesCount'         => array( 'type' => 'integer', 'description' => __( 'Maximum number of entries allowed.', 'gravityforms' ) ),
			'limitEntriesPeriod'        => array( 'type' => 'string', 'enum' => array( '', 'day', 'week', 'month', 'year' ), 'description' => __( 'Period for the entry limit. Empty string means total (all-time). Other values reset the count per period.', 'gravityforms' ) ),
			'limitEntriesMessage'       => array( 'type' => 'string', 'description' => __( 'Message displayed when the entry limit is reached. Defaults to "Sorry. This form is no longer accepting new submissions."', 'gravityforms' ) ),
			'enableHoneypot'            => array( 'type' => 'boolean', 'description' => __( 'Enable the anti-spam honeypot field. Required for the submission speed check to run.', 'gravityforms' ) ),
			'enableSubmitSpeedCheck'    => array( 'type' => 'boolean', 'description' => __( 'Flag submissions completed faster than submitSpeedCheckThreshold as spam. Only runs when the honeypot is enabled — enabling this also enables enableHoneypot.', 'gravityforms' ) ),
			'submitSpeedCheckThreshold' => array( 'type' => 'integer', 'description' => __( 'Submissions completed in fewer milliseconds than this are flagged as spam. Defaults to 2000.', 'gravityforms' ) ),
		);
	}

	/**
	 * Get the input_values schema for submission abilities.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function input_values_schema() {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Field values keyed by input name (e.g., \'input_1\': \'John\', \'input_2\': \'john@example.com\'). Most fields use string values. Multiselect fields accept an array of selected values (e.g., \'input_3\': [\'Red\', \'Blue\']).', 'gravityforms' ),
			'additionalProperties' => array(
				'type'  => array( 'string', 'array' ),
				'items' => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Get the validation_messages output schema for submission abilities.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function validation_messages_schema() {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Field-level validation errors keyed by field ID (only if is_valid is false).', 'gravityforms' ),
			'additionalProperties' => array( 'type' => 'string' ),
		);
	}

	/**
	 * Get the entry summary schema properties.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function entry_summary_properties() {
		return array(
			'id'           => array( 'type' => 'string', 'description' => __( 'Entry ID.', 'gravityforms' ) ),
			'form_id'      => array( 'type' => 'string', 'description' => __( 'The form this entry belongs to.', 'gravityforms' ) ),
			'date_created' => array( 'type' => 'string', 'description' => __( 'Submission date (UTC).', 'gravityforms' ) ),
			'date_updated' => array( 'type' => 'string', 'description' => __( 'Last update date (UTC).', 'gravityforms' ) ),
			'is_starred'   => array( 'type' => 'string', 'description' => __( 'Whether the entry is starred.', 'gravityforms' ) ),
			'is_read'      => array( 'type' => 'string', 'description' => __( 'Whether the entry has been read.', 'gravityforms' ) ),
			'ip'           => array( 'type' => 'string', 'description' => __( 'Submitter IP address.', 'gravityforms' ) ),
			'source_url'   => array( 'type' => 'string', 'description' => __( 'Source URL for the submission.', 'gravityforms' ) ),
			'status'       => array( 'type' => 'string', 'enum' => array( 'active', 'spam', 'trash' ) ),
			'created_by'   => array( 'type' => 'string', 'description' => __( 'WordPress user ID if logged in.', 'gravityforms' ) ),
		);
	}
}
