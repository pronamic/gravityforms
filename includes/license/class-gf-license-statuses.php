<?php

namespace Gravity_Forms\Gravity_Forms\License;

/**
 * Class GF_License_Statuses
 *
 * Helper class to provide license statuse codes and messages. Should not be instantiated, but used statically.
 *
 * @since 2.5.11
 *
 * @package Gravity_Forms\Gravity_Forms\License
 */
class GF_License_Statuses {
	const VALID_KEY             = 'valid_key';
	const SITE_UNREGISTERED     = 'site_unregistered';
	const INVALID_LICENSE_KEY   = 'gravityapi_invalid_license';
	const EXPIRED_LICENSE_KEY   = 'gravityapi_expired_license';
	const SITE_REVOKED          = 'gravityapi_site_revoked';
	const URL_CHANGED           = 'gravityapi_site_url_changed';
	const MAX_SITES_EXCEEDED    = 'gravityapi_exceeds_number_of_sites';
	const MULTISITE_NOT_ALLOWED = 'gravityapi_multisite_not_allowed';
	const NO_LICENSE_KEY        = 'no_license_key';
	const NO_DATA               = 'rest_no_route';

	const REQUEST_BLOCKED       = 'http_request_blocked';
	const REQUEST_FAILED        = 'http_request_failed';

	const USABILITY_VALID       = 'success';
	const USABILITY_ALLOWED     = 'warning';
	const USABILITY_NOT_ALLOWED = 'error';

	/**
	 * Get the correct Message for the given code.
	 *
	 * @since 2.5.11
	 *
	 * @param $code
	 *
	 * @return mixed|string|void
	 */
	public static function get_message_for_code( $code, $error_message = '' ) {

		$general_invalid_message = sprintf(
		/* translators: %1s and %2s are link tag markup */
			__( 'The license key entered is incorrect; please visit the %1$sGravity Forms website%2$s to verify your license. ', 'gravityforms' ),
			'<a href="https://www.gravityforms.com/my-account/licenses/?utm_source=gf-admin&utm_medium=purchase-link&utm_campaign=license-enforcement" target="_blank">',
			'</a>'
		);
		$error_message = ! empty( $error_message ) ? ' ' . $error_message : '';

		$map = array(
			self::VALID_KEY             => __( 'Your license key has been successfully validated. ', 'gravityforms' ),
			self::SITE_REVOKED          => sprintf(
				/* translators: %1s and %2s are link tag markup */
				__( 'The license key entered has been revoked; please check its status in your %1$sGravity Forms account.%2$s ', 'gravityforms' ),
				'<a href="https://www.gravityforms.com/my-account/licenses/?utm_source=gf-admin&utm_medium=account-link-revoked&utm_campaign=license-enforcement" target="_blank">',
				'</a>'
			),
			self::MAX_SITES_EXCEEDED    => __( 'The license key has already been activated on its maximum number of sites; please upgrade your license. ', 'gravityforms' ),
			self::MULTISITE_NOT_ALLOWED => __( 'The license key does not support multisite installations. Please use a different license. ', 'gravityforms' ),
			self::EXPIRED_LICENSE_KEY   => sprintf(
				/* translators: %1s and %2s are link tag markup */
				__( 'The license key has expired; please visit your %1$sGravity Forms account%2$s to manage your license. ', 'gravityforms' ),
				'<a href="https://www.gravityforms.com/my-account/licenses/?utm_source=gf-admin&utm_medium=account-link-expired&utm_campaign=license-enforcement" target="_blank">',
				'</a>'
			),
			self::NO_LICENSE_KEY        => sprintf( 
				/* translators: %1$s admin link tag markup, %2$s closing markup, %3$s Gravity Forms link tag markup, %4$s closing markup  */
				__( '%1$sRegister%2$s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %3$sPurchase one now%4$s. ', 'gravityforms' ), 
				'<a href="' . admin_url() . 'admin.php?page=gf_settings">',
				'</a>', 
				'<a href="https://www.gravityforms.com" target="_blank">', 
				'</a>' 
			),
			self::REQUEST_FAILED        => sprintf(
				/* translators: %1s and %2s are link tag markup */
				__( 'There was an error while validating your license key; please try again later. If the problem persists, please %1$scontact support%2$s. ', 'gravityforms' ),
				'<a href="https://www.gravityforms.com/support/" target="_blank">',
				'</a>'
			) . $error_message,
			self::REQUEST_BLOCKED       => sprintf(
				/* translators: %1s and %2s are link tag markup */
				__( 'Your IP has been blocked by Gravity for exceeding our API rate limits. If you think this is a mistake, please %1$scontact support%2$s. ', 'gravityforms' ),
				'<a href="https://www.gravityforms.com/support/" target="_blank">',
				'</a>'
			) . $error_message,

			self::SITE_UNREGISTERED     => $general_invalid_message,
			self::INVALID_LICENSE_KEY   => $general_invalid_message,
			self::URL_CHANGED           => $general_invalid_message,
		);

		return isset( $map[ $code ] ) ? $map[ $code ] : $general_invalid_message;
	}
}
