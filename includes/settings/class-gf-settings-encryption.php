<?php
/**
 * Handles logic for encrypting / decrypting settings.
 *
 * @package Gravity_Forms\Gravity_Forms\Honeypot
 */

namespace Gravity_Forms\Gravity_Forms\Settings;

/**
 * Class GF_Settings_Encryption
 *
 * @since 2.7.17
 *
 * Provides functionality for handling the encryption and decryption of settings
 */
class GF_Settings_Encryption {

	/**
	 * The encryption key to use for encrypting / decrypting settings.
	 *
	 * @since 2.7.17
	 *
	 * @var string The encryption key to use for encrypting / decrypting settings.
	 */
	private $_encryption_key;

	/**
	 * Constructor.
	 *
	 * @since 2.7.17
	 *
	 * @param string $encryption_key Encryption key to use for encrypting / decrypting settings. Defaults to GF_ENCRYPTION_KEY constant.
	 *
	 * @return void
	 */
	function __construct( $encryption_key = '' ) {
		if ( ! empty( $encryption_key ) ) {
			$this->_encryption_key = $encryption_key;
		} else {
			$this->_encryption_key = defined( 'GF_ENCRYPTION_KEY' ) ? GF_ENCRYPTION_KEY : '';
		}
	}

	/**
	 * Determines if settings encryption is enabled.
	 *
	 * @since 2.7.17
	 *
	 * @return bool True if settings encryption is enabled. False otherwise.
	 *
	 */
	public function is_enabled() {
		return ! empty( $this->get_key() );
	}

	/**
	 * Returns the value of the GF_ENCRYPTION_KEY constant for the settings page.
	 *
	 * @since 2.7.17
	 *
	 * @return string The value of the GF_ENCRYPTION_KEY constant.
	 */
	public function get_key() {
		return $this->_encryption_key;
	}

	/**
	 * Will encrypt a settings array given it has encryption enabled, is not an empty array, and is not currently encrypted.
	 *
	 * @since 2.7.17
	 *
	 * @param array $settings An array of settings values
	 *
	 * @return false|mixed|string Will return a json block containing the encrypted setting values, otherwise returns original setting values.
	 */
	public function encrypt( $settings ) {
		if ( ! $this->is_enabled() ) {
			return $settings;
		}

		$settings_wrapper = $this->get_wrapper( $settings );
		if ( ! empty( $settings_wrapper ) && ! rgar( $settings_wrapper, 'encrypted' ) ) {
			$key      = $this->get_key();
			$settings = json_encode(
				array(
					'encrypted' => true,
					'value'     => \GFCommon::openssl_encrypt( json_encode( $settings_wrapper ), $key, 'aes-256-ctr', $key ),
				)
			);
		}

		return $settings;
	}

	/**
	 * Will decrypt settings given it has encryption enabled and is currently encrypted.
	 *
	 * @since 2.7.17
	 *
	 * @param $settings
	 *
	 * @return mixed Will return the decrypted settings values, otherwise returns original settings values.
	 */
	public function decrypt( $settings ) {
		if ( ! $this->is_enabled() ) {
			return $settings;
		}

		$settings = $this->get_wrapper( $settings );
		if ( rgar( $settings, 'encrypted' ) ) {
			$key      = $this->get_key();
			$settings = json_decode( \GFCommon::openssl_decrypt( rgar( $settings, 'value' ), $key, 'aes-256-ctr', $key ), true );
		}

		return $settings;
	}

	/**
	 * For encrypted settings, returns the decoded JSON wrapper.
	 *
	 * @since 2.7.17
	 *
	 * @param string $setting a settings value
	 *
	 * @return bool  returns the decoded JSON wrapper for encrypted settings, otherwise returns the original settings.
	 */
	public function get_wrapper( $setting ) {
		if ( empty( $setting ) || ! is_string( $setting ) ) {
			return $setting;
		}

		$wrapper = json_decode( $setting, true );
		if ( ! rgar( $wrapper, 'encrypted' ) ) {
			return $setting;
		}

		return $wrapper;
	}

	/**
	 * When encryption is enabled, will decrypt all fields that are encrypted and return the resulting array.
	 *
	 * @since 2.7.17
	 *
	 * @param $meta array The feed meta array.
	 *
	 * @return array Returns the feed meta array with all fields decrypted.
	 */
	public function decrypt_feed_meta( $meta ) {

		if ( ! $this->is_enabled() || ! is_array( $meta ) ) {
			return $meta;
		}

		foreach ( $meta as $name => $value ) {
			if ( ! empty( $value ) ) {
				$meta[ $name ] = $this->decrypt( $value );
			}
		}

		return $meta;
	}

	/**
	 * When encryption is enabled, will encrypt all fields marked for encryption and return the resulting array.
	 *
	 * @since 2.7.17
	 *
	 * @param $meta array The feed meta array.
	 * @param $fields_to_encrypt array The array of field names to encrypt.
	 *
	 * @return array Returns the feed meta array with all fields marked for encryption encrypted.
	 */
	public function encrypt_feed_meta( $meta, $fields_to_encrypt = array() ) {

		if ( ! $this->is_enabled() || ! is_array( $meta ) ) {
			return $meta;
		}

		foreach ( $meta as $name => $value ) {
			if ( ! empty( $value ) && in_array( $name, $fields_to_encrypt ) ) {
				$meta[ $name ] = $this->encrypt( $value );
			}
		}

		return $meta;
	}
}
