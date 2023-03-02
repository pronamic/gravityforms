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
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['asset'] ) && ! empty( $_POST['state'] ) ) {
			// Option is being updated by the ajax request performed when using the enable/disable auto-updates links on the plugins page.
			$asset = sanitize_text_field( urldecode( $_POST['asset'] ) );
			if ( $asset !== GF_PLUGIN_BASENAME ) {
				return;
			}

			$is_enabled = $_POST['state'] === 'enable';
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

}
