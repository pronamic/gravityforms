<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GFAutoUpgrade {
	protected $_version;
	protected $_min_gravityforms_version;
	protected $_slug;
	protected $_title;
	protected $_full_path;
	protected $_path;
	protected $_url;
	protected $_is_gravityforms_supported;
	protected $_inactive;

	/**
	 * Constructor.
	 *
	 * @since Unknown
	 * @since 3.2.3 Added $inactive param.
	 *
	 * @param string $slug                      The add-on slug.
	 * @param string $version                   The add-on version.
	 * @param string $min_gravityforms_version  The minimum Gravity Forms version required by the add-on.
	 * @param string $title                     The add-on title.
	 * @param string $full_path                 The full path to the add-on file.
	 * @param string $path                      The relative path to the add-on file from the plugins folder.
	 * @param string $url                       The Gravity Forms site URL.
	 * @param bool   $is_gravityforms_supported Whether the minimum Gravity Forms version is met.
	 * @param bool   $inactive                  Whether the add-on is inactive.
	 */
	public function __construct( $slug, $version, $min_gravityforms_version, $title, $full_path, $path, $url, $is_gravityforms_supported, $inactive = false ) {
		$this->_slug                      = $slug;
		$this->_version                   = $version;
		$this->_min_gravityforms_version  = $min_gravityforms_version;
		$this->_title                     = $title;
		$this->_full_path                 = $full_path;
		$this->_path                      = $path;
		$this->_url                       = $url;
		$this->_is_gravityforms_supported = $is_gravityforms_supported;
		$this->_inactive                  = $inactive;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		if ( is_admin() ) {
			GFCommon::load_gf_text_domain();
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'display_changelog' ), 9 );
			add_filter( 'gform_updates_list', array( $this, 'get_update_info' ) );

			if ( RG_CURRENT_PAGE == 'plugins.php' ) {
				add_action( 'after_plugin_row_' . $this->_path, array( $this, 'rg_plugin_row' ), 10, 2 );
			} elseif ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {
				add_action( 'wp_ajax_gf_get_changelog', array( $this, 'ajax_display_changelog' ) );
			}
		}

		// Check for updates. The check might not run the admin context. E.g. from WP-CLI.
		add_filter( 'transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'auto_update_plugin', function ( $update, $item ) {
			return GFForms::maybe_auto_update( $update, $item, $this->_slug, $this->_version );
		}, 10, 2 );

		// ManageWP premium update filters
		add_filter( 'mwp_premium_update_notification', array( $this, 'premium_update_push' ) );
		add_filter( 'mwp_premium_perform_update', array( $this, 'premium_update' ) );
	}

	/**
	 * Displays messages for the add-on listing on the Plugins page.
	 *
	 * Displays if Gravity Forms isn't supported.
	 *
	 * @since Unknown
	 * @since 2.4.15 Update to improve multisite updates.
	 * @since 3.2.3 Moved API minimum requirements check from GFAddOn::plugin_row().
	 *
	 * @param string $plugin_name The plugin filename.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public function rg_plugin_row( $plugin_name, $plugin_data ) {
		GFForms::maybe_display_update_notification( $plugin_name, $plugin_data, $this->_slug, $this->_version );

		if ( ! rgar( $plugin_data, 'is_minimum_requirements_blocked' ) ) {
			// Fallback to legacy check for add-ons that haven't had a release since the minimum requirements feature was implemented.
			if ( ! $this->_is_gravityforms_supported ) {
				// translators: %1$s: The add-on title. %2$s: The minimum Gravity Forms version required. %3$s: The installed Gravity Forms version.
				$message = sprintf( esc_html__( 'The %1$s requires Gravity Forms version %2$s or higher for full compatibility. You are currently using version %3$s. Please update Gravity Forms.', 'gravityforms' ), $this->_title, $this->_min_gravityforms_version, GFForms::$version );
				$this->display_plugin_message( $message, 'error' );
			}

			return;
		}

		$message = rgar( $plugin_data, 'minimum_requirements_message' );

		if ( version_compare( $this->_version, rgar( $plugin_data, 'new_version' ), '<' ) ) {
			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . urlencode( $this->_slug ) . '&section=changelog&TB_iframe=true&width=600&height=800' );
			// translators: %s: new version.
			$message_link_text = sprintf( esc_html__( 'View version %s details', 'gravityforms' ), rgar( $plugin_data, 'new_version' ) );
			$message_link      = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', esc_url( $details_url ), esc_attr( $this->_title ), $message_link_text );
			// translators: %1$s: Add-on title. %2$s: View details message and link.
			$message_new_version = sprintf( esc_html__( 'There is a new version of %1$s available. %2$s.', 'gravityforms' ), $this->_title, $message_link );
			$message             = $message_new_version . ' ' . $message;
		}

		$this->display_plugin_message( $message, 'warning' );
	}

	/**
	 * Formats and outputs a message for the plugin row.
	 *
	 * @since 3.2.3
	 *
	 * @param string $message The message to display.
	 * @param string $style   The style to apply to the message. Should be 'error' or 'warning'.
	 *
	 * @return void
	 */
	private function display_plugin_message( $message, $style ) {
		if ( empty( $message ) ) {
			return;
		}

		if ( ! class_exists( 'GFAddOn' ) ) {
			GFForms::include_addon_framework();
		}

		GFAddOn::display_plugin_message( $message, $style, $this->_inactive );
	}

	//Integration with ManageWP
	public function premium_update_push( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = $this->get_version_info( $this->_slug );
		if ( ! rgar( $update, 'is_minimum_requirements_blocked' ) && rgar( $update, 'is_valid_key' ) && version_compare( $this->_version, $update['version'], '<' ) ) {
			$plugin_data                = get_plugin_data( $this->_full_path );
			$plugin_data['type']        = 'plugin';
			$plugin_data['slug']        = $this->_path;
			$plugin_data['new_version'] = isset( $update['version'] ) ? $update['version'] : false;
			$premium_update[]           = $plugin_data;
		}

		return $premium_update;
	}

	//Integration with ManageWP
	public function premium_update( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = $this->get_version_info( $this->_slug );
		if ( ! rgar( $update, 'is_minimum_requirements_blocked' ) && rgar( $update, 'is_valid_key' ) && version_compare( $this->_version, $update['version'], '<' ) ) {
			$plugin_data         = get_plugin_data( $this->_full_path );
			$plugin_data['slug'] = $this->_path;
			$plugin_data['type'] = 'plugin';
			$plugin_data['url']  = isset( $update['url'] ) ? $update['url'] : false; // OR provide your own callback function for managing the update

			array_push( $premium_update, $plugin_data );
		}

		return $premium_update;
	}

	public function flush_version_info() {
		$this->set_version_info( $this->_slug, false );
	}

	private function set_version_info( $plugin_slug, $version_info ) {
		if ( function_exists( 'set_site_transient' ) ) {
			set_site_transient( $plugin_slug . '_version', $version_info, 60 * 60 * 12 );
		} else {
			set_transient( $plugin_slug . '_version', $version_info, 60 * 60 * 12 );
		}
	}

	/**
	 * Adds the add-on to the list of (un)available updates.
	 *
	 * @since Unknown
	 * @since 2.9.23 Updated the add-on data to include `minimum_requirements`.
	 * @since 3.2.3 Updated the add-on data to include `is_minimum_requirements_blocked` and `minimum_requirements_message`.
	 *
	 * @param object $option The update option.
	 *
	 * @return object
	 */
	public function check_update( $option ) {

		if ( ! is_object( $option ) ) {
			return $option;
		}

		$version_info = $this->get_version_info( $this->_slug );

		if ( rgar( $version_info, 'is_error' ) ) {
			return $option;
		}

		$new_version = rgar( $version_info, 'version', '0' );

		$plugin = array(
			'plugin'                          => $this->_path,
			'url'                             => $this->_url,
			'slug'                            => $this->_slug,
			'package'                         => $version_info['url'] ? str_replace( '{KEY}', $this->get_key(), $version_info['url'] ) : '',
			'new_version'                     => $new_version,
			'version_latest'                  => $version_info['version_latest'],
			'id'                              => '0',
			'minimum_requirements'            => $version_info['minimum_requirements'],
			'is_minimum_requirements_blocked' => $version_info['is_minimum_requirements_blocked'],
			'minimum_requirements_message'    => $version_info['minimum_requirements_message'],
		);

		if ( $version_info['is_minimum_requirements_blocked'] || ! rgar( $version_info, 'is_valid_key' ) || version_compare( $this->_version, $new_version, '>=' ) ) {
			unset( $option->response[ $this->_path ] );
			$option->no_update[ $this->_path ] = (object) $plugin;
		} else {
			$option->response[ $this->_path ] = (object) $plugin;
		}

		return $option;
	}

	// Displays current version details on plugins page and updates page
	public function display_changelog() {
		if ( isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] != $this->_slug ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$change_log = $this->get_changelog();
		echo $change_log; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Get changelog with admin-ajax.php in GFForms::maybe_display_update_notification().
	 *
	 * @since 2.4.15
	 */
	public function ajax_display_changelog() {
		check_admin_referer( 'gf_get_changelog' );

		$this->display_changelog();
	}

	private function get_changelog() {
		$key                = $this->get_key();
		$body               = "key={$key}";
		$options            = array( 'method' => 'POST', 'timeout' => 3, 'body' => $body );
		$options['headers'] = array(
			'Content-Type'   => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'Content-Length' => strlen( $body ),
			'User-Agent'     => 'WordPress/' . get_bloginfo( 'version' ),
		);

		$raw_response = GFCommon::post_to_manager( 'changelog.php', $this->get_remote_request_params( $this->_slug, $key, $this->_version ), $options );

		if ( is_wp_error( $raw_response ) || 200 != $raw_response['response']['code'] ) {
			$text = sprintf( esc_html__( 'Oops!! Something went wrong.%sPlease try again or %scontact us%s.', 'gravityforms' ), '<br/>', "<a href='" . esc_attr( GFCommon::get_support_url() ). "'>", '</a>' );
		} else {
			$text = $raw_response['body'];
			if ( substr( $text, 0, 10 ) != '<!--GFM-->' ) {
				$text = '';
			}
		}

		return stripslashes( $text );
	}

	/**
	 * Gets the version info for the add-on.
	 *
	 * @since Unknown
	 * @since 2.9.23 Updated the returned array to include `minimum_requirements`.
	 * @since 3.2.3 Updated the returned array to include `is_minimum_requirements_blocked` and `minimum_requirements_message`.
	 *
	 * @param string $slug      The slug of the add-on.
	 * @param bool   $use_cache Whether to use the cache.
	 *
	 * @return array
	 */
	private function get_version_info( $slug, $use_cache = true ) {
		$version_info            = GFCommon::get_version_info( $use_cache );
		$offering                = rgars( $version_info, "offerings/{$slug}" );
		$min_requirements        = rgar( $offering, 'minimum_requirements', array() );
		$min_requirements_result = GFCommon::evaluate_minimum_requirements( $min_requirements );

		return array(
			'is_error'                        => rgar( $version_info, 'is_error' ),
			'is_valid_key'                    => rgar( $version_info, 'is_valid_key' ),
			'is_available'                    => rgar( $offering, 'is_available' ),
			'version'                         => rgar( $offering, 'version' ),
			'version_latest'                  => rgar( $offering, 'version_latest' ),
			'url'                             => rgar( $offering, 'url' ),
			'minimum_requirements'            => $min_requirements,
			'is_minimum_requirements_blocked' => $min_requirements_result['block'],
			'minimum_requirements_message'    => $min_requirements_result['message'],
		);
	}

	private function get_remote_request_params( $offering, $key, $version ) {
		global $wpdb;

		return sprintf( 'of=%s&key=%s&v=%s&wp=%s&php=%s&mysql=%s', urlencode( $offering ), urlencode( $key ), urlencode( $version ), urlencode( get_bloginfo( 'version' ) ), urlencode( phpversion() ), urlencode( GFCommon::get_db_version() ) );
	}

	private function get_key() {
		if ( $this->_is_gravityforms_supported ) {
			return GFCommon::get_key();
		} else {
			return '';
		}
	}

	public function get_update_info( $updates ) {

		$force_check = rgget( 'force-check' ) == 1;
		$version_info = $this->get_version_info( $this->_slug, ! $force_check );

		$plugin_file = $this->_path;
		$upgrade_url = wp_nonce_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_file ), 'upgrade-plugin_' . $plugin_file );

		if ( ! rgar( $version_info, 'is_valid_key' ) ) {

			$version_icon    = 'dashicons-no';
			$version_message = sprintf(
				'<p>%s</p>',
				sprintf(
					esc_html( '%sRegister%s your copy of Gravity Forms to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ),
					'<a href="admin.php?page=gf_settings">',
					'</a>',
					'<a href="https://www.gravityforms.com">',
					'</a>'
				)
			);

		} elseif ( version_compare( $this->_version, $version_info['version'], '<' ) ) {

			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . urlencode( $this->_slug ) . '&section=changelog&TB_iframe=true&width=600&height=800' );
			$message_link_text = sprintf( esc_html__( 'View version %s details', 'gravityforms' ), $version_info['version'] );
			$message_link      = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', esc_url( $details_url ), esc_attr( $this->_title ), $message_link_text );
			$message           = sprintf( esc_html__( 'There is a new version of %1$s available. %s.', 'gravityforms' ), $this->_title, $message_link );

			$version_icon    = 'dashicons-no';
			$version_message = $message;

		} else {

			$version_icon    = 'dashicons-yes';
			$version_message = sprintf( esc_html__( 'Your version of %s is up to date.', 'gravityforms' ), $this->_title );
		}

		$updates[] = array(
			'name'              => esc_html( $this->_title ),
			'is_valid_key'      => rgar( $version_info, 'is_valid_key' ),
			'path'              => $this->_path,
			'slug'              => $this->_slug,
			'latest_version'    => $version_info['version'],
			'installed_version' => $this->_version,
			'upgrade_url'       => $upgrade_url,
			'download_url'      => $version_info['url'],
			'version_icon'      => $version_icon,
			'version_message'   => $version_message,
			'version_info'      => $version_info,
		);

		return $updates;

	}

	/**
	 * @depecated 2.2 Was the callback for the gform_updates action, which was removed when the Forms > System Status > Updates page was implemented.
	 * @remove-in 4.0
	 */
	public function display_updates() {
		_deprecated_function( __METHOD__, '2.2' );

		?>
		<div class="wrap <?php echo esc_attr( GFCommon::get_browser_class() ); ?>">
			<h2><?php esc_html_e( $this->_title ); ?></h2>
			<?php
			$force_check = rgget( 'force-check' ) == 1;
			$version_info = $this->get_version_info( $this->_slug, ! $force_check );

			if ( ! rgar( $version_info, 'is_valid_key' ) ) {
				?>
				<div class="gf_update_expired alert_red">
					<?php printf( esc_html__( '%sRegister%s your copy of Gravity Forms to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ), '<a href="admin.php?page=gf_settings">','</a>','<a href="https://www.gravityforms.com">', '</a>' ); ?>
				</div>

			<?php
			} elseif ( version_compare( $this->_version, $version_info['version'], '<' ) ) {

				if ( rgar( $version_info, 'is_valid_key' ) ) {
					$plugin_file = $this->_path;
					$upgrade_url = wp_nonce_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_file ), 'upgrade-plugin_' . $plugin_file );
					$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . urlencode( $this->_slug ) . '&section=changelog&TB_iframe=true&width=600&height=800' );
					$message_link_text = sprintf( esc_html__( 'View version %s details', 'gravityforms' ), $version_info['version'] );
					$message_link      = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', esc_url( $details_url ), esc_attr( $this->_title ), $message_link_text );
					$message           = sprintf( esc_html__( 'There is a new version of %1$s available. %s.', 'gravityforms' ), $this->_title, $message_link );

					?>
					<div class="gf_update_outdated alert_yellow">
						<?php echo $message . ' <p>' . sprintf( esc_html__( 'You can update to the latest version automatically or download the update and install it manually. %sUpdate Automatically%s %sDownload Update%s', 'gravityforms' ), "</p><a class='button-primary' href='{$upgrade_url}'>", '</a>', "&nbsp;<a class='button' href='{$version_info['url']}'>", '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
					</div>
				<?php
				}
			} else {

				?>
				<div class="gf_update_current alert_green">
					<?php printf( esc_html__( 'Your version of %s is up to date.', 'gravityforms' ), esc_html( $this->_title ) ); ?>
				</div>
			<?php
			}

			?>

		</div>
	<?php
	}
}
