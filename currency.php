<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'RGCurrency' ) ) {

	class RGCurrency {
		private $currency;

		public function __construct( $currency ) {
			if ( is_array( $currency ) ) {
				$this->currency = $currency;
			} else {
				$this->currency = self::get_currency( $currency );
			}
		}

		/**
		 * Removes currency formatting from a value.
		 *
		 * @since unknown
		 *
		 * @param string|float|int $text The value to be cleaned of currency formatting.
		 *
		 * @return false|float|int
		 */
		public function to_number( $text ) {
			$text = strval( $text );

			if ( is_numeric( $text ) ) {
				return $this->convert_number( $text );
			}

			//Making sure symbol is in unicode format (i.e. &#4444;)
			$text = preg_replace( '/&.*?;/', '', $text );

			//Removing symbol from text
			$text = str_replace( $this->currency['symbol_right'], '', $text );
			$text = str_replace( $this->currency['symbol_left'], '', $text );
			if ( ! empty( $this->currency['symbol_old'] ) ) {
				$text = str_replace( $this->currency['symbol_old'], '', $text );
			}

			//Removing all non-numeric characters
			$array        = str_split( $text );
			$is_negative  = false;
			$clean_number = '';
			foreach ( $array as $char ) {

				if ( ( $char >= '0' && $char <= '9' ) || $char == $this->currency['decimal_separator'] ) {
					$clean_number .= $char;
				} elseif ( $char == '-' ) {
					$is_negative = true;
				}
			}

			$decimal_separator = $this->currency && $this->currency['decimal_separator'] ? $this->currency['decimal_separator'] : '.';

			//Removing thousand separators but keeping decimal point
			$array        = str_split( $clean_number );
			$float_number = '';
			for ( $i = 0, $count = sizeof( $array ); $i < $count; $i ++ ) {
				$char = $array[ $i ];

				if ( $char >= '0' && $char <= '9' ) {
					$float_number .= $char;
				} elseif ( $char == $decimal_separator ) {
					$float_number .= '.';
				}
			}

			if ( $is_negative ) {
				$float_number = '-' . $float_number;
			}

			if ( ! is_numeric( $float_number ) ) {
				return false;
			}

			return $this->convert_number( $float_number );
		}

		/**
		 * Returns the given value as an integer or float based on the decimal configuration of the current currency.
		 *
		 * @since 2.6.1
		 *
		 * @param string $value The value to be converted.
		 *
		 * @return float|int
		 */
		private function convert_number( $value ) {
			return $this->is_zero_decimal() ? intval( $value ) : floatval( $value );
		}

		public function to_money( $number, $do_encode = false ) {

			if ( ! is_numeric( $number ) ) {
				$number = $this->to_number( $number );
			}

			if ( $number === false ) {
				return '';
			}

			$negative = '';
			if ( strpos( strval( $number ), '-' ) !== false ) {
				$negative = '-';
				$number   = floatval( substr( $number, 1 ) );
			}

			$money = number_format( $number, $this->currency['decimals'], $this->currency['decimal_separator'], $this->currency['thousand_separator'] );

			if ( $money == '0.00' ) {
				$negative = '';
			}

			$symbol_left  = ! empty( $this->currency['symbol_left'] ) ? $this->currency['symbol_left'] . $this->currency['symbol_padding'] : '';
			$symbol_right = ! empty( $this->currency['symbol_right'] ) ? $this->currency['symbol_padding'] . $this->currency['symbol_right'] : '';

			if ( $do_encode ) {
				$symbol_left  = html_entity_decode( $symbol_left );
				$symbol_right = html_entity_decode( $symbol_right );
			}

			return $negative . $symbol_left . $money . $symbol_right;
		}

		public static function get_currency( $code ) {
			$currencies = self::get_currencies();

			return $currencies[ $code ];
		}

		public function is_zero_decimal() {

			return empty( $this->currency['decimals'] );
		}

		/**
		 * Returns an array that contains all the supported currencies and their configurations.
		 *
		 * @since unknown.
		 * @since 2.5.13 add currency code to the configuration array.
		 *
		 * @return array
		 */
		public static function get_currencies() {
			$currencies = array(
				'USD' => array(
					'name'               => esc_html__( 'U.S. Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => '',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'USD',
				),
				'GBP' => array(
					'name'               => esc_html__( 'Pound Sterling', 'gravityforms' ),
					'symbol_left'        => '&#163;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'GBP',
				),
				'EUR' => array(
					'name'               => esc_html__( 'Euro', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => '&#8364;',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'EUR',
				),
				'AUD' => array(
					'name'               => esc_html__( 'Australian Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'AUD',
				),
				'BRL' => array(
					'name'               => esc_html__( 'Brazilian Real', 'gravityforms' ),
					'symbol_left'        => 'R$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'BRL',
				),
				'CAD' => array(
					'name'               => esc_html__( 'Canadian Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => 'CAD',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'CAD',
				),
				'CZK' => array(
					'name'               => esc_html__( 'Czech Koruna', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => '&#75;&#269;',
					'symbol_padding'     => ' ',
					'thousand_separator' => ' ',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'CZK',
				),
				'DKK' => array(
					'name'               => esc_html__( 'Danish Krone', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => 'kr.',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'DKK',
				),
				'HKD' => array(
					'name'               => esc_html__( 'Hong Kong Dollar', 'gravityforms' ),
					'symbol_left'        => 'HK$',
					'symbol_right'       => '',
					'symbol_padding'     => '',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'HKD',
				),
				'HUF' => array(
					'name'               => esc_html__( 'Hungarian Forint', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => 'Ft',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'HUF',
				),
				'ILS' => array(
					'name'               => esc_html__( 'Israeli New Sheqel', 'gravityforms' ),
					'symbol_left'        => '&#8362;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'ILS',
				),
				'JPY' => array(
					'name'               => esc_html__( 'Japanese Yen', 'gravityforms' ),
					'symbol_left'        => '&#165;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '',
					'decimals'           => 0,
					'code'               => 'JPY',
				),
				'MYR' => array(
					'name'               => esc_html__( 'Malaysian Ringgit', 'gravityforms' ),
					'symbol_left'        => '&#82;&#77;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'MYR',
				),
				'MXN' => array(
					'name'               => esc_html__( 'Mexican Peso', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'MXN',
				),
				'NOK' => array(
					'name'               => esc_html__( 'Norwegian Krone', 'gravityforms' ),
					'symbol_left'        => 'Kr',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'NOK',
				),
				'NZD' => array(
					'name'               => esc_html__( 'New Zealand Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'NZD',
				),
				'PHP' => array(
					'name'               => esc_html__( 'Philippine Peso', 'gravityforms' ),
					'symbol_left'        => 'Php',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'PHP', // bun not intended.
				),
				'PLN' => array(
					'name'               => esc_html__( 'Polish Zloty', 'gravityforms' ),
					'symbol_left'        => '&#122;&#322;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => '.',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'PLN',
				),
				'RUB' => array(
					'name'               => esc_html__( 'Russian Ruble', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => 'pyб',
					'symbol_padding'     => ' ',
					'thousand_separator' => ' ',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'RUB',
				),
				'SGD' => array(
					'name'               => esc_html__( 'Singapore Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'SGD',
				),
				'ZAR' => array(
					'name'               => esc_html__( 'South African Rand', 'gravityforms' ),
					'symbol_left'        => 'R',
					'symbol_right'       => '',
					'symbol_padding'     => '',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'ZAR',
				),
				'SEK' => array(
					'name'               => esc_html__( 'Swedish Krona', 'gravityforms' ),
					'symbol_left'        => '',
					'symbol_right'       => 'Kr',
					'symbol_padding'     => ' ',
					'thousand_separator' => ' ',
					'decimal_separator'  => ',',
					'decimals'           => 2,
					'code'               => 'SEK',
				),
				'CHF' => array(
					'name'               => esc_html__( 'Swiss Franc', 'gravityforms' ),
					'symbol_left'        => 'CHF',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => "'",
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'symbol_old'         => 'Fr.',
					'code'               => 'CHF',
				),
				'TWD' => array(
					'name'               => esc_html__( 'Taiwan New Dollar', 'gravityforms' ),
					'symbol_left'        => '$',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'TWD',
				),
				'THB' => array(
					'name'               => esc_html__( 'Thai Baht', 'gravityforms' ),
					'symbol_left'        => '&#3647;',
					'symbol_right'       => '',
					'symbol_padding'     => ' ',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'decimals'           => 2,
					'code'               => 'THB',
				),
			);

			return apply_filters( 'gform_currencies', $currencies );
		}
	}

}
