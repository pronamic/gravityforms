<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_System_Status
 *
 * Handles the system status page.
 *
 * @since 2.2
 */
class GF_System_Status {

	/**
	 * Determines which system status page to display.
	 *
	 * @since  2.2
	 * @access public
	 */
	public static function system_status_page() {

		$subview = rgget( 'subview' ) ? rgget( 'subview' ) : 'report';

		switch ( $subview ) {
			case 'report':
				GF_System_Report::system_report();
				break;
			case 'updates':
				GF_Update::updates();
				break;
			default:
				/**
				 * Fires when the settings page view is determined
				 *
				 * Used to add additional pages to the form settings
				 *
				 * @since Unknown
				 *
				 * @param string $subview Used to complete the action name, allowing an additional subview to be detected
				 */
				do_action( "gform_system_status_page_{$subview}" );
		}

	}

	/**
	 * Get System Status page subviews.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return array
	 */
	public static function get_subviews() {

		// Define default subview.
		$subviews = array(
			10 => array(
				'name'  => 'report',
				'label' => __( 'System Report', 'gravityforms' ),
			),
		);

		// Add Update subview if user has capabilities.
		if ( current_user_can( 'install_plugins' ) ) {
			$subviews[20] = array(
				'name'  => 'updates',
				'label' => __( 'Updates', 'gravityforms' ),
			);
		}

		/**
		 * Modify menu items which will appear in the System Status menu.
		 *
		 * @since 2.2
		 * @param array $subviews An array of menu items to be displayed on the System Status page.
		 */
		$subviews = apply_filters( 'gform_system_status_menu', $subviews );

		ksort( $subviews, SORT_NUMERIC );

		return $subviews;

	}

	/**
	 * Get current System Status subview.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_current_subview() {

		return rgempty( 'subview', $_GET ) ? 'report' : rgget( 'subview' );

	}

	/**
	 * Render System Status page header.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param string $title Page title.
	 *
	 * @uses GFCommon::display_dismissible_message()
	 * @uses GFCommon::get_base_url()
	 * @uses GFCommon::get_browser_class()
	 * @uses GFCommon::get_remote_message()
	 * @uses GFSystemStatus::get_current_subview()
	 * @uses GFSystemStatus::get_subviews()
	 */
	public static function page_header( $title = '' ) {
	    GFForms::admin_header( self::get_subviews(), false );
	}

	/**
	 * Render System Status page footer.
	 *
	 * @since  2.2
	 * @access public
	 */
	public static function page_footer() {
	    GFForms::admin_footer();
	}
}
