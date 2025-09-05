<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

use Gravity_Forms\Gravity_Forms\TranslationsPress_Updater;

/**
 * Class GF_System_Report
 *
 * Handles the System Report subview on the System Status page.
 *
 * @since 2.2
 */
class GF_System_Report {

	/**
	 * Whether background tasks are enabled.
	 *
	 * @since 2.3.0.3
	 *
	 * @var null|bool
	 */
	public static $background_tasks = null;


	/**
	 * Remove WordPress's emoji scripts and styles from the system report page.
	 *
	 * Can be removed when WordPress has full support for the wp-exclude-emoji class.
	 *
	 * @since 2.7.1
	 *
	 * @return void
	 */
	public function remove_emoji_script() {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Display system report page.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFSystemReport::get_system_report()
	 * @uses GFSystemReport::maybe_process_action()
	 * @uses GFSystemReport::prepare_item_value()
	 * @uses GFSystemStatus::page_footer()
	 * @uses GFSystemStatus::page_header()
	 */
	public static function system_report() {

		// Process page actions.
		self::maybe_process_action();

		// Display page header.
		GF_System_Status::page_header();

		// Get system report sections.
		$sections           = self::get_system_report();
		$system_report_text = self::get_system_report_text( $sections );

		?>

		<div class="alert info">
			<p><?php esc_html_e( 'The following is a system report containing useful technical information for troubleshooting issues. If you need further help after viewing the report, click on the "Copy System Report" button below to copy the report and paste it in your message to support.', 'gravityforms' ); ?></p>

			<button class="gform-button gform-button--size-r gform-button--white gform-button--icon-leading gform-system-report__copy-button" data-js="gf-copy-system-report">
				<i class="gform-button__icon gform-button__icon--inactive gform-icon gform-icon--copy" data-js="button-icon"></i>

				<span class="gform-system-report__copy-label" data-js="system-status-copy-label" aria-hidden="false">Copy System Report</span>
				<span class="gform-system-report__copy-copied" data-js="system-status-copy-copied" aria-hidden="true">
					<i class="gform-system-report__copy-icon gform-icon gform-icon--circle-check-alt"></i>
					Copied
				</span>
			</button>

			<div id="gform-system-report-text" class="gform-system-report__text wp-exclude-emoji" aria-hidden="true" data-js="system-report-text" ><?php echo esc_html( $system_report_text ) ?></div>
		</div>

		<form method="post" id="gf_system_report_form" data-js="system-report-form" class="wp-exclude-emoji">
			<input type="hidden" name="gf_action" id="gf_action" data-js="system-report-action" />
			<input type="hidden" name="gf_arg" id="gf_arg" data-js="system-report-action-arg"/>

		<?php
		wp_nonce_field( 'gf_sytem_report_action', 'gf_sytem_report_action' );

		// Loop through system report sections.
		foreach ( $sections as $i => $section ) {


			// Display section title.
			echo '<h3><span>' . $section['title'] . '</span></h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// Loop through tables.
			foreach ( $section['tables'] as $table ) {

				if ( ! isset( $table['items'] ) || empty( $table['items'] ) ) {
					continue;
				}

				// Open section table.
				echo '<table class="gform_system_report wp-list-table fixed striped feeds">';

				// Add table caption.
				echo '<caption>' . rgar( $table, 'title' ) . '</caption>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				// Add table headers (for screen readers and accessibility).
				echo '<thead class="screen-reader-text"><tr><th scope="col">'. esc_html__( 'Setting', 'gravityforms' ) .'</th><th scope="col">'. esc_html__( 'Value', 'gravityforms' ) .'</th></tr></thead>';

				// Open table body.
				echo '<tbody id="the-list" data-wp-lists="list:feed">';

				// Loop through section items.
				foreach ( $table['items'] as $item ) {

					if ( rgar( $item, 'export_only' ) ) {
						continue;
					}

					// Open item row.
					echo '<tr>';

					// Display item label.
					echo '<td data-export-label="' . esc_attr( $item['label'] ) . '">' . $item['label'] . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					// Display item value.
					echo '<td>' . self::prepare_item_value( $item ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					// Close item row.
					echo '</tr>';

				}

				// Close section table.
				echo '</tbody></table><br />';

			}
		}

		// Close form.
		echo '</form>';

		// Display page footer.
		GF_System_Status::page_footer();

	}

	/**
	 * Generate copyable system report.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param array $sections System report sections.
	 *
	 * @return string
	 */
	public static function get_system_report_text( $sections ) {

		// Initialize system report text.
		$system_report_text = '';

		// Loop through system report sections.
		foreach ( $sections as $section ) {

			// Loop through tables.
			foreach ( $section['tables'] as $table ) {

				// If table has no items, skip it.
				if ( ! isset( $table['items'] ) || empty( $table['items'] ) ) {
					continue;
				}

				// Add table title to system report.
				$system_report_text .= "\n### " . self::get_export( $table, 'title' ) . " ###\n\n";

				// Loop through section items.
				foreach ( $table['items'] as $item ) {

					// Add section item to system report.
					$system_report_text .= self::get_export( $item, 'label' ) . ': ' . self::prepare_item_value( $item, true ) . "\n";

				}

			}

		}

		$system_report_text = str_replace( array( '()', '../' ), array( '', '[DT]' ), $system_report_text );

		return $system_report_text;

	}

	/**
	 * Get item value for system report.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param array $array Array of items.
	 * @param string $item  Item to get value of.
	 *
	 * @return string
	 */
	public static function get_export( $array, $item ) {

		// Get value.
		$value = isset( $array[ "{$item}_export" ] ) ? $array[ "{$item}_export" ] : $array[ $item ];

		return is_string( $value ) ? trim( $value ) : $value;

	}

	/**
	 * Process System Report page actions.
	 *
	 * @since  2.2
	 * @access private
	 *
	 * @uses GFUpgrade::get_versions()
	 * @uses GFUpgrade::upgrade()
	 */
	private static function maybe_process_action() {
		global $wpdb;

		switch ( rgpost( 'gf_action' ) ) {

			case 'upgrade_database':
				check_admin_referer( 'gf_sytem_report_action', 'gf_sytem_report_action' );

				$versions = gf_upgrade()->get_versions();

				$previous_db_version = $versions['previous_db_version'];

				if ( version_compare( $previous_db_version, '2.3-beta-1', '<' ) && GFCommon::table_exists( $wpdb->prefix . 'rg_form' ) ) {

					$status = get_option( 'gform_upgrade_status' );

					$percent = self::get_upgrade_percent_complete();

					$percent_label = sprintf( esc_html__( 'complete.', 'gravityforms' ), $percent );

					$status = sprintf( '<span id="gf-upgrade-status">%s</span> <span id="gf-upgrade-precent">%s</span>%% %s', $status, $percent, $percent_label );

					$message = sprintf( esc_html__( 'Current status: %s', 'gravityforms' ), $status );

					$message .= ' ' . sprintf( '<img id="gf-spinner" src="%s" />', GFCommon::get_base_url() . '/images/spinner.svg' );

					$ajax_url = admin_url( 'admin-ajax.php' );

					$args = array(
						'action' => 'gf_force_upgrade',
						'nonce'  => wp_create_nonce( 'gf_force_upgrade' ),
					);

					$ajax_url = add_query_arg( $args, $ajax_url	);

					echo '<h2>' . esc_html__( 'Upgrading Gravity Forms', 'gravityforms' ) . '</h2>';

					$warning = esc_html__( 'Do not close or navigate away from this page until the upgrade is 100% complete.', 'gravityforms' );

					printf( '<p>%s</p>', $warning ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					printf( '<p>%s</p>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<script>
						jQuery(document).ready(function ($) {
							var timer = setInterval(function(){ getStatus() }, 30000);

							function getStatus() {
								$.post( "<?php echo esc_url_raw( $ajax_url ); ?>", function( data ) {
									var response = jQuery.parseJSON( data );
									$('#gf-upgrade-status').text( response.status_label );
									$('#gf-upgrade-precent').text( response.percent );
									if ( response.status == 'complete' ) {
										$('#gf-spinner').hide();
										clearInterval( timer );
									}
								});
							}
						});
					</script>
					<?php
					ob_end_flush();
				}

				gf_upgrade()->upgrade( $previous_db_version, true );

				break;

			default:
				break;

		}

	}

	/**
	 * Prepare system report for System Status page.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFSystemReport::get_active_plugins()
	 * @uses GFSystemReport::get_available_logs()
	 * @uses GFSystemReport::get_gravityforms()
	 * @uses GFSystemReport::get_database()
	 * @uses GFSystemReport::get_network_active_plugins()
	 * @uses wpdb::db_version()
	 * @uses wpdb::get_var()
	 *
	 * @return array
	 */
	public static function get_system_report() {

		global $wpdb, $wp_version;

		$wp_cron_disabled  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$alternate_wp_cron = defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON;

		$args = array(
			'timeout'   => 2,
			'body'      => 'test',
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$query_args = array(
			'action' => 'gf_check_background_tasks',
			'nonce'  => wp_create_nonce( 'gf_check_background_tasks' ),
		);

		$url = add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) );

		$response = wp_remote_post( $url, $args );

		// Trims the background tasks response to prevent extraneous characters causing unexpected content in the response.
		$background_tasks = trim( wp_remote_retrieve_body( $response ) ) == 'ok';

		$background_validation_message = '';
		if ( is_wp_error( $response ) ) {
			$background_validation_message = $response->get_error_message();
		} elseif ( ! $background_tasks ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$background_validation_message = esc_html__( 'Unexpected content in the response.', 'gravityforms' );
			} else {
				$background_validation_message = sprintf( esc_html__( 'Response code: %s', 'gravityforms' ), $response_code );
			}
		}
		self::$background_tasks = $background_tasks;

		$db_date  = $wpdb->get_var( 'SELECT utc_timestamp()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$php_date = date( 'Y-m-d H:i:s' );

		// Prepare system report.
		$system_report = array(
			array(
				'title'        => esc_html__( 'Gravity Forms Environment', 'gravityforms' ),
				'title_export' => 'Gravity Forms Environment',
				'tables'       => array(
					array(
						'title'        => esc_html__( 'Gravity Forms', 'gravityforms' ),
						'title_export' => 'Gravity Forms',
						'items'        => self::get_gravityforms(),
					),
					array(
						'title'        => esc_html__( 'Add-Ons', 'gravityforms' ),
						'title_export' => 'Add-Ons',
						'items'        => self::get_active_plugins( false, true, false ),
					),
					array(
						'title'        => esc_html__( 'Database', 'gravityforms' ),
						'title_export' => 'Database',
						'items'        => self::get_database(),
					),
					array(
						'title'        => esc_html__( 'Translations', 'gravityforms' ),
						'title_export' => 'Translations',
						'items'        => self::get_translations(),
					),
					array(
						'title'        => esc_html__( 'Log Files', 'gravityforms' ),
						'title_export' => 'Log Files',
						'items'        => self::get_available_logs(),
					),
					array(
						'title'        => esc_html__( 'Scheduled (Cron) Events Log', 'gravityforms' ),
						'title_export' => 'Scheduled (Cron) Events Log',
						'items'        => self::get_cron_events_log(),
					),
				),
			),
			array(
				'title'        => esc_html__( 'WordPress Environment', 'gravityforms' ),
				'title_export' => 'WordPress Environment',
				'tables'       => array(
					array(
						'title'        => esc_html__( 'WordPress', 'gravityforms' ),
						'title_export' => 'WordPress',
						'items'        => array(
							array(
								'label'        => esc_html__( 'Home URL', 'gravityforms' ),
								'label_export' => 'Home URL',
								'value'        => get_home_url(),
							),
							array(
								'label'        => esc_html__( 'Site URL', 'gravityforms' ),
								'label_export' => 'Site URL',
								'value'        => get_site_url(),
							),
							array(
								'label'        => esc_html__( 'REST API Base URL', 'gravityforms' ),
								'label_export' => 'REST API Base URL',
								'value'        => rest_url(),
							),
							array(
								'label'        => esc_html__( 'WordPress Version', 'gravityforms' ),
								'label_export' => 'WordPress Version',
								'value'        => $wp_version,
								'type'         => 'wordpress_version_check',
								'versions'     => array(
									'support' => array(
										'version_compare'    => '>=',
										'minimum_version'    => GF_MIN_WP_VERSION_SUPPORT_TERMS,
										'validation_message' => sprintf(
											esc_html__( 'The Gravity Forms support agreement requires WordPress %s or greater. This site must be upgraded in order to be eligible for support.', 'gravityforms' ),
											GF_MIN_WP_VERSION_SUPPORT_TERMS
										),
									),
									'minimum' => array(
										'version_compare'    => '>=',
										'minimum_version'    => GF_MIN_WP_VERSION,
										'validation_message' => sprintf(
											esc_html__( 'Gravity Forms requires WordPress %s or greater. You must upgrade WordPress in order to use Gravity Forms.', 'gravityforms' ),
											GF_MIN_WP_VERSION
										),
									),
								),
							),
							array(
								'label'        => esc_html__( 'WordPress Multisite', 'gravityforms' ),
								'label_export' => 'WordPress Multisite',
								'value'        => is_multisite() ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => is_multisite() ?  sprintf( 'Yes (%d sites)', rgar( wp_count_sites(), 'all' ) ) : 'No',
							),
							array(
								'label'        => esc_html__( 'WordPress Memory Limit', 'gravityforms' ),
								'label_export' => 'WordPress Memory Limit',
								'value'        => WP_MEMORY_LIMIT,
							),
							array(
								'label'        => esc_html__( 'WordPress Debug Mode', 'gravityforms' ),
								'label_export' => 'WordPress Debug Mode',
								'value'        => WP_DEBUG ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => WP_DEBUG ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'WordPress Debug Log', 'gravityforms' ),
								'label_export' => 'WordPress Debug Log',
								'value'        => WP_DEBUG_LOG ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => WP_DEBUG_LOG ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'WordPress Script Debug Mode', 'gravityforms' ),
								'label_export' => 'WordPress Script Debug Mode',
								'value'        => SCRIPT_DEBUG ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => SCRIPT_DEBUG ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'WordPress Cron', 'gravityforms' ),
								'label_export' => 'WordPress Cron',
								'value'        => ! $wp_cron_disabled ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => ! $wp_cron_disabled ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'WordPress Alternate Cron', 'gravityforms' ),
								'label_export' => 'WordPress Alternate Cron',
								'value'        => $alternate_wp_cron ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => $alternate_wp_cron ? 'Yes' : 'No',
							),
							array(
								'label'              => esc_html__( 'Background tasks', 'gravityforms' ),
								'label_export'       => 'Background tasks',
								'type'               => 'wordpress_background_tasks',
								'value'              => $background_tasks ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export'       => $background_tasks ? 'Yes' : 'No',
								'is_valid'           => $background_tasks,
								'validation_message' => $background_validation_message,
							),
						),
					),
					array(
						'title'        => esc_html__( 'Active Theme', 'gravityforms' ),
						'title_export' => 'Active Theme',
						'items'        => self::get_theme(),
					),
					array(
						'title'        => esc_html__( 'Active Plugins', 'gravityforms' ),
						'title_export' => 'Active Plugins',
						'items'        => self::get_active_plugins( false, false, true ),
					),
					array(
						'title'        => esc_html__( 'Network Active Plugins', 'gravityforms' ),
						'title_export' => 'Network Active Plugins',
						'items'        => self::get_network_active_plugins(),
					),
				),
			),
			array(
				'title'        => esc_html__( 'Server Environment', 'gravityforms' ),
				'title_export' => 'Server Environment',
				'tables'       => array(
					array(
						'title'        => esc_html__( 'Web Server', 'gravityforms' ),
						'title_export' => 'Web Server',
						'items'        => array(
							array(
								'label'        => esc_html__( 'Software', 'gravityforms' ),
								'label_export' => 'Software',
								'value'        => esc_html( $_SERVER['SERVER_SOFTWARE'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
							),
							array(
								'label'        => esc_html__( 'Port', 'gravityforms' ),
								'label_export' => 'Port',
								'value'        => esc_html( $_SERVER['SERVER_PORT'] ),  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
							),
							array(
								'label'        => esc_html__( 'Document Root', 'gravityforms' ),
								'label_export' => 'Document Root',
								'value'        => esc_html( $_SERVER['DOCUMENT_ROOT'] ),  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
							),
						),
					),
					array(
						'title'        => esc_html__( 'PHP', 'gravityforms' ),
						'title_export' => 'PHP',
						'items'        => array(
							array(
								'label'              => esc_html__( 'Version', 'gravityforms' ),
								'label_export'       => 'Version',
								'value'              => esc_html( phpversion() ),
								'type'               => 'version_check',
								'version_compare'    => '>=',
								'minimum_version'    => GF_MIN_PHP_VERSION,
								'validation_message' => sprintf( esc_html__( 'Recommended: PHP %s or higher.', 'gravityforms' ), GF_MIN_PHP_VERSION ),
							),
							array(
								'label'        => esc_html__( 'Memory Limit', 'gravityforms' ) . ' (memory_limit)',
								'label_export' => 'Memory Limit',
								'value'        => esc_html( ini_get( 'memory_limit' ) ),
							),
							array(
								'label'        => esc_html__( 'Maximum Execution Time', 'gravityforms' ) . ' (max_execution_time)',
								'label_export' => 'Maximum Execution Time',
								'value'        => esc_html( ini_get( 'max_execution_time' ) ),
							),
							array(
								'label'        => esc_html__( 'Maximum File Upload Size', 'gravityforms' ) . ' (upload_max_filesize)',
								'label_export' => 'Maximum File Upload Size',
								'value'        => esc_html( ini_get( 'upload_max_filesize' ) ),
							),
							array(
								'label'        => esc_html__( 'Maximum File Uploads', 'gravityforms' ) . ' (max_file_uploads)',
								'label_export' => 'Maximum File Uploads',
								'value'        => esc_html( ini_get( 'max_file_uploads' ) ),
							),
							array(
								'label'        => esc_html__( 'Maximum Post Size', 'gravityforms' ) . ' (post_max_size)',
								'label_export' => 'Maximum Post Size',
								'value'        => esc_html( ini_get( 'post_max_size' ) ),
							),
							array(
								'label'        => esc_html__( 'Maximum Input Variables', 'gravityforms' ) . ' (max_input_vars)',
								'label_export' => 'Maximum Input Variables',
								'value'        => esc_html( ini_get( 'max_input_vars' ) ),
							),
							array(
								'label'        => esc_html__( 'cURL Enabled', 'gravityforms' ),
								'label_export' => 'cURL Enabled',
								'value'        => function_exists( 'curl_init' ) ? __( 'Yes', 'gravityforms' ) . ' (' . __( 'version', 'gravityforms' ) . ' ' . rgar( curl_version(), 'version' ) . ')' : __( 'No', 'gravityforms' ),
								'value_export' => function_exists( 'curl_init' ) ? 'Yes' . ' (' . __( 'version', 'gravityforms' ) . ' ' . rgar( curl_version(), 'version' ) . ')' : 'No',
							),
							array(
								'label'        => esc_html__( 'OpenSSL', 'gravityforms' ),
								'label_export' => 'OpenSSL',
								'value'        => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT . ' (' . OPENSSL_VERSION_NUMBER . ')' : __( 'No', 'gravityforms' ),
								'value_export' => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT . ' (' . OPENSSL_VERSION_NUMBER . ')' : 'No',
							),
							array(
								'label'        => esc_html__( 'Mcrypt Enabled', 'gravityforms' ),
								'label_export' => 'Mcrypt Enabled',
								'value'        => function_exists( 'mcrypt_encrypt' ) ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => function_exists( 'mcrypt_encrypt' ) ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'Mbstring Enabled', 'gravityforms' ),
								'label_export' => 'Mbstring Enabled',
								'value'        => function_exists( 'mb_strlen' ) ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
								'value_export' => function_exists( 'mb_strlen' ) ? 'Yes' : 'No',
							),
							array(
								'label'        => esc_html__( 'Loaded Extensions', 'gravityforms' ),
								'label_export' => 'Loaded Extensions',
								'type'         => 'csv',
								'value'        => get_loaded_extensions(),
							),
						),
					),
					array(
						'title'        => esc_html__( 'Database Server', 'gravityforms' ),
						'title_export' => 'Database Server',
						'items'        => array(
							array(
								'label'        => esc_html__( 'Database Management System', 'gravityforms' ),
								'label_export' => 'Database Management System',
								'value'        => esc_html( GFCommon::get_dbms_type() ),
							),
							array(
								'label'              => esc_html__( 'Version', 'gravityforms' ),
								'label_export'       => 'Version',
								'value'              => esc_html( GFCommon::get_db_version() ),
								'type'               => 'version_check',
								'version_compare'    => '>',
								'minimum_version'    => ( GFCommon::get_dbms_type() === 'SQLite' ) ? '3.0.0' : '5.0.0',
								// translators: %s is the database type (MySQL, MariaDB or SQLite).
								'validation_message' => sprintf( esc_html__( 'Gravity Forms requires %s or above.', 'gravityforms' ) , ( GFCommon::get_dbms_type() === 'SQLite' ) ? 'SQLite 3.0' : 'MySQL 5' ),
							),
							array(
								'label'        => esc_html__( 'Database Character Set', 'gravityforms' ),
								'label_export' => 'Database Character Set',
								'value'        => esc_html( ( GFCommon::get_dbms_type() === 'SQLite' ) ? $wpdb->charset : $wpdb->get_var( 'SELECT @@character_set_database' ) ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							),
							array(
								'label'        => esc_html__( 'Database Collation', 'gravityforms' ),
								'label_export' => 'Database Collation',
								'value'        => esc_html( ( GFCommon::get_dbms_type() === 'SQLite' ) ? $wpdb->collate : $wpdb->get_var( 'SELECT @@collation_database' ) ),
							),
						),
					),
					array(
						'title'        => esc_html__( 'Date and Time', 'gravityforms' ),
						'title_export' => 'Date and Time',
						'items'        => array(
							array(
								'label'        => esc_html__( 'WordPress (Local) Timezone', 'gravityforms' ),
								'label_export' => 'WordPress (Local) Timezone',
								'value'        => self::get_timezone(),
							),
							array(
								'label'        => esc_html__( 'MySQL - Universal time (UTC)', 'gravityforms' ),
								'label_export' => 'MySQL (UTC)',
								'value'        => $db_date,
							),
							array(
								'label'        => esc_html__( 'MySQL - Local time', 'gravityforms' ),
								'label_export' => 'MySQL (Local)',
								'value'        => GFCommon::format_date( $db_date, false ),
							),
							array(
								'label'        => esc_html__( 'PHP - Universal time (UTC)', 'gravityforms' ),
								'label_export' => 'PHP (UTC)',
								'value'        => $php_date,
							),
							array(
								'label'        => esc_html__( 'PHP - Local time', 'gravityforms' ),
								'label_export' => 'PHP (Local)',
								'value'        => GFCommon::format_date( $php_date, false ),
							),
						),
					),
				),
			),
		);

		/**
		 * Modify sections displayed on the System Status page.
		 *
		 * @since 2.2
		 *
		 * @param array $system_status An array of default sections displayed on the System Status page.
		 */
		$system_report = apply_filters( 'gform_system_report', $system_report );

		return $system_report;

	}

	/**
	 * Prepare item value for System Status table.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param array $item System Status item.
	 *
	 * @uses GFSystemReport::get_export()
	 *
	 * @return string
	 */
	public static function prepare_item_value( $item, $is_export = false ) {

		// Get display as type.
		$type = rgar( $item, 'type' );

		// Prepare value.
		switch ( $type ) {

			case 'csv':
				return implode( ', ', $item['value'] );

			case 'version_check':

				// Is the provided value a valid version?
				if ( ! rgar( $item, 'minimum_version' ) ) {
					return $item['value'];
				} else {
					$valid_version = version_compare( $item['value'], $item['minimum_version'], $item['version_compare'] );
				}

				// Display value based on valid version check.
				if ( $valid_version ) {
					return $is_export ? self::get_export( $item, 'value' ) . ' ✔' : $item['value'] . ' <span class="yes"><span class="dashicons dashicons-yes"></span><span class="screen-reader-text">'. esc_html__( 'Passes', 'gravityforms' ) .'</span></span>';

				} elseif ( $is_export ) {
					$html = self::get_export( $item, 'value' ) . ' ✘ ' . self::get_export( $item, 'validation_message' );

					return $html;

				} else {
					$html = $item['value'] . ' <span class="error"><span class="dashicons dashicons-no"></span><span class="screen-reader-text">'. esc_html__( 'Fails', 'gravityforms' ) .'</span></span>';
					$html .= '<span class="error_message">' . rgar( $item, 'validation_message' ) . '</span>';

					return $html;
				}

			case 'wordpress_version_check':

				// Run version checks.
				$version_check_support = version_compare( $item['value'], $item['versions']['support']['minimum_version'], $item['versions']['support']['version_compare'] );
				$version_check_min     = version_compare( $item['value'], $item['versions']['minimum']['minimum_version'], $item['versions']['minimum']['version_compare'] );

				// If minimum WordPress version for support passed, return valid state.
				if ( $version_check_support ) {
					return $is_export ? self::get_export( $item, 'value' ) . ' ✔' : $item['value'] . ' <span class="yes"><span class="dashicons dashicons-yes"></span><span class="screen-reader-text">'. esc_html__( 'Passes', 'gravityforms' ) .'</span></span>';

				} elseif ( $is_export ) {

					$validation_message = $version_check_min ? self::get_export( $item['versions']['support'], 'validation_message' ) : self::get_export( $item['versions']['minimum'], 'validation_message' );

					return self::get_export( $item, 'value' ) . ' ✘ ' . $validation_message;

				} else {

					$validation_message = $version_check_min ? $item['versions']['support']['validation_message'] : $item['versions']['minimum']['validation_message'];

					$html = $item['value'] . ' <span class="error"><span class="dashicons dashicons-no"></span><span class="screen-reader-text">'. esc_html__( 'Fails', 'gravityforms' ) .'</span></span> ';
					$html .= '<span class="error_message">' . $validation_message . '</span>';

					return $html;
				}

			default:

				$value = $is_export ? self::get_export( $item, 'value' ) : rgar( $item, 'value' );

				if ( rgar( $item, 'is_valid' ) ) {

					$value .= $is_export ? '  ✔' : '&nbsp;<span class="yes"><span class="dashicons dashicons-yes"></span><span class="screen-reader-text">'. esc_html__( 'Passes', 'gravityforms' ) .'</span></span>';

					if ( ! rgempty( 'message', $item ) ) {
						$value .= $is_export ? ' ' . self::get_export( $item, 'message' ) : '&nbsp;' . rgar( $item, 'message' );
					}
				} elseif ( rgar( $item, 'is_valid' ) === false ) {

					$value .= $is_export ? ' ✘' : '&nbsp;<span class="error"><span class="dashicons dashicons-no"></span><span class="screen-reader-text">'. esc_html__( 'Fails', 'gravityforms' ) .'</span></span>';

					if ( ! rgempty( 'validation_message', $item ) ) {
						$value .= $is_export ? ' ' . self::get_export( $item, 'validation_message' ) : '&nbsp;<span class="error_message">' . rgar( $item, 'validation_message' ) . '</span>';
					}
				}

				if ( isset( $item['action'] ) && ! $is_export ) {
					$value .= "&nbsp;<a href='#' onclick='gfDoAction(\"{$item['action']['code']}\", \"" . esc_attr( $item['action']['confirm'] ) . "\");'>{$item['action']['label']}</a>";
				}

				return $value;

		}

	}

	/**
	 * Get Gravity Forms Info.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFCommon::get_version_info()
	 * @uses GFFormsModel::get_upload_root()
	 *
	 * @return array
	 */
	public static function get_gravityforms() {

		// Get Gravity Forms version info, clearing cache
		$version_info = GFCommon::get_version_info( false );

		// Re-caches remote message.
		GFCommon::cache_remote_message();

		// Determine if upload folder is writable.
		$upload_path = GFFormsModel::get_upload_root();
		if ( ! is_dir( $upload_path ) ) {
			wp_mkdir_p( $upload_path );
		}

		$is_writable = wp_is_writable( $upload_path );

		$disable_css      = GFCommon::is_frontend_default_css_disabled();
		$enable_html5     = get_option( 'rg_gforms_enable_html5', false );
		$no_conflict_mode = get_option( 'gform_enable_noconflict' );
		$updates          = get_option( 'gform_enable_background_updates' );

		$default_theme = get_option( 'rg_gforms_default_theme');
		$theme_names   = array(
			'gravity-theme' => 'Gravity Forms 2.5 Theme',
			'orbital'       => 'Orbital Theme',
		);
		$default_theme_name = rgar( $theme_names, $default_theme );

		$web_api       = GFWebAPI::get_instance();
		$is_v2_enabled = $web_api->is_v2_enabled( $web_api->get_plugin_settings() );

		// Prepare versions array.
		$gravityforms = array(
			array(
				'label'              => esc_html__( 'Version', 'gravityforms' ),
				'label_export'       => 'Version',
				'value'              => GFForms::$version,
				'type'               => 'version_check',
				'version_compare'    => '>=',
				'minimum_version'    => $version_info['version'],
				'validation_message' => sprintf(
					esc_html__( 'New version %s available.', 'gravityforms' ),
					esc_html( $version_info['version'] )
				),
			),
			array(
				'label'        => esc_html__( 'Upload folder', 'gravityforms' ),
				'label_export' => 'Upload folder',
				'value'        => GFFormsModel::get_upload_root(),
			),
			array(
				'label'              => esc_html__( 'Upload folder permissions', 'gravityforms' ),
				'label_export'       => 'Upload folder permissions',
				'value'              => $is_writable ? __( 'Writable', 'gravityforms' ) : __( 'Not writable', 'gravityforms' ),
				'value_export'       => $is_writable ? 'Writable' : 'Not writable',
				'is_valid'           => $is_writable,
				'validation_message' => $is_writable ? '' : esc_html__( 'File uploads, entry exports, and logging will not function properly.', 'gravityforms' ),
			),
			array(
				'label'        => esc_html__( 'Output CSS', 'gravityforms' ),
				'label_export' => 'Output CSS',
				'value'        => ! $disable_css ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
				'value_export' => ! $disable_css ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Default Theme', 'gravityforms' ),
				'label_export' => 'Default Theme',
				'value'        => $default_theme_name,
			),
			array(
				'label'        => esc_html__( 'No-Conflict Mode', 'gravityforms' ),
				'label_export' => 'No-Conflict Mode',
				'value'        => $no_conflict_mode ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
				'value_export' => $no_conflict_mode ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Currency', 'gravityforms' ),
				'label_export' => 'Currency',
				'value'        => get_option( 'rg_gforms_currency' ),
			),
			array(
				'label'        => esc_html__( 'Background updates', 'gravityforms' ),
				'label_export' => 'Background updates',
				'value'        => $updates ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
				'value_export' => $updates ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'REST API v2', 'gravityforms' ),
				'label_export' => 'REST API v2',
				'value'        => $is_v2_enabled ? __( 'Yes', 'gravityforms' ) : __( 'No', 'gravityforms' ),
				'value_export' => $is_v2_enabled ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Orbital Style Filter', 'gravityforms' ),
				'value'        => has_filter( 'gform_default_styles' ) ? 'Yes' : 'No',
			),
		);


		return $gravityforms;

	}


