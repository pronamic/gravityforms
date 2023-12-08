<?php
/**
 * Displays a splash page when a user updates to a new version.
 *
 * @package Gravity_Forms\Gravity_Forms\Splash_Page
 */

namespace Gravity_Forms\Gravity_Forms\Splash_Page;

use \GFForms;
use \GFCommon;
use \Gravity_Forms\Gravity_Forms\Splash_Page_Template_Tags;

/**
 * Class GF_Splash_Page
 *
 * @since 2.6
 *
 * Displays a splash page when a user updates to a new version.
 */
class GF_Splash_Page {

	/**
	 * The latest version that has a splash page.
	 *
	 * @var string
	 */
	protected $about_version;

	/**
	 * The class that generates template tags.
	 *
	 * @var Splash_Page_Template_Tags\GF_Splash_Page_Template_Tags
	 */
	protected $tags;

	/**
	 * The directory where splash page images are stored.
	 *
	 * @var string
	 */
	protected $img_dir;

	/**
	 * GF_Splash_Page_Constructor
	 *
	 * @param Splash_Page_Template_Tags\GF_Splash_Page_Template_Tags $tags
	 */
	public function __construct( $tags ) {
		$this->about_version = '2.8';

		$this->tags = $tags;

		$this->img_dir = 'https://cdn.gravity.com/gravityforms/about-page/2.8/';
	}

	/**
	 * Conditional test for if we're on the splash page.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function is_splash_page() {
		$screen = get_current_screen();

		return ( 'forms_page_gf_system_status' === $screen->base ) && ( 'about' === rgget( 'subview' ) );
	}

	/**
	 * Enqueue splash page styles.
	 *
	 * @since 2.6
	 */
	public function splash_page_styles() {
		if ( ! $this->should_display() && ! $this->is_splash_page() ) {
			return;
		}

		wp_enqueue_style( 'gform_admin' );
	}

	/**
	 * Add a body class to the splash page.
	 *
	 * @since 2.6
	 *
	 * @param $classes
	 *
	 * @return string
	 */
	public function body_class( $classes ) {
		if ( $this->is_splash_page() ) {
			$classes .= ' toplevel_page_gf_splash';
		}
		return $classes;
	}

	/**
	 * Add "About" to the title tag.
	 *
	 * If you add a submenu page without a parent page, it doesn't get a title, so we need to add one manually.
	 *
	 * @since 2.6
	 *
	 * @param $title
	 *
	 * @return mixed|string
	 */
	public function admin_title( $title ) {
		if ( $this->is_splash_page() ) {
			$title = __( 'About', 'gravityforms' ) . ' ' . $this->about_version . ' - Gravity Forms';
		}
		return $title;
	}

	/**
	 * Set a transient if we need to show the splash page.
	 *
	 * @since 2.6
	 *
	 * @param $version
	 * @param $from_db_version
	 * @param $force_upgrade
	 */
	public function set_upgrade_transient( $version, $from_db_version, $force_upgrade ) {
		if ( $force_upgrade ) {
			return;
		}

		if ( $this->need_splash_page( $from_db_version, GFForms::$version, $this->about_version ) ) {
			set_transient( 'gf_updated', $this->about_version );
		}
	}

	/**
	 * Determine whether we need to display a splash page.
	 *
	 * If the old version is earlier than $this->about_version, and the new version is the same or later than $this->about_version,
	 * we need to show the splash page.
	 *
	 * @since 2.6
	 *
	 * @param $old_version
	 * @param $new_version
	 * @param $about_version
	 *
	 * @return bool
	 */
	public function need_splash_page( $old_version, $new_version, $about_version ) {
		$old_version = implode( '.', array_slice( preg_split( '/[.-]/', $old_version ), 0, 2 ) );
		$new_version = implode( '.', array_slice( preg_split( '/[.-]/', $new_version ), 0, 2 ) );
		return version_compare( $old_version, $about_version, '<' ) && $new_version >= $about_version;
	}

	/**
	 * Add a link to the splash page in the system status menu.
	 *
	 * @since 2.6
	 *
	 * @param array $subviews
	 */
	public function system_status_link( $subviews ) {
		$subviews[19] = array(
			'name'  => 'about',
			'label' => sprintf( __( 'About %s', 'gravityforms' ), $this->about_version ),
		);

		return $subviews;
	}

	/**
	 * Display the splash page.
	 *
	 * @since 2.6
	 */
	public function about_page() {
		if ( get_transient( 'gf_updated' ) ) {
			delete_transient( 'gf_updated' );
		}

		ob_start();
		include __DIR__ . '/gf_splash.php';
		echo ob_get_clean();
	}

	/**
	 * Display the splash page as a modal.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function about_page_modal() {
		if ( ! $this->should_display() ) {
			return null;
		}

		ob_start();
		$this->about_page();
		$markup = ob_get_clean();

		return sprintf( '<script type="text/template" data-js="gf-splash-template">%s</script>', $markup );
	}

	/**
	 * Whether the splash page should display.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function should_display() {
		return $this->about_version == get_transient( 'gf_updated' ) && GFForms::is_gravity_page() && GFCommon::current_user_can_any( GFCommon::all_caps() );
	}

}
