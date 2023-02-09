<?php

namespace Gravity_Forms\Gravity_Forms\Query\JSON_Handlers;

/**
 * Abstract class to provide contract for JSON Handlers used to query against JSON values in the database.
 *
 * @since 2.7
 */
abstract class GF_JSON_Handler {

	const SETTING_NAME = 'form_full_screen_slug';
	const SECTION_NAME = 'gf_theme_layers';

	/**
	 * Get the correct setting name to check to enable full screen for a slug.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	protected function get_setting_name() {
		/**
		 * Filter to allow third-party code to modify the setting name being queried against in the JSON.
		 *
		 * @since 2.7
		 *
		 * @param string $setting_name The current setting name
		 *
		 * @return string
		 */
		return apply_filters( 'gform_full_screen_display_setting_name', self::SETTING_NAME );
	}

	protected function get_section_name() {
		/**
		 * Filter to allow third-party code to modify the setting section to query against in the JSON.
		 *
		 * @since 2.7
		 *
		 * @param string $section_name The current section name
		 *
		 * @return string
		 */
		return apply_filters( 'gform_full_screen_display_setting_group', self::SECTION_NAME );
	}

	/**
	 * Perform the DB query to get data.
	 *
	 * @param string $slug The slug against which to query.
	 *
	 * @return string
	 */
	abstract public function query( $slug );

}