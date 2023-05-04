<?php
/**
 * Trait Redirects_On_Save
 *
 * @since 2.5
 */

trait Redirects_On_Save {

	private static $_saved_item_id = null;

	/**
	 * Redirect the user to the newly-created Confirmation after save.
	 *
	 * @since 2.5
	 */
	public static function redirect_after_valid_save( $query_arg ) {
		if ( rgget( $query_arg ) != 0 ) {
			return;
		}

		if ( is_null( self::$_saved_item_id ) ) {
			return;
		}

		$values   = self::get_settings_renderer()->get_posted_values();
		$is_valid = self::get_settings_renderer()->validate( $values );

		if ( ! $is_valid ) {
			return;
		}

		$url = get_admin_url();
		$url = add_query_arg(
			array(
				'page'     => rgget( 'page' ),
				'view'     => rgget( 'view' ),
				'subview'  => rgget( 'subview' ),
				'id'       => rgget( 'id' ),
				$query_arg => self::$_saved_item_id,
			),
			$url
		);

		self::save_flash_message();

		wp_safe_redirect( esc_url_raw( $url ) );
	}

	/**
	 * Get the formatted Option Name for this item.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	protected static function get_flash_id() {
		return sprintf( 'gform_save_redirect_message_' . self::$_saved_item_id );
	}

	/**
	 * Get the saved flash message flag for this item.
	 *
	 * @since 2.5
	 *
	 * @return false|mixed|void
	 */
	protected static function get_flash_message() {
		return get_option( self::get_flash_id() );
	}

	/**
	 * Save the flash message flag.
	 *
	 * @since 2.5
	 */
	protected static function save_flash_message() {
		update_option( self::get_flash_id(), true );
	}

	/**
	 * Flush the flash message flag.
	 *
	 * @since 2.5
	 */
	protected static function flush_flash_message() {
		delete_option( self::get_flash_id() );
	}

	/**
	 * Check if the current page load is a Save Redirect by checking for a flash message flag.
	 *
	 * @since 2.5
	 *
	 * @param $attr
	 *
	 * @return bool
	 */
	protected static function is_save_redirect( $attr ) {
		if ( ! empty( rgget( $attr ) ) ) {
			self::$_saved_item_id = rgget( $attr );
		}

		if ( empty( self::$_saved_item_id ) ) {
			return false;
		}

		$has = self::get_flash_message();

		if ( empty( $has ) ) {
			return false;
		}

		// Flush to avoid showing every time.
		self::flush_flash_message();

		return true;
	}
}