	/**
	 * Get Gravity Forms database tables.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFCommon::table_exists()
	 * @uses GFFormsModel::get_tables()
	 * @uses GFSystemReport::has_addons_of()
	 * @uses GFSystemReport::has_payment_callback_addons()
	 * @uses GFUpgrade::get_versions()
	 *
	 * @return array
	 */
	public static function get_database() {

		global $wpdb;

		// Get Gravity Forms version information.
		$versions = gf_upgrade()->get_versions();

		// Initialize available tables.
		$tables = array(
			array(
				'label'        => __( 'Database Version', 'gravityforms' ),
				'label_export' => 'Database Version',
				'value'        => $versions['current_db_version'],
			),
		);

		// Get Gravity Forms tables to check for.
		$gf_tables = GFFormsModel::get_tables();

		// Add feeds table if any Feed Add-Ons are active.
		if ( self::has_addons_of( 'GFFeedAddOn' ) ) {
			$gf_tables[] = $wpdb->prefix . 'gf_addon_feed';
		}

		// Add payment transactions table if any Payment Add-Ons are active.
		if ( self::has_addons_of( 'GFPaymentAddOn' ) ) {
			$gf_tables[] = $wpdb->prefix . 'gf_addon_payment_transaction';
		}

		// Add payment callbacks table if any Payment Add-Ons with callbacks enabled are active.
		if ( self::has_payment_callback_addons() ) {
			$gf_tables[] = $wpdb->prefix . 'gf_addon_payment_callback';
		}

		// Define initial failed tables state.
		$has_failed_tables = false;

		// Loop through Gravity Forms tables.
		foreach ( $gf_tables as $i => $table_name ) {

			if ( $table_name == GFFormsModel::get_rest_api_keys_table_name() && ! self::is_rest_api_enabled() ) {
				// The REST API key table is only created when the REST API is enabled.
				continue;
			}

			// Set initial validity and validation message states.
			$value                     = true;
			$validation_message        = '';
			$validation_message_export = '';

			// If table does not exist, set validation message.
			if ( ! GFCommon::table_exists( $table_name ) ) {
				$has_failed_tables         = true;
				$value                     = false;
				$validation_message        = __( 'Table does not exist', 'gravityforms' );
				$validation_message_export = 'Table does not exist';
				// If table does not have auto-increment set on id field, set validation message.
			} elseif ( ! gf_upgrade()->is_auto_increment_enabled( $table_name ) ) {
				$has_failed_tables         = true;
				$value                     = false;
				$validation_message        = __( 'Table has incorrect auto-increment settings.', 'gravityforms' );
				$validation_message_export = 'Table has incorrect auto-increment settings.';
				// If table schema is incorrect, set validation message.
			} elseif ( ! gf_upgrade()->check_table_schema( $table_name ) ) {
				$has_failed_tables         = true;
				$value                     = false;
				$validation_message        = __( 'Table has not been upgraded successfully.', 'gravityforms' );
				$validation_message_export = 'Table has not been upgraded successfully.';
			}

			// Add table to return array.
			$tables[] = array(
				'label'                     => $table_name,
				'value'                     => '',
				'is_valid'                  => $value,
				'validation_message'        => $validation_message,
				'validation_message_export' => $validation_message_export,
			);

		}

		// Define database upgrade warning message.
		$warning_message = __( "WARNING! Re-running the upgrade process is only recommended if you are currently experiencing issues with your database. This process may take several minutes to complete. 'OK' to upgrade. 'Cancel' to abort.", 'gravityforms' );

		// If database version is out of date, add upgrade database option.
		if ( version_compare( $versions['current_db_version'], GFForms::$version, '<' ) ) {

			if ( gf_upgrade()->is_upgrading() && version_compare( $versions['previous_db_version'], '2.3-beta-1', '<' ) && GFCommon::table_exists( $wpdb->prefix . 'rg_form' ) ) {
				$status = get_option( 'gform_upgrade_status' );
				$status = empty( $status ) ? '' : sprintf( __( 'Current Status: %s', 'gravityforms' ), $status );
				$percent = self::get_upgrade_percent_complete();
				$percent_label = sprintf( esc_html__( '%s%% complete.', 'gravityforms' ), $percent );
				$status .= ' ' . $percent_label;
				if ( defined( 'GFORM_AUTO_DB_MIGRATION_DISABLED' ) && GFORM_AUTO_DB_MIGRATION_DISABLED ) {
					$message = sprintf( __( 'Automatic background migration is disabled but the database needs to be upgraded to version %s. %s', 'gravityforms' ), GFForms::$version, $status );
					$action_label = __( 'Force the migration manually', 'gravityforms' );
				} else {
					$message = sprintf( __( 'The database is currently being upgraded to version %s. %s', 'gravityforms' ), GFForms::$version, $status );
					if ( ! self::$background_tasks ) {
						$message .= ' ' . __( "As this site doesn't support background tasks the upgrade process will take longer than usual and the status will change infrequently.", 'gravityforms' );
					}
					$action_label = __( 'Force the upgrade', 'gravityforms' );
				}

				$tables[0] = array_merge(
					$tables[0],
					array(
						'label'   => __( 'Database Version', 'gravityforms' ),
						'action'         => array(
							'label'   => $action_label,
							'code'    => 'upgrade_database',
							'confirm' => $warning_message,
						),
						'is_valid'       => false,
						'validation_message' => $message,
						'validation_message_export' => $message,
					)
				);
			} else {
				$tables[0] = array_merge(
					$tables[0],
					array(
						'action'         => array(
							'label'   => __( 'Upgrade database', 'gravityforms' ),
							'code'    => 'upgrade_database',
							'confirm' => $warning_message,
						),
						'is_valid'       => false,
						'message'        => __( 'Your database version is out of date.', 'gravityforms' ),
						'message_export' => 'Your database version is out of date.',
					)
				);
			}

		} elseif ( $has_failed_tables ) {

			$tables[0] = array_merge(
				$tables[0],
				array(
					'action'         => array(
						'label'   => __( 'Re-run database upgrade', 'gravityforms' ),
						'code'    => 'upgrade_database',
						'confirm' => $warning_message,
					),
					'is_valid'       => false,
					'message'        => 'upgrade_database' == rgpost( 'gf_action' ) ? __( 'Database upgrade failed.', 'gravityforms' ) : __( 'There are issues with your database.', 'gravityforms' ),
					'message_export' => 'upgrade_database' == rgpost( 'gf_action' ) ? 'Database upgrade failed.' : 'There are issues with your database.',
				)
			);

		} else {

			$tables[0] = array_merge(
				$tables[0],
				array(
					'action'         => array(
						'label'   => __( 'Re-run database upgrade', 'gravityforms' ),
						'code'    => 'upgrade_database',
						'confirm' => $warning_message,
					),
					'is_valid'       => true,
					'message'        => 'upgrade_database' == rgpost( 'gf_action' ) ? __( 'Database upgraded successfully.', 'gravityforms' ) : __( 'Your database is up-to-date.', 'gravityforms' ) . ' ' . __( 'Warning: downgrading Gravity Forms is not recommended.', 'gravityforms' ),
					'message_export' => 'upgrade_database' == rgpost( 'gf_action' ) ? 'Database upgraded successfully.' : 'Your database is up-to-date.',
				)
			);

		}

		return $tables;

	}

