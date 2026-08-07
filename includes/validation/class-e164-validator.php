<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class E164Validator
 *
 * Validates phone numbers against E.164 format using territory-based rules extracted from the Google's libphonenumber library.
 *
 * @since 2.9.0
 */
class E164Validator {

	/**
	 * The validation rules for all territories.
	 *
	 * @since  2.9.0
	 * @access private
	 *
	 * @var array The validation rules array.
	 */
	private $rules;

	/**
	 * Cached validation rules shared across all instances.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @var array|null The cached validation rules array.
	 */
	private static $cached_rules = null;

	/**
	 * E164Validator constructor.
	 *
	 * @since 2.9.0
	 * @access public
	 */
	public function __construct() {
		if ( self::$cached_rules === null ) {
			$E164_VALIDATION_RULES = array();
			require dirname( __FILE__ ) . '/e164-validation-rules.php';
			self::$cached_rules = $E164_VALIDATION_RULES;
		}
		$this->rules = self::$cached_rules;
	}

	/**
	 * Validates a phone number against E.164 format and territory-specific rules.
	 *
	 * @since  2.9.0
	 * @access public
	 *
	 * @param string $phone_number The phone number to validate in E.164 format.
	 * @param bool   $detailed     Whether to return detailed validation information. Defaults to false.
	 *
	 * @return bool|array Returns boolean validation result or detailed array if $detailed is true.
	 */
	public function validate( $phone_number, $detailed = false ) {
		// Basic E.164 format check.
		if ( ! preg_match( '/^\+[1-9]\d{1,14}$/', $phone_number ) ) {
			return $this->result( false, esc_html__( 'Please enter a valid phone number.', 'gravityforms' ), $detailed );
		}

		// Remove + sign.
		$number = substr( $phone_number, 1 );

		// Try to match country code (longest first).
		foreach ( $this->rules as $country_code => $territories ) {
			if ( strpos( $number, (string) $country_code ) === 0 ) {
				$national_number = substr( $number, strlen( $country_code ) );

				// Try each territory for this country code.
				foreach ( $territories as $territory ) {
					$result = $this->validate_territory( $national_number, $territory );
					if ( $result['valid'] ) {
						return $this->result(
							true,
							esc_html__( 'Valid', 'gravityforms' ),
							$detailed,
							array(
								'country_code'    => $country_code,
								'territory'       => $territory['territory_id'],
								'national_number' => $national_number,
								'type'            => $result['type']
							)
						);
					}
				}

				// Country code matched but number invalid.
				return $this->result(
					false,
					esc_html__( 'The phone number appears to be incomplete or invalid for the selected country.', 'gravityforms' ),
					$detailed,
					array( 'country_code' => $country_code, 'national_number' => $national_number )
				);
			}
		}

		return $this->result( false, esc_html__( 'Please enter a valid phone number.', 'gravityforms' ), $detailed );
	}

	/**
	 * Validates a national number against territory-specific rules.
	 *
	 * @since  2.9.0
	 * @access private
	 *
	 * @param string $national_number The national part of the phone number.
	 * @param array  $territory       The territory rules array.
	 *
	 * @return array Validation result with 'valid' boolean and 'type' if valid.
	 */
	private function validate_territory( $national_number, $territory ) {
		$national_length = strlen( $national_number );

		// First try specific number types.
		foreach ( $territory['types'] as $type => $type_rule ) {
			// Check length.
			if ( ! empty( $type_rule['lengths'] ) && ! in_array( $national_length, $type_rule['lengths'] ) ) {
				continue;
			}

			// Check pattern.
			if ( ! empty( $type_rule['pattern'] ) ) {
				$pattern = $this->convert_pattern( $type_rule['pattern'] );
				if ( preg_match( $pattern, $national_number ) ) {
					return array( 'valid' => true, 'type' => $type );
				}
			}
		}

		// Try general pattern as fallback.
		if ( ! empty( $territory['lengths'] ) && in_array( $national_length, $territory['lengths'] ) ) {
			if ( ! empty( $territory['pattern'] ) ) {
				$pattern = $this->convert_pattern( $territory['pattern'] );
				if ( preg_match( $pattern, $national_number ) ) {
					return array( 'valid' => true, 'type' => 'general' );
				}
			}
		}

		return array( 'valid' => false );
	}

	/**
	 * Converts Java regex pattern to PHP regex pattern.
	 *
	 * @since  2.9.0
	 * @access private
	 *
	 * @param string $java_pattern The Java regex pattern from libphonenumber metadata.
	 *
	 * @return string The PHP regex pattern with delimiters.
	 */
	private function convert_pattern( $java_pattern ) {
		// Most patterns from the original libphonenumber metadata (Java regex) work as-is, just need delimiters.
		return '/^' . $java_pattern . '$/';
	}

	/**
	 * Formats the validation result based on detail level requested.
	 *
	 * @since  2.9.0
	 * @access private
	 *
	 * @param bool   $valid    Whether the validation passed.
	 * @param string $message  The validation message.
	 * @param bool   $detailed Whether to return detailed information.
	 * @param array  $data     Additional data for detailed response. Defaults to empty array.
	 *
	 * @return bool|array Returns boolean for simple validation or array for detailed validation.
	 */
	private function result( $valid, $message, $detailed, $data = array() ) {
		// Detailed version used mostly for debugging.
		if ( $detailed ) {
			return array_merge(
				array( 'valid' => $valid, 'message' => $message ),
				$data
			);
		}
		return $valid;
	}
}
