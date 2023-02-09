<?php

namespace Gravity_Forms\Gravity_Forms\License;

use Gravity_Forms\Gravity_Forms\External_API\GF_API_Response;
use Gravity_Forms\Gravity_Forms\Transients\GF_Transient_Strategy;

/**
 * Class GF_License_API_Response
 *
 * Concrete Response class for the GF License API.
 *
 * @since 2.5.11
 *
 * @package Gravity_Forms\Gravity_Forms\License
 */
class GF_License_API_Response extends GF_API_Response {

	/**
	 * @var GF_Transient_Strategy
	 */
	private $transient_strategy;

	/**
	 * GF_License_API_Response constructor.
	 *
	 * @since 2.5.11
	 *
	 * @param mixed $data The data from the API connector.
	 * @param bool $validate Whether to validate the data passed.
	 * @param GF_Transient_Strategy $transient_strategy The Transient Strategy used to store things in transients.
	 */
	public function __construct( $data, $validate, GF_Transient_Strategy $transient_strategy ) {
		$this->transient_strategy = $transient_strategy;

		// Data is a wp_error, parse it to get the correct code and message.
		if ( is_wp_error( $data ) ) {
			/**
			 * @var \WP_Error $data
			 */
			if ( $data->get_error_code() == 'rest_invalid_param' ) {
				$this->set_status( GF_License_Statuses::INVALID_LICENSE_KEY );
				$this->add_error( __( 'The license is invalid.', 'gravityforms' ) );
			} else {
				$this->set_status( $data->get_error_code() );
				$this->add_error( $data->get_error_message() );
			}

			if ( empty( $data->get_error_data() ) ) {
				return;
			}

			$error_data = $data->get_error_data();

			if ( rgar( $error_data, 'license' ) ) {
				$error_data = rgar( $error_data, 'license' );
			}

			$this->add_data_item( $error_data );

			return;
		}

		// Data is somehow broken; set a status for Invalid license keys and bail.
		if ( ! is_array( $data ) ) {
			$this->set_status( GF_License_Statuses::INVALID_LICENSE_KEY );
			$this->add_error( GF_License_Statuses::get_message_for_code( GF_License_Statuses::INVALID_LICENSE_KEY ) );

			return;
		}

		// Set is_valid to true since we are bypassing validation.
		if ( ! $validate ) {
			$data['is_valid'] = true;
		}

		// Data is formatted properly, but the `is_valid` param is false. Return an invalid license key error.
		if ( isset( $data['is_valid'] ) && ! $data['is_valid'] ) {
			$this->set_status( GF_License_Statuses::INVALID_LICENSE_KEY );
			$this->add_error( GF_License_Statuses::get_message_for_code( GF_License_Statuses::INVALID_LICENSE_KEY ) );

			return;
		}

		// Finally, the data is correct, so store it and set our status to valid.
		$this->add_data_item( $data );
		$this->set_status( GF_License_Statuses::VALID_KEY );
	}

	/**
	 * Get the stored error for this site license.
	 *
	 * @since 2.5.11
	 *
	 * @return \WP_Error|false
	 */
	private function get_stored_error() {
		return $this->transient_strategy->get( 'rg_gforms_registration_error' );
	}

	/**
	 * Whether this license key is valid.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function is_valid() {
		if ( empty( $this->data ) || $this->get_status() === GF_License_Statuses::NO_DATA ) {
			return false;
		}

		if ( ! $this->has_errors() ) {
			return (bool) $this->get_data_value( 'is_valid' );
		}

		return $this->get_status() !== GF_License_Statuses::INVALID_LICENSE_KEY;
	}

	/**
	 * Get the error message for the response, either the first one by default, or at a specific index.
	 *
	 * @since 2.5.11
	 *
	 * @param int $index The array index to use if mulitple errors exist.
	 *
	 * @return mixed|string
	 */
	public function get_error_message( $index = 0 ) {
		if ( ! $this->has_errors() ) {
			return '';
		}

		return $this->errors[ $index ];
	}

	/**
	 * Get the human-readable display status for the response.
	 *
	 * @since 2.5.11
	 *
	 * @return string|void
	 */
	public function get_display_status() {

		if ( $this->max_seats_exceeded() ) {
			return __( 'Sites Exceeded', 'gravityforms' );
		}

		switch ( $this->get_status() ) {
			case GF_License_Statuses::INVALID_LICENSE_KEY:
				return __( 'Invalid', 'gravityforms' );
			case GF_License_Statuses::EXPIRED_LICENSE_KEY:
				return __( 'Expired', 'gravityforms' );
			case GF_License_Statuses::VALID_KEY:
			default:
				return __( 'Active', 'gravityforms' );
		}
	}

	/**
	 * Licenses can be valid and usable, technically-invalid but still usable, or invalid and unusable.
	 * This will return the correct usability value for this license key.
	 *
	 * @since 2.5.11
	 *
	 * @return string
	 */
	public function get_usability() {
		if ( $this->get_status() === GF_License_Statuses::VALID_KEY || $this->get_status() === GF_License_Statuses::NO_DATA ) {
			return GF_License_Statuses::USABILITY_VALID;
		}

		if ( $this->get_status() === GF_License_Statuses::INVALID_LICENSE_KEY || $this->get_status() === GF_License_Statuses::SITE_REVOKED ) {
			return GF_License_Statuses::USABILITY_NOT_ALLOWED;
		}

		return GF_License_Statuses::USABILITY_ALLOWED;
	}

