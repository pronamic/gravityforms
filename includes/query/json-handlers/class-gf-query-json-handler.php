<?php

namespace Gravity_Forms\Gravity_Forms\Query\JSON_Handlers;

/**
 * GF_JSON_Handler implementation which uses a MySQL JSON query to gather data. More performant that string-based
 * queries, but only available in MySQL 5.7+.
 *
 * @since 2.7
 */
class GF_Query_JSON_Handler extends GF_JSON_Handler {

	/**
	 * Perform the query against the DB.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function query( $slug ) {
		global $wpdb;

		$setting_name = $this->get_setting_name();
		$section      = $this->get_section_name();

		// JSON Selector is formatted as `{"setting_name": "value"}`
		$json_selector = sprintf( '{"%s": "%s"}', $setting_name, $slug );
		$query         = "SELECT form_id FROM {$wpdb->prefix}gf_form_meta AS meta LEFT JOIN {$wpdb->prefix}gf_form AS form ON form.id = meta.form_id WHERE is_trash = 0 AND is_active = 1 AND JSON_CONTAINS(display_meta, %s, %s)";

		// To define a "section" to query against, we pass it as `$.section` as the third argument to JSON_CONTAINS.
		$prepared_query = $wpdb->prepare( $query, $json_selector, sprintf( '$.%s', $section ) );

		return $wpdb->get_var( $prepared_query );
	}

}