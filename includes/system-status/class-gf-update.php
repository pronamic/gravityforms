<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Update
 *
 * Handles the Updates subview on the System Status page.
 */
class GF_Update {

	/**
	 * Display updates page.
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
	public static function updates() {

		// If user does not have access to this page, die.
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_updates' ) ) {
			wp_die( esc_html__( "You don't have permissions to view this page", 'gravityforms' ) );
		}

		// Get available updates.
		$updates = self::available_updates();

		// Display page header.
		GF_System_Status::page_header();

		wp_print_styles( array( 'thickbox' ) );

		?>
        <div class="gform-settings__content">
            <table cellspacing="0" cellpadding="0" class="wp-list-table plugins">
                <thead>
                <tr id="updates">
                    <th scope="col"
                        class="manage-column column-name column-primary"><?php esc_html_e( 'Updates', 'gravityforms' ) ?></th>
                </tr>
                <tr>
                    <th scope="col" id="name"
                        class="manage-column column-name column-primary"><?php esc_html_e( 'Plugin', 'gravityforms' ); ?></th>
                    <th scope="col" id="description"
                        class="manage-column column-description"><?php esc_html_e( 'Description', 'gravityforms' ); ?></th>
                </tr>
                </thead>

                <tbody id="the-list">
				<?php
				$plugins = get_plugins();
				// Loop through updates.
				foreach ( $updates as $update ) {
					$installed_version = rgar( $update, 'installed_version' );
					$latest_version    = rgar( $update, 'latest_version' );
					$name              = rgar( $update, 'name' );
					$slug              = rgar( $update, 'slug' );
					$plugin_path       = rgar( $update, 'path' );
					
					$update_available  = ! empty( $latest_version ) && version_compare( $installed_version, $latest_version, '<' );
					$update_class      = $update_available ? ' update' : '';
					$settings_link     = $slug == 'gravityforms' ? admin_url( 'admin.php?page=gf_settings' ) : admin_url( 'admin.php?page=gf_settings&subview=' . $slug );
					
					$plugin            = rgar( $plugins, $update['path'] );
					$description       = rgar( $plugin, 'Description' );
					$installed_version = rgar( $update, 'installed_version' );
					?>
					<tr class="inactive<?php echo $update_class ?>" data-slug="admin-bar-form-search"
						data-plugin="gw-admin-bar-form-manager.php">
						<td class="plugin-title column-primary"><strong><?php echo esc_html( $name ) ?></strong>
							<div class="row-actions visible">
								<span class="deactivate"><a
											href="<?php echo esc_url( $settings_link ); ?>"><?php esc_html_e( 'Settings', 'gravityforms' ) ?></a></span>
							</div>
						</td>
						<td class="column-description desc">
							<div class="plugin-description">
								<p><?php echo esc_html( $description ); ?></p>
							</div>
							<div class="active second plugin-version-author-uri">
								Version <?php echo esc_html( $installed_version ); ?> |
								<a href="<?php echo rgar( $plugin, 'PluginURI' ); ?>"><?php esc_html_e( 'Visit plugin page', 'gravityforms' ); ?></a>
							</div>
							<?php 
							$messages = GFForms::get_status_messages( $plugin_path, $plugin, $slug, $installed_version );
							if ( ! empty( $messages) ) {
								echo self::get_markup_for_status_messages( $messages );
							}
							?>
						</td>
					</tr>
					<?php 
				} 
				?>
                </tbody>
            </table>
        </div>

		<?php

		// Display page footer.
		GF_System_Status::page_footer();

	}

	/**
	 * Get available Gravity Forms updates.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFCommon::get_version_info()
	 *
	 * @return array
	 */
	public static function available_updates() {

		// Initialize updates array.
		$updates = array();

		// Get Gravity Forms version info.
		$version_info = GFCommon::get_version_info( false );

		// Define Gravity Forms plugin path.
		$plugin_path = plugin_basename( GFCommon::get_base_path() . '/gravityforms.php' );

		// Get upgrade URL.
		$upgrade_url = wp_nonce_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_path ), 'upgrade-plugin_' . $plugin_path );


		$version_message = array();

		// Prepare version message and icon.
		if ( empty( $version_info['version'] ) || version_compare( GFForms::$version, $version_info['version'], '>=' ) ) {

			$version_icon    = 'dashicons-yes';
			$version_message[] = esc_html__( 'Your version of Gravity Forms is up to date.', 'gravityforms' );

		} else {

			if ( rgar( $version_info, 'is_valid_key' ) && rgar( $version_info, 'is_available' ) ) {

				$version_icon    = 'dashicons-no';
				$version_message[] = sprintf(
					'%s<p>%s</p>',
					esc_html__( 'There is a new version of Gravity Forms available.', 'gravityforms' ),
					esc_html__( 'You can update to the latest version automatically or download the update and install it manually.', 'gravityforms' )
				);
			} else {


				$version_icon    = 'dashicons-no';
				$version_message[] = sprintf(
					'%s<p>%s</p>',
					esc_html__( 'There is a new version of Gravity Forms available.', 'gravityforms' ),
					sprintf(
						esc_html__( '%sRegister%s your copy of Gravity Forms to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ),
						'<a href="admin.php?page=gf_settings">',
						'</a>',
						'<a href="https://www.gravityforms.com">',
						'</a>'
					)
				);
			}
		}

		// Add Gravity Forms core to updates array.
		$updates[] = array(
			'is_valid_key'      => rgar( $version_info, 'is_valid_key' ),
			'name'              => esc_html__( 'Gravity Forms', 'gravityforms' ),
			'path'              => $plugin_path,
			'slug'              => 'gravityforms',
			'latest_version'    => rgar( $version_info, 'version' ),
			'installed_version' => GFCommon::$version,
			'upgrade_url'       => $upgrade_url,
			'download_url'      => rgar( $version_info, 'url' ),
			'version_icon'      => $version_icon,
			'version_message'   => $version_message,
		);

		/**
		 * Modify plugins displayed on the Updates page.
		 *
		 * @since 2.2
		 *
		 * @param array $updates An array of plugins displayed on the Updates page.
		 */
		$updates = apply_filters( 'gform_updates_list', $updates );

		return $updates;

	}

	/**
	 * Generates the markup for displaying the status messages. 
	 * 
	 * @since: 2.9
	 *
	 * @param array $messages An array of status messages.
	 * 
	 * @return string The html markup for the status messages.
	 */
	public static function get_markup_for_status_messages( $messages ) {
		$markup = '';

		if ( ! empty( $messages ) ) {
			$markup .= '<div class="update-message notice inline notice-warning notice-alt">';
			$markup .= '<p>';
			$markup .= implode( ' ', $messages );
			$markup .= '</p>';
			$markup .= '</div>';
		}

		return $markup;
	}

}