	/**
	 * Get available Gravity Forms log files.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFLogging::get_log_file_size()
	 * @uses GFLogging::get_log_file_url()
	 * @uses GFLogging::get_supported_plugins()
	 * @uses GFLogging::log_file_exists()
	 *
	 * @return string
	 */
	public static function get_available_logs() {

		// If Logging is not available, return.
		if ( ! function_exists( 'gf_logging' ) ) {
			return;
		}

		// Initialize logs array.
		$logs = array();

		// Get plugins that support logging.
		$supported_plugins = gf_logging()->get_supported_plugins();
		$logs_dir_path     = gf_logging()->get_log_dir();
		$logs_dir_url      = gf_logging()->get_log_dir_url();

		// Loop through supported plugins.
		foreach ( $supported_plugins as $plugin_slug => $plugin_name ) {

			$files = GFCommon::glob( $plugin_slug . '_*.txt', $logs_dir_path );

			if ( empty( $files ) ) {
				continue;
			}

            // Create an array to hold file info including the modification time.
            $file_info = array();

            foreach ( $files as $file ) {
                $mod_time    = filemtime( $file );
                $file_info[] = array(
                    'file'     => $file,
                    'mod_time' => $mod_time
                );
            }

            // Sort the files by modification time.
            usort( $file_info, function( $a, $b ) {
                return $b['mod_time'] - $a['mod_time'];
            } );

            // Add sorted files to the logs array.
            foreach ( $file_info as $info ) {
                $file = $info['file'];
                $url  = str_replace( $logs_dir_path, $logs_dir_url, $file );

                $logs[] = array(
                    'label'        => '<a href="' . $url . '" target="_blank">' . esc_html( $plugin_name ) . '<span class="screen-reader-text">' . esc_html__('(opens in a new tab)', 'gravityforms') . '</span>&nbsp;<span class="gform-icon gform-icon--external-link"></span></a>',
                    'label_export' => esc_html( $plugin_name ),
                    'value'        => gf_logging()->get_log_file_size( $file, true ) . ' (' . GFCommon::format_date( date( 'c', filemtime( $file ) ) ) . ')',
                    'value_export' => $url,
                );
            }
		}

		return $logs;

	}