	//----------------------------------------
	//---------- Helpers/Utils ---------------
	//----------------------------------------

	/**
	 * Whether this response has any errors stored as a transient.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	private function has_stored_error() {
		return (bool) $this->get_stored_error();
	}

	/**
	 * Get a properly-formatted link to the Upgrade page for this license key.
	 *
	 * @since 2.5.11
	 *
	 * @return string
	 */
	public function get_upgrade_link() {
		$key  = $this->get_data_value( 'license_key_md5' );
		$type = $this->get_data_value( 'product_code' );

		return sprintf( 'https://www.gravityforms.com/my-account/licenses/?action=upgrade&license_key=%s&license_code=%s&utm_source=gf-admin&utm_medium=upgrade-button&utm_campaign=license-enforcement', $key, $type );
	}

	/**
	 * Get the CTA information for this license key, if applicable.
	 *
	 * @since 2.5.11
	 *
	 * @return mixed
	 */
	public function get_cta() {

		if ( $this->get_status() == GF_License_Statuses::EXPIRED_LICENSE_KEY ) {
			return array(
				'type'  => 'button',
				'label' => __( 'Manage', 'gravityforms' ),
				'link'  => 'https://www.gravityforms.com/my-account/licenses/?utm_source=gf-admin&utm_medium=manage-button&utm_campaign=license-enforcement',
				'class' => 'cog',
			);
		} elseif ( $this->max_seats_exceeded() ) {
			return array(
				'type'  => 'button',
				'label' => __( 'Upgrade', 'gravityforms' ),
				'link'  => $this->get_upgrade_link(),
				'class' => 'product',
			);
		} else if ( $this->has_expiration() ) {
			return array(
				'type'    => 'text',
				'content' => $this->get_data_value( 'days_to_expire' ),
			);
		} else {
			return array(
				'type'    => 'blank',
			);
		}
	}

	/**
	 * Some statuses are invalid, but get treated as usable. This determines if they should be displayed as
	 * though they are valid.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function display_as_valid() {

		if ( $this->max_seats_exceeded() ) {
			return false;
		}
		switch ( $this->get_status() ) {
			case GF_License_Statuses::INVALID_LICENSE_KEY:
			case GF_License_Statuses::EXPIRED_LICENSE_KEY:
				return false;
			case GF_License_Statuses::VALID_KEY:
			default:
				return true;
		}
	}

	/**
	 * Whether the license key can be used.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function can_be_used() {
		return $this->get_usability() !== GF_License_Statuses::USABILITY_NOT_ALLOWED;
	}

	/**
	 * Determine if the contained License Key has an expiration date.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function has_expiration() {
		return $this->get_data_value( 'date_expires' ) && ! $this->get_data_value( 'renewal_date' ) && ! $this->get_data_value( 'is_perpetual' );
	}

	/**
	 * Get the text for the renewal message.
	 *
	 * @since 2.5.11
	 *
	 * @return string
	 */
	public function renewal_text() {
		if ( $this->get_status() === GF_License_Statuses::EXPIRED_LICENSE_KEY ) {
			return __( 'Expired On', 'gravityforms' );
		}

		$has_subscription = (bool) $this->get_data_value( 'renewal_date' );
		$cancelled        = (bool) $this->get_data_value( 'is_subscription_canceled' );

		if ( $has_subscription && ! $cancelled ) {
			return __( 'Renews On', 'gravityforms' );
		}

		return __( 'Expires On', 'gravityforms' );
	}

	/**
	 * Returns the license renewal or expiry date or the doesn't expire message.
	 *
	 * @since 2.6.2
	 *
	 * @return string|void
	 */
	public function renewal_date() {
		if ( $this->get_data_value( 'is_perpetual' ) ) {
			return __( 'Does not expire', 'gravityforms' );
		}

		$date = $this->get_data_value( 'renewal_date' );
		if ( empty( $date ) ) {
			$date = $this->get_data_value( 'date_expires' );
		}

		return gmdate( 'M d, Y', strtotime( $date ) );
	}

	/**
	 * Whether the license has max seats exceeded.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function max_seats_exceeded() {
		return $this->get_status() === GF_License_Statuses::MAX_SITES_EXCEEDED || $this->get_data_value( 'remaining_seats' ) < 0;
	}

	//----------------------------------------
	//---------- Serialization ---------------
	//----------------------------------------

	/**
	 * Prepares the object for serializing.
	 *
	 * @since 2.6.2
	 *
	 * @return array
	 */
	public function __serialize() {
		return array(
			'data'   => $this->data,
			'errors' => $this->errors,
			'status' => $this->status,
			'meta'   => $this->meta,
			'strat'  => $this->transient_strategy,
		);
	}

	/**
	 * Hydrates the object when unserializing.
	 *
	 * @since 2.6.2
	 *
	 * @param array $data The unserialized data.
	 *
	 * @return void
	 */
	public function __unserialize( $data ) {
		$this->data               = $data['data'];
		$this->errors             = $data['errors'];
		$this->status             = $data['status'];
		$this->meta               = $data['meta'];
		$this->transient_strategy = $data['strat'];
	}

}
