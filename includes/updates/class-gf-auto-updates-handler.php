<?php
/**
 * Handles syncing the GF and WP options for enabling/disabling auto updates.
 *
 * @package Gravity_Forms\Gravity_Forms\Updates
 */

namespace Gravity_Forms\Gravity_Forms\Updates;

/**
 * Class GF_Auto_Updates_Handler
 *
 * @since 2.7.2
 */
class GF_Auto_Updates_Handler {

	/**
	 * Updates the background updates setting when the WordPress auto_update_plugins option is updated.
	 *
	 * @since 2.7.2
	 *
	 * @param string $option    The name of the option.
	 * @param array  $value     The current value of the option.
	 * @param array  $old_value The previous value of the option.
	 */
	public function wp_option_updated( $option, $value, $old_value = array() ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['asset'] ) && ! empty( $_POST['state'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Option is being updated by the ajax request performed when using the enable/disable auto-updates links on the plugins page.
			$asset = sanitize_text_field( urldecode( wp_unslash( $_POST['asset'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $asset !== GF_PLUGIN_BASENAME ) {
				return;
			}

			$is_enabled = $_POST['state'] === 'enable'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			// Option is being updated by some other means (e.g. CLI).
			$is_enabled  = in_array( GF_PLUGIN_BASENAME, $value );
			$was_enabled = in_array( GF_PLUGIN_BASENAME, $old_value );

			if ( $is_enabled === $was_enabled ) {
				return;
			}
		}

		$this->update_gf_option( $is_enabled );
	}

	/**
	 * Updates the background updates setting when the WordPress auto_update_plugins option is deleted.
	 *
	 * @since 2.7.2
	 */
	public function wp_option_deleted() {
		$this->update_gf_option( false );
	}

	/**
	 * Updates the WordPress auto_update_plugins option when the gform_enable_background_updates option is saved for the first time.
	 *
	 * @since 2.7.2
	 *
	 * @param string $option The option name.
	 * @param mixed  $value  The current value of the option.
	 *
	 * @return void
	 */
	public function gf_option_added( $option, $value ) {
		$this->update_wp_option( (bool) $value );
	}

	/**
	 * Updates the WordPress auto_update_plugins option when the gform_enable_background_updates option is updated.
	 *
	 * @since 2.7.2
	 *
	 * @param mixed $old_value The previous value of the option.
	 * @param mixed $value     The current value of the option.
	 *
	 * @return void
	 */
	public function gf_option_updated( $old_value, $value ) {
		if ( $old_value == $value ) {
			return;
		}
		$this->update_wp_option( (bool) $value );
	}

	/**
	 * Updates the WordPress auto_update_plugins option when the gform_enable_background_updates option is deleted.
	 *
	 * @since 2.7.2
	 */
	public function gf_option_deleted() {
		$this->update_wp_option( false );
	}

	/**
	 * Updates the gform_enable_background_updates option.
	 *
	 * @since 2.7.2
	 *
	 * @param bool $is_enabled Indicates if background updates are enabled for Gravity Forms.
	 *
	 * @return void
	 */
	public function update_gf_option( $is_enabled ) {
		$this->remove_gf_hooks();
		update_option( 'gform_enable_background_updates', $is_enabled );
		$this->add_gf_hooks();
	}

	/**
	 * Updates the WordPress auto_update_plugins option to enable or disable automatic updates so the correct state is displayed on the plugins page.
	 *
	 * @since 2.7.2
	 *
	 * @param bool $is_enabled Indicates if background updates are enabled for Gravity Forms.
	 */
	public function update_wp_option( $is_enabled ) {
		$option       = 'auto_update_plugins';
		$auto_updates = (array) get_site_option( $option, array() );

		if ( $is_enabled ) {
			$auto_updates[] = GF_PLUGIN_BASENAME;
			$auto_updates   = array_unique( $auto_updates );
		} else {
			$auto_updates = array_diff( $auto_updates, array( GF_PLUGIN_BASENAME ) );
		}

		$this->remove_wp_hooks();
		update_site_option( $option, $auto_updates );
		$this->add_wp_hooks();
	}

	/**
	 * Adds the action hooks for the gform_enable_background_updates option.
	 *
	 * @since 2.7.2
	 *
	 * @return void
	 */
	public function add_gf_hooks() {
		add_action( 'add_option_gform_enable_background_updates', array(
			$this,
			'gf_option_added',
		), 10, 2 );
		add_action( 'update_option_gform_enable_background_updates', array(
			$this,
			'gf_option_updated',
		), 10, 2 );
		add_action( 'delete_option_gform_enable_background_updates', array(
			$this,
			'gf_option_deleted',
		) );
	}

	/**
	 * Removes the action hooks for the gform_enable_background_updates option.
	 *
	 * @since 2.7.2
	 *
	 * @return void
	 */
	public function remove_gf_hooks() {
		remove_action( 'add_option_gform_enable_background_updates', array(
			$this,
			'gf_option_added',
		), 10, 2 );
		remove_action( 'update_option_gform_enable_background_updates', array(
			$this,
			'gf_option_updated',
		), 10, 2 );
		remove_action( 'delete_option_gform_enable_background_updates', array(
			$this,
			'gf_option_deleted',
		) );
	}

	/**
	 * Adds the action hooks for the auto_update_plugins option.
	 *
	 * @since 2.7.2
	 *
	 * @return void
	 */
	public function add_wp_hooks() {
		add_action( 'add_site_option_auto_update_plugins', array(
			$this,
			'wp_option_updated',
		), 10, 2 );
		add_action( 'update_site_option_auto_update_plugins', array(
			$this,
			'wp_option_updated',
		), 10, 3 );
		add_action( 'delete_site_option_auto_update_plugins', array(
			$this,
			'wp_option_deleted',
		) );
	}

	/**
	 * Removes the action hooks for the auto_update_plugins option.
	 *
	 * @since 2.7.2
	 *
	 * @return void
	 */
	public function remove_wp_hooks() {
		remove_action( 'add_site_option_auto_update_plugins', array(
			$this,
			'wp_option_updated',
		) );
		remove_action( 'update_site_option_auto_update_plugins', array(
			$this,
			'wp_option_updated',
		) );
		remove_action( 'delete_site_option_auto_update_plugins', array(
			$this,
			'wp_option_deleted',
		) );
	}

	/**
	 * Updates the WP auto_update_plugins option to match the background updates setting.
	 *
	 * @since 2.7.2
	 *
	 * @return void
	 */
	public function activation_sync() {
		$enabled = (bool) get_option( 'gform_enable_background_updates' );
		if ( ! $enabled ) {
			return;
		}

		$this->update_wp_option( $enabled );
	}

	/**
	 * Includes inactive add-ons in the plugin update check already running for Gravity Forms.
	 *
	 * Hooked to the same update-plugins transients as GFForms::check_update(). WordPress reads
	 * those while checking for plugin updates, not on gform_post_loaded. Returning the transient
	 * unchanged lets GFAutoUpgrade append each inactive add-on at the normal check priority.
	 *
	 * @since next
	 *
	 * @param object|false $update_data Update transient value.
	 *
	 * @return object|false
	 */
	public function include_inactive_addon_updates( $update_data ) {
		$this->init_inactive_addon_updates();

		return $update_data;
	}

	/**
	 * Initializes the GFAutoUpgrade class for inactive add-ons.
	 *
	 * @since 3.2.3
	 *
	 * @return void
	 */
	public function init_inactive_addon_updates() {
		static $initialized = false;

		if ( $initialized ) {
			return;
		}

		$initialized = true;

		$versions = \GFCommon::get_version_info( true );

		if ( empty( $versions['offerings'] ) || ! is_array( $versions['offerings'] ) ) {
			return;
		}

		$plugins = \GFForms::get_installed_plugins();

		foreach ( $plugins as $slug => $plugin ) {
			if ( rgar( $plugin, 'is_active' ) || ! isset( $plugin['version'] ) || ! str_starts_with( $slug, 'gravityforms' ) ) {
				continue;
			}

			$offering = rgar( $versions['offerings'], $slug );
			if ( empty( $offering ) || ! is_array( $offering ) ) {
				continue;
			}

			require_once GF_PLUGIN_DIR_PATH . 'includes/addon/class-gf-auto-upgrade.php';

			$min_gf_version = $this->get_min_gf_version( rgar( $offering, 'minimum_requirements' ) );

			$upgrader = new \GFAutoUpgrade(
				$slug,
				$plugin['version'],
				$min_gf_version,
				$plugin['name'],
				WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin['path'],
				$plugin['path'],
				'https://gravityforms.com',
				version_compare( \GFForms::$version, $min_gf_version, '>=' ),
				true
			);

			// Update transients and changelog AJAX requests can run after init, so register the upgrader hooks now.
			if ( did_action( 'init' ) ) {
				remove_action( 'init', array( $upgrader, 'init' ) );
				$upgrader->init();
			}
		}
	}

	/**
	 * Gets the minimum Gravity Forms version required for an offering from its minimum requirements data.
	 *
	 * @since 3.2.3
	 *
	 * @param array $minimum_requirements The minimum requirements for the offering.
	 *
	 * @return string
	 */
	private function get_min_gf_version( $minimum_requirements ) {
		if ( empty( $minimum_requirements ) || ! is_array( $minimum_requirements ) ) {
			return \GFForms::$version;
		}

		foreach ( $minimum_requirements as $requirement ) {
			if ( rgar( $requirement, 'minimum_requirements_slug' ) !== 'gravityforms' ) {
				continue;
			}

			$version = rgar( $requirement, 'minimum_requirements_version' );
			if ( empty( $version ) ) {
				return \GFForms::$version;
			}

			if ( $version === 'latest' ) {
				return rgar( $requirement, 'minimum_requirements_version_latest', \GFForms::$version );
			}

			return $version;
		}

		return \GFForms::$version;
	}

}