	/**
	 * Get active plugins for system report.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param bool $include_gravity_forms  Include Gravity Forms in plugin list.
	 * @param bool $include_gf_addons      Include Add-On Framework plugins in plugin list.
	 * @param bool $included_non_gf_addons Include non Add-On Framework plugins in plugin list.
	 *
	 * @uses GFAddOn::meets_minimum_requirements()
	 * @uses GFCommon::get_version_info()
	 * @uses GFSystemReport::get_gf_addon()
	 *
	 * @return string
	 */
	public static function get_active_plugins( $include_gravity_forms = true, $include_gf_addons = true, $include_non_gf_addons = true ) {

		// Initialize active plugins array.
		$active_plugins = array();

		// Get Gravity Forms version info.
		$version_info = GFCommon::get_version_info();

		// Prepare active plugins.
		foreach ( get_plugins() as $plugin_path => $plugin ) {

			// If plugin is not active, skip it.
			if ( ! is_plugin_active( $plugin_path ) ) {
				continue;
			}

			// If this plugin is Gravity Forms and it is not to be included, skip it.
			if ( 'gravityforms/gravityforms.php' === $plugin_path && ! $include_gravity_forms ) {
				continue;
			}

			// Check if plugin is a Gravity Forms Add-On.
			$addon    = self::get_gf_addon( $plugin_path );
			$is_addon = $addon !== false;

			// If this plugin is an Add-On and Add-Ons are not to be included, skip it.
			if ( $is_addon && ! $include_gf_addons ) {
				continue;
			}

			// If this plugin is not an Add-On and non Add-Ons are not to be included, skip it.
			if ( ! $is_addon && ! $include_non_gf_addons ) {
				continue;
			}

			// Define default validity and error message.
			$is_valid                  = true;
			$validation_message        = '';
			$validation_message_export = '';

			// If plugin is an Add-On, check for available updates.
			if ( $is_addon ) {

				// Get plugin slug.
				$slug = $addon->get_slug();

				$minimum_requirements = $addon->meets_minimum_requirements();

				// If the Add-On is an official Add-On and an update exists, add "error" message.
				if ( isset( $version_info['offerings'][ $slug ] ) && version_compare( $plugin['Version'], $version_info['offerings'][ $slug ]['version'], '<' ) ) {

					$is_valid           = false;
					$validation_message = sprintf( __( 'New version %s available.', 'gravityforms' ), $version_info['offerings'][ $slug ]['version'] );

				} elseif ( ! $minimum_requirements['meets_requirements'] ) {

					$errors                    = $minimum_requirements['errors'];
					$is_valid                  = false;
					$validation_message        = sprintf( __( 'Your system does not meet the minimum requirements for this Add-On (%d errors).', 'gravityforms' ), count( $errors ) );
					$validation_message_export = sprintf( 'Your system does not meet the minimum requirements for this Add-On (%1$d errors). %2$s', count( $errors ), implode( '. ', $errors ) );

				}
			}

			// Cleaning up Add-On name
			$plugin_name = $is_addon ? str_replace( ' Add-On', '', str_replace( 'Gravity Forms ', '', $plugin['Name'] ) ) : $plugin['Name'];

			// Prepare plugin label.
			if ( rgar( $plugin, 'PluginURI' ) ) {
				$label = '<a href="' . esc_url( $plugin['PluginURI'] ) . '">' . esc_html( $plugin_name ) . '</a>';
			} else {
				$label = esc_html( $plugin_name );
			}

			// Prepare plugin value.
			if ( rgar( $plugin, 'AuthorURI' ) ) {
				$value = sprintf(
					'%s <a href="%s">%s</a> - %s',
					__( 'by', 'gravityforms' ),
					esc_url( $plugin['AuthorURI'] ),
					esc_html( $plugin['Author'] ),
					$plugin['Version']
				);
			} else {
				$value = sprintf( '%s %s - %s',
					__( 'by', 'gravityforms' ),
					$plugin['Author'],
					$plugin['Version']
			    );
			}

			// Add plugin to active plugins.
			$active_plugins[] = array(
				'label'                     => $label,
				'label_export'              => strip_tags( $plugin_name ),
				'value'                     => $value,
				'value_export'              => sprintf(
				'%s %s - %s',
					__( 'by', 'gravityforms' ),
					strip_tags( $plugin['Author'] ),
					$plugin['Version']
				),
				'is_valid'                  => $is_valid,
				'validation_message'        => $validation_message,
				'validation_message_export' => $validation_message_export,
			);

		}

		return $active_plugins;

	}

	/**
	 * Get network active plugins for system report.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses wpdb::get_var()
	 * @uses wpdb::prepare()
	 *
	 * @return string
	 */
	public static function get_network_active_plugins() {

		global $wpdb;

		// If multi-site is not active, return.
		if ( ! is_multisite() ) {
			return;
		}

		// Get network active plugins.
		$network_active_plugins = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key=%s", 'active_sitewide_plugins' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		// If no network active plugins were found, return.
		if ( empty( $network_active_plugins ) ) {
			return;
		}

		// Convert network active plugins to array.
		$network_active_plugins = maybe_unserialize( $network_active_plugins );

		// Loop through network active plugins.
		foreach ( $network_active_plugins as $plugin_path => &$plugin ) {

			// Get plugin data.
			$plugin_data = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $plugin_path );

			// Prepare plugin label.
			if ( rgar( $plugin_data, 'PluginURI' ) ) {
				$label = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '">' . esc_html( $plugin_data['Name'] ) . '</a>';
			} else {
				$label = esc_html( $plugin_data['Name'] );
			}

			// Prepare plugin value.
			if ( rgar( $plugin_data, 'AuthorURI' ) ) {
				$value = sprintf(
					'%s <a href="%s">%s</a> - %s',
					__( 'by', 'gravityforms' ),
					esc_url( $plugin_data['AuthorURI'] ),
					$plugin_data['Author'],
					$plugin_data['Version']
				);
			} else {
				$value = sprintf(
					'%s %s - %s',
					__( 'by', 'gravityforms' ),
					$plugin_data['Author'],
					$plugin_data['Version']
				);
			}

			// Replace plugin.
			$plugin = array(
				'label'        => $label,
				'label_export' => strip_tags( $label ),
				'value'        => $value,
				'value_export' => strip_tags( $value ),
			);

		}

		// Convert active plugins to string.
		return $network_active_plugins;

	}

	/**
	 * Returns a GFAddon child class if the plugin slug specified is a Gravity Forms Add-On.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param string $path Plugin path. (e.g. gravityformsmailchimp/mailchimp.php)
	 *
	 * @uses GFAddOn::get_instance()
	 * @uses GFAddOn::get_registered_addons()
	 *
	 * @return object|bool Returns a subclass of GFAddon if the specified plugin is a Gravity Forms Add-On. Returns false otherwise
	 */
	public static function get_gf_addon( $path ) {

		// Get active Add-Ons.
		$gf_addons = GFAddOn::get_registered_addons();

		// Loop through active Add-Ons.
		foreach ( $gf_addons as $gf_addon ) {

			// If Add-On instance cannot be retrieved, skip it.
			if ( ! is_callable( array( $gf_addon, 'get_instance' ) ) ) {
				continue;
			}

			// Get Add-On instance.
			$addon = call_user_func( array( $gf_addon, 'get_instance' ) );

			if ( ! is_subclass_of( $addon, 'GFAddOn' ) ) {
				continue;
			}

			// If Add-On path matches provided path, return.
			if ( $path == $addon->get_path() ) {
				return $addon;
			}

		}

		return false;

	}

	/**
	 * Determine if there are any active Add-Ons that extend a specific class.
	 *
	 * @since  2.2
 	 * @since  2.6 access changed to public
	 * @access public
	 *
	 * @param string $class_name Class name to check if Add-Ons are a subclass of.
	 *
	 * @uses GFAddOn::get_instance()
	 * @uses GFAddOn::get_registered_addons()
	 *
	 * @return bool
	 */
	public static function has_addons_of( $class_name ) {

		// Get active Add-Ons.
		$gf_addons = GFAddOn::get_registered_addons();

		// Loop through active Add-Ons.
		foreach ( $gf_addons as $gf_addon ) {

			// If Add-On instance cannot be retrieved, skip it.
			if ( ! is_callable( array( $gf_addon, 'get_instance' ) ) ) {
				continue;
			}

			// Get Add-On instance.
			$addon = call_user_func( array( $gf_addon, 'get_instance' ) );

			// If Add-On is a subclass of the class name we are checking for, return.
			if ( is_subclass_of( $addon, $class_name ) ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Determine if there are any active Add-Ons with a payment callback.
	 *
	 * @since  2.2
 	 * @since  2.6 access changed to public
	 * @access public
	 *
	 * @uses GFAddOn::get_instance()
	 * @uses GFAddOn::get_registered_addons()
	 * @uses GFPaymentAddOn::get_supports_callback()
	 *
	 * @return bool
	 */
	public static function has_payment_callback_addons() {

		// Get active Add-Ons.
		$gf_addons = GFAddOn::get_registered_addons();

		// Loop through active Add-Ons.
		foreach ( $gf_addons as $gf_addon ) {

			// If Add-On instance cannot be retrieved, skip it.
			if ( ! is_callable( array( $gf_addon, 'get_instance' ) ) ) {
				continue;
			}

			// Get Add-On instance.
			$addon = call_user_func( array( $gf_addon, 'get_instance' ) );

			// If Add-On is not a Payment Add-On, skip it.
			if ( ! is_subclass_of( $addon, 'GFPaymentAddOn' ) ) {
				continue;
			}

			// If Add-On supports payment callback, return.
			if ( $addon->get_supports_callback() ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Get the theme info.
	 *
	 * @since  2.2.5.9
	 * @access public
	 *
	 * @return array
	 */
	public static function get_theme() {

		wp_update_themes();
		$update_themes          = get_site_transient( 'update_themes' );
		$update_themes_versions = ! empty( $update_themes->checked ) ? $update_themes->checked : array();

		$active_theme     = wp_get_theme();
		$theme_name       = wp_strip_all_tags( $active_theme->get( 'Name' ) );
		$theme_version    = wp_strip_all_tags( $active_theme->get( 'Version' ) );
		$theme_author     = wp_strip_all_tags( $active_theme->get( 'Author' ) );
		$theme_author_uri = esc_url( $active_theme->get( 'AuthorURI' ) );

		$theme_details = array(
			array(
				'label'        => $theme_name,
				'value'        => sprintf( '%s <a href="%s">%s</a> - %s', __( 'by', 'gravityforms' ), $theme_author_uri, $theme_author, $theme_version ),
				'value_export' => sprintf( '%s %s (%s) - %s', __( 'by', 'gravityforms' ), $theme_author, $theme_author_uri, $theme_version ),
				'is_valid'     => version_compare( $theme_version, rgar( $update_themes_versions, $active_theme->get_stylesheet() ), '>=' )
			),
		);

		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->get( 'Template' ) );
			$parent_name       = wp_strip_all_tags( $parent_theme->get( 'Name' ) );
			$parent_version    = wp_strip_all_tags( $parent_theme->get( 'Version' ) );
			$parent_author     = wp_strip_all_tags( $parent_theme->get( 'Author' ) );
			$parent_author_uri = esc_url( $parent_theme->get( 'AuthorURI' ) );

			$theme_details[] = array(
				'label'        => sprintf( '%s (%s)', $parent_name, esc_html__( 'Parent', 'gravityforms' ) ),
				'label_export' => $parent_name . ' (Parent)',
				'value'        => sprintf( '%s <a href="%s">%s</a> - %s', __( 'by', 'gravityforms' ), $parent_author_uri, $parent_author, $parent_version ),
				'value_export' => sprintf( '%s %s (%s) - %s', __( 'by', 'gravityforms' ), $parent_author, $parent_author_uri, $parent_version ),
				'is_valid'     => version_compare( $parent_version, rgar( $update_themes_versions, $parent_theme->get_stylesheet() ), '>=' )
			);
		}

		return $theme_details;

	}

	/**
	* Returns the percent complete of the migration from the legacy rg_ tables to the gf_ tables.
	*
	* @since 2.3.0.4
	*
	* @return float
	*/
	public static function get_upgrade_percent_complete() {
		global $wpdb;

		$form_table = $wpdb->prefix . 'gf_form';
		$form_meta_table = $wpdb->prefix . 'gf_form_meta';
		$form_view = $wpdb->prefix . 'gf_form_view';
		$entry_table = GFFormsModel::get_entry_table_name();
		$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
		$entry_notes_table = GFFormsModel::get_entry_notes_table_name();

		$legacy_form_table = $wpdb->prefix . 'rg_form';
		$legacy_form_meta_table = $wpdb->prefix . 'rg_form_meta';
		$legacy_form_view_table = $wpdb->prefix . 'rg_form_view';
		$lead_table = GFFormsModel::get_lead_table_name();
		$lead_detail_table = GFFormsModel::get_lead_details_table_name();
		$lead_meta_table = GFFormsModel::get_lead_meta_table_name();
		$lead_notes_table = GFFormsModel::get_lead_notes_table_name();

		$query = "
			select
			(select count(1) from {$form_table}) as form_count,
			(select count(1) from {$form_meta_table}) as form_meta_count,
			(select count(1) from {$form_view}) as form_view_count,
			(select count(1) from {$entry_table}) as entry_count,
			(select count(1) from {$entry_meta_table}) as entry_meta_count,
			(select count(1) from {$entry_notes_table}) as entry_notes_count,

			(select count(1) from {$legacy_form_table}) as legacy_form_count,
			(select count(1) from {$legacy_form_meta_table}) as legacy_form_meta_count,
			(select count(1) from {$legacy_form_view_table}) as legacy_form_view_count,
			(select count(1) from {$lead_table}) as lead_count,
			(select count(1) from {$lead_detail_table}) as lead_detail_count,
			(select count(1) from {$lead_meta_table}) as lead_meta_count,
			(select count(1) from {$lead_notes_table}) as lead_notes_count";

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $wpdb->last_error || ! isset( $results[0] ) ) {
			return 0;
		}

		$c = $results[0];

		if ( ! isset( $c->form_count ) ) {
			return 0;
		}

		$count = $c->form_count + $c->form_meta_count + $c->form_view_count + $c->entry_count + $c->entry_meta_count + $c->entry_notes_count;

		$legacy_count = $c->legacy_form_count + $c->legacy_form_meta_count + $c->legacy_form_view_count + $c->lead_count + $c->lead_detail_count + $c->lead_meta_count + $c->lead_notes_count;

		if ( 0 == $legacy_count ) {
			return 100;
		}

		$percent_complete = round( $count / $legacy_count * 100, 2 );

		if ( $percent_complete > 100 ) {
			$percent_complete = 100;
		}

		return $percent_complete;
	}


	/**
	 * Checks whether the REST API is enabled.
 	 *
 	 * @since 2.4.0.1
	 *
	 * @return bool
	 */
	public static function is_rest_api_enabled() {
		$rest_api_settings = get_option( 'gravityformsaddon_gravityformswebapi_settings' );
		return ! empty( $rest_api_settings ) && $rest_api_settings['enabled'];
	}

	/**
	* Gets the WordPress timezone string.
	*
	* Based on WP 5.2 options-general.php.
	*
	* @since 2.4.11
	*
	* @return string
	*/
	public static function get_timezone() {
		$tzstring = get_option( 'timezone_string' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
			$current_offset = get_option( 'gmt_offset' );
			if ( 0 == $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		return $tzstring;
	}

	/**
	 * Get translations info.
	 *
	 * @since  2.5.6
	 *
	 * @return array
	 */
	public static function get_translations() {
		$items = array(
			array(
				'label'        => esc_html__( 'Site Locale', 'gravityforms' ),
				'label_export' => 'Site Locale',
				'value'        => get_locale(),
			),
		);

		if ( function_exists( 'get_user_locale' ) ) {
			$items[] = array(
				// translators: %d: The ID of the currently logged in user.
				'label'        => sprintf( esc_html__( 'User (ID: %d) Locale', 'gravityforms' ), get_current_user_id() ),
				'label_export' => sprintf( 'User (ID: %d) Locale', get_current_user_id() ),
				'value'        => get_user_locale(),
			);
		}

		$items[] = array(
			'label' => 'Gravity Forms',
			'value' => implode( ', ', GFCommon::get_installed_translations() ),
		);

		if ( ! class_exists( 'GFAddOn' ) ) {
			return $items;
		}

		$addons = GFAddOn::get_registered_addons( true );

		foreach ( $addons as $addon ) {
			$locales = $addon->get_installed_locales();

			if ( empty( $locales ) ) {
				continue;
			}

			$items[] = array(
				'label' => $addon->get_short_title(),
				'value' => implode( ', ', $locales ),
			);
		}

		return $items;
	}

	/**
	 * Gets the items for the cron events log section.
	 *
	 * @since 2.7.1
	 *
	 * @return array
	 */
	public static function get_cron_events_log() {
		$events = GFCache::get( GFCache::KEY_CRON_EVENTS );

		if ( empty( $events ) ) {
			return array();
		}

		$items = array();

		foreach ( $events as $hook => $timestamps ) {
			foreach ( $timestamps as $timestamp ) {
				$full_dt = date( 'c', $timestamp );

				$items[] = array(
					'label'        => $hook,
					'value'        => GFCommon::format_date( $full_dt ),
					'value_export' => GFCommon::format_date( $full_dt, false, 'Y-m-d H:i:s', false ),
					'timestamp'    => $timestamp,
				);
			}
		}

		return wp_list_sort( $items, 'timestamp', 'DESC' );
	}

}
