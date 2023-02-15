<?php

namespace Gravity_Forms\Gravity_Forms\Query\JSON_Handlers;

/**
 * GF_JSON_Handler implementation which uses a MySQL "LIKE" query. Not as performant as JSON_CONTAINS, but
 * available on older (pre-5.7) versions of MySQL.
 *
 * @since 2.7
 */
class GF_String_JSON_Handler extends GF_JSON_Handler {

	public function query( $slug ) {
		global $wpdb;

		$setting_name   = $this->get_setting_name();
		$like_statement = sprintf( '%%"%s":"%s"%%', $setting_name, $slug );
		$query          = "SELECT form_id FROM {$wpdb->prefix}gf_form_meta AS meta LEFT JOIN {$wpdb->prefix}gf_form AS form ON form.id = meta.form_id WHERE is_trash = 0 AND is_active = 1 AND display_meta LIKE %s";
		$prepared_query = $wpdb->prepare( $query, $like_statement );

		return $wpdb->get_var( $prepared_query );
	}

}