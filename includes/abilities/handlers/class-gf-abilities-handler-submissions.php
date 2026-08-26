<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles submission ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Submissions {

	/**
	 * Submit a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function submit_form( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$input_values = self::normalize_submission_input_values( $form, $input['input_values'] );
		$input_values = self::backfill_consent_inputs( $form, $input_values );

		$result = \GFAPI::submit_form(
			absint( $input['form_id'] ),
			$input_values,
			isset( $input['field_values'] ) && is_array( $input['field_values'] ) ? $input['field_values'] : array()
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Strip the full form object — it's massive and unnecessary for the caller.
		// Keep only the actionable result data.
		$output = array(
			'is_valid' => (bool) rgar( $result, 'is_valid' ),
		);

		if ( $output['is_valid'] ) {
			// Cast to int — GFAPI::submit_form() returns the entry ID as a string from the DB,
			// but MCP clients strictly validate structuredContent against the integer output schema.
			$output['entry_id']             = (int) rgar( $result, 'entry_id' );
			$output['confirmation_message'] = rgar( $result, 'confirmation_message', '' );
			$output['confirmation_type']    = rgar( $result, 'confirmation_type', 'message' );

			if ( ! empty( $result['confirmation_redirect'] ) ) {
				$output['confirmation_redirect'] = $result['confirmation_redirect'];
			}
		} else {
			$output['validation_messages'] = self::cast_validation_messages( rgar( $result, 'validation_messages', array() ) );
			$output['page_number']         = rgar( $result, 'page_number' );
		}

		return $output;
	}

	/**
	 * Validate a form submission.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function validate_submission( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$result = \GFAPI::validate_form(
			absint( $input['form_id'] ),
			self::normalize_submission_input_values( $form, $input['input_values'] )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'is_valid'            => (bool) rgar( $result, 'is_valid' ),
			'validation_messages' => self::cast_validation_messages( rgar( $result, 'validation_messages', array() ) ),
		);
	}

	/**
	 * Backfill the consent text and revision inputs for checked consent fields.
	 *
	 * On a rendered form these arrive as hidden inputs: the consent field markup
	 * posts the checkbox label (input .2) and the form revision ID (input .3)
	 * alongside the checkbox (input .1), and GF stores them with the entry to
	 * record exactly what the user consented to. API submissions have no
	 * rendered markup, so without this the consent audit trail is saved empty.
	 * Caller-provided values are never overridden.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form         The form object.
	 * @param array $input_values Normalized input values.
	 *
	 * @return array
	 */
	private static function backfill_consent_inputs( $form, $input_values ) {
		foreach ( rgar( $form, 'fields', array() ) as $field ) {
			if ( ! is_object( $field ) || 'consent' !== $field->get_input_type() ) {
				continue;
			}

			$checked = false;

			foreach ( array( "input_{$field->id}.1", "input_{$field->id}_1" ) as $key ) {
				if ( ! empty( $input_values[ $key ] ) ) {
					$checked = true;
					break;
				}
			}

			if ( ! $checked ) {
				continue;
			}

			if ( ! self::has_consent_input( $input_values, $field->id, 2 ) ) {
				$input_values[ "input_{$field->id}.2" ] = trim( (string) $field->checkboxLabel );
			}

			if ( ! self::has_consent_input( $input_values, $field->id, 3 ) ) {
				$input_values[ "input_{$field->id}.3" ] = \GFFormsModel::get_latest_form_revisions_id( rgar( $form, 'id' ) );
			}
		}

		return $input_values;
	}

	/**
	 * Check whether a consent sub-input was provided in either key style.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input_values The input values.
	 * @param int   $field_id     The consent field ID.
	 * @param int   $suffix       The input suffix (2 or 3).
	 *
	 * @return bool
	 */
	private static function has_consent_input( $input_values, $field_id, $suffix ) {
		foreach ( array( "input_{$field_id}.{$suffix}", "input_{$field_id}_{$suffix}" ) as $key ) {
			if ( isset( $input_values[ $key ] ) && '' !== $input_values[ $key ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cast validation messages to an object so they always JSON-encode as an object.
	 *
	 * An empty PHP array encodes as a JSON array ([]), which fails the strict
	 * structuredContent validation MCP clients run against the declared object
	 * output schema. Casting to stdClass guarantees {} for the empty case and
	 * preserves the field-ID-keyed map otherwise.
	 *
	 * @since 3.1.0
	 *
	 * @param array $messages Validation messages keyed by field ID.
	 *
	 * @return \stdClass
	 */
	private static function cast_validation_messages( $messages ) {
		return (object) ( is_array( $messages ) ? $messages : array() );
	}

	/**
	 * Normalize submission input values for fields submitted as arrays.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form         The form object.
	 * @param array $input_values Raw input values from the ability payload.
	 *
	 * @return array
	 */
	private static function normalize_submission_input_values( $form, $input_values ) {
		if ( empty( $input_values ) || ! is_array( $input_values ) ) {
			return array();
		}

		foreach ( rgar( $form, 'fields', array() ) as $field ) {
			if ( ! is_object( $field ) || ! method_exists( $field, 'is_value_submission_array' ) || ! $field->is_value_submission_array() ) {
				continue;
			}

			if ( null !== $field->get_entry_inputs() || empty( $field->inputs ) || ! is_array( $field->inputs ) ) {
				continue;
			}

			$parent_key = 'input_' . $field->id;

			if ( isset( $input_values[ $parent_key ] ) && is_array( $input_values[ $parent_key ] ) ) {
				continue;
			}

			$normalized = array_fill( 0, count( $field->inputs ), null );
			$has_values = false;

			foreach ( array_values( $field->inputs ) as $index => $input ) {
				$input_id = (string) rgar( $input, 'id' );
				$suffix   = strpos( $input_id, '.' ) !== false ? substr( strrchr( $input_id, '.' ), 1 ) : (string) ( $index + 1 );
				$keys     = array(
					'input_' . $field->id . '_' . $suffix,
					'input_' . $field->id . '.' . $suffix,
				);

				foreach ( $keys as $key ) {
					if ( ! array_key_exists( $key, $input_values ) ) {
						continue;
					}

					$normalized[ $index ] = $input_values[ $key ];
					unset( $input_values[ $key ] );
					$has_values = true;
					break;
				}
			}

			if ( $has_values ) {
				$input_values[ $parent_key ] = $normalized;
			}
		}

		return $input_values;
	}
}
