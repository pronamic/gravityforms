<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\Full_Screen;

use Gravity_Forms\Gravity_Forms\Query\JSON_Handlers\GF_JSON_Handler;

class Full_Screen_Handler {

	const SETTING_NAME = 'form_full_screen_slug';

	/**
	 * Tokenized storage of forms discovered to avoid duplicate queries.
	 *
	 * @var array $form_found
	 */
	private static $form_found = array();

	/**
	 * Tokenized storage of the DB Version to avoid duplicate queries.
	 *
	 * @var string $db_version
	 */
	private static $db_version;

	/**
	 * The JSON query handler.
	 *
	 * @var GF_JSON_Handler
	 */
	private $json_handler;

	public function __construct( GF_JSON_Handler $handler ) {
		$this->json_handler = $handler;
	}

	/**
	 * Indicates if the given database management system version and type support the JSON_CONTAINS function.
	 *
	 * @since 2.7.1
	 *
	 * @param string $version The database management system version number.
	 * @param string $type    The database management system type.
	 *
	 * @return bool
	 */
	public static function db_supports_json_contains( $version, $type ) {
		return ( $type === 'MySQL' && version_compare( $version, '5.7.8', '>=' ) ) || ( $type === 'MariaDB' && version_compare( $version, '10.2.25', '>=' ) );
	}

	/**
	 * Get the MySQL version for the current server environment.
	 *
	 * @since 2.7
	 * @deprecated 2.7.1
	 *
	 * @return string
	 */
	public static function get_mysql_version() {
		if ( ! is_null( self::$db_version ) ) {
			return self::$db_version;
		}

		global $wpdb;

		// Query to get MySQL version. No prepare() needed since it's a static query.
		$query   = "SHOW VARIABLES LIKE 'version'";
		$results = $wpdb->get_row( $query );

		if ( empty( $results->Value ) ) {
			return null;
		}

		$version          = $results->Value;
		self::$db_version = $version;

		return $version;
	}

	/**
	 * Load the full screen template if enabled.
	 *
	 * @since 2.7
	 *
	 * @hook  template_include 10, 1
	 *
	 * @param string $template The template file passed to template_include.
	 *
	 * @return string
	 */
	public function load_full_screen_template( $template ) {
		$form_for_display = $this->get_form_for_display();

		/**
		 * External filter usable by third-party code to modify/return the form ID for display. Useful for
		 * selecting the Form ID based on externally-defined conditions.
		 *
		 * @since 2.7
		 *
		 * @param string $form_for_display The current Form ID found.
		 * @param string $template         The current templat being loaded by template_load.
		 *
		 * @return string
		 */
		$form_for_display = apply_filters( 'gform_full_screen_form_for_display', $form_for_display, $template );

		if ( ! $form_for_display ) {
			return $template;
		}

		$form = \GFFormsModel::get_form( $form_for_display );

		add_filter( 'pre_get_document_title', function ( $title ) use ( $form ) {
			return $form->title;
		} );

		$new_template = dirname( __FILE__ ) . '/views/class-full-screen-template.php';

		/**
		 * Allows third-party code to define a custom template path to load for full-screen display. Defaults to
		 * our internal view.
		 *
		 * @since 2.7
		 *
		 * @param string $new_template     The current template being loaded.
		 * @param string $form_for_display The Form ID being loaded.
		 *
		 * @return string
		 */
		$new_template = apply_filters( 'gform_full_screen_template_path', $new_template, $form_for_display );

		// WordPress 5.5 introduced the ability to pass arguments to a template partial. Use that if available.
		if ( $this->supports_template_arguments() ) {
			load_template( $new_template, true, array( 'form_id' => $form_for_display ) );

			http_response_code( 200 );

			return null;
		}

		/**
		 * Internal filter used to globally store the Form ID for the currently-detected form for usage in
		 * the template itself. Used as a workaround to pass arguments to the loaded template partial
		 * in pre-5.5 WordPress.
		 *
		 * NOTE: This isn't a good filter for dynamically modifying the Form ID being loaded. Instead, use
		 * the gform_full_screen_form_for_display filter.
		 *
		 * @since 2.7
		 *
		 * @param string $form_for_display The current Form ID detected for display.
		 *
		 * @return string.
		 */
		add_filter( 'gform_full_screen_form_id', function () use ( $form_for_display ) {
			return $form_for_display;
		}, 10, 0 );

		return $new_template;
	}

	/**
	 * Get the applicable Form ID to display for the given $slug.
	 *
	 * @since 2.7
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_form_for_display( $slug = '' ) {
		if ( ! empty( self::$form_found[ $slug ] ) ) {
			return self::$form_found[ $slug ];
		}

		global $wp;

		$post_slug = empty( $slug ) ? $wp->request : $slug;

		// Prevent empty values from hijacking the site homepage.
		if ( empty( $post_slug ) ) {
			return null;
		}

		$form_id = $this->get_form_by_slug( $post_slug );

		if ( ! empty( $form_id ) ) {
			self::$form_found[ $slug ] = $form_id;
		}

		return $form_id;
	}

	/**
	 * Check WP core version to determine if template arguments are supported.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	private function supports_template_arguments() {
		global $wp_version;

		return version_compare( $wp_version, '5.5', '>=' );
	}

	/**
	 * Use the JSON Handler to query for a form ID by slug.
	 *
	 * @since 2.7
	 *
	 * @param string $slug The page slug to query by.
	 *
	 * @return string
	 */
	private function get_form_by_slug( $slug ) {
		return $this->json_handler->query( $slug );
	}

}