<?php

namespace Gravity_Forms\Gravity_Forms;

use GFCommon;

/**
 * Allows to download translations from TranslationsPress
 * This is a modified version of the library available at https://github.com/WP-Translations/t15s-registry
 * This version aims to be compatible with PHP 5.2, and supports only plugins.
 *
 * @since 2.5
 */
class TranslationsPress_Updater {

	const T15S_TRANSIENT_KEY = 't15s-registry-gforms';
	const T15S_API_URL       = 'https://packages.translationspress.com/rocketgenius/packages.json';

	/**
	 * The plugin slug.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * The locales installed during the current request.
	 *
	 * @since 2.5.6
	 *
	 * @var array
	 */
	private $installed = array();

	/**
	 * Cached TranslationsPress data for all Rocketgenius plugins.
	 *
	 * @since 2.5.6
	 *
	 * @var null|object
	 */
	private static $all_translations;

	/**
	 * The current instances of this class with the slugs as the keys.
	 *
	 * @since 2.5.6
	 *
	 * @var TranslationsPress_Updater[]
	 */
	private static $_instances = array();

	/**
	 * Returns an instance of this class for the given slug.
	 *
	 * @since 2.5.6
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return TranslationsPress_Updater
	 */
	public static function get_instance( $slug ) {
		if ( empty( self::$_instances[ $slug ] ) ) {
			self::$_instances[ $slug ] = new self( $slug );
		}

		return self::$_instances[ $slug ];
	}

	/**
	 * Adds a new project to load translations for.
	 *
	 * @since 2.5
	 *
	 * @param string $slug       The plugin slug.
	 * @param string $deprecated Not used. Previously, the locale to be installed.
	 */
	public function __construct( $slug, $deprecated = '' ) {
		$this->slug = $slug;

		if ( 'gravityforms' === $slug ) {
			// Translations data for all Rocketgenius plugins is stored together so we only need to add this hook once.
			add_action( 'delete_site_transient_update_plugins', array( __CLASS__, 'refresh_all_translations' ) );
		}

		add_action( 'gform_post_install', array( $this, 'install' ), 10, 0 );
		add_action( 'gform_post_upgrade', array( $this, 'install' ), 10, 0 );
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );

		add_filter( 'translations_api', array( $this, 'translations_api' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'site_transient_update_plugins' ) );
	}

	/**
	 * Short-circuits translations API requests for private projects.
	 *
	 * @since 2.5
	 *
	 * @param bool|array $result         The result object. Default false.
	 * @param string     $requested_type The type of translations being requested.
	 * @param object     $args           Translation API arguments.
	 *
	 * @return bool|array
	 */
	public function translations_api( $result, $requested_type, $args ) {
		if ( 'plugins' !== $requested_type || $this->slug !== $args['slug'] ) {
			return $result;
		}

		return $this->get_plugin_translations();
	}

	/**
	 * Filters the translations transients to include the current plugin.
	 *
	 * @see wp_get_translation_updates()
	 *
	 * @since 2.5
	 *
	 * @param mixed $value The transient value.
	 *
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		if ( ! $value ) {
			$value = new \stdClass();
		}

		if ( ! isset( $value->translations ) ) {
			$value->translations = array();
		}

		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			return $value;
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation ) ) {
				continue;
			}

			$translation['type'] = 'plugin';
			$translation['slug'] = $this->slug;

			$value->translations[] = $translation;
		}

		return $value;
	}

	/**
	 * Gets the TranslationsPress data for the current plugin.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	private function get_plugin_translations() {
		self::set_all_translations();

		return (array) rgar( self::$all_translations->projects, $this->slug );
	}

	/**
	 * Refreshes the cached TranslationsPress data, if expired.
	 *
	 * @since 2.5.6
	 */
	public static function refresh_all_translations() {
		static $done;

		if ( $done ) {
			return;
		}

		self::$all_translations = null;
		self::set_all_translations();
		$done = true;
	}

	/**
	 * Determines if the cached TranslationsPress data needs refreshing.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	private static function is_transient_expired() {
		$cache_lifespan = 12 * HOUR_IN_SECONDS;

		return ! isset( self::$all_translations->_last_checked ) || ( time() - self::$all_translations->_last_checked ) > $cache_lifespan;
	}

	/**
	 * Gets the translations data from the TranslationsPress API.
	 *
	 * @since 2.5.6
	 *
	 * @return array
	 */
	private static function get_remote_translations_data() {
		$result = json_decode( wp_remote_retrieve_body( wp_remote_get( self::T15S_API_URL, array( 'timeout' => 3 ) ) ), true );

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Caches the TranslationsPress data, if not already cached.
	 *
	 * @since 2.5.6
	 */
	private static function set_all_translations() {
		if ( is_object( self::$all_translations ) ) {
			return;
		}

		self::$all_translations = get_site_transient( self::T15S_TRANSIENT_KEY );
		if ( is_object( self::$all_translations ) && ! self::is_transient_expired() ) {
			return;
		}

		self::$all_translations                = new \stdClass();
		self::$all_translations->projects      = self::get_remote_translations_data();
		self::$all_translations->_last_checked = time();
		set_site_transient( self::T15S_TRANSIENT_KEY, self::$all_translations );
	}

	/**
	 * Gets the translations for a given project.
	 *
	 * @since 2.5
	 *
	 * @deprecated 2.5.6
	 *
	 * @param string $url Full GlotPress API URL for the project.
	 *
	 * @return array Translation data.
	 */
	public static function get_translations( $url ) {
		_deprecated_function( __METHOD__, '2.5.6', '\Gravity_Forms\Gravity_Forms\TranslationsPress_Updater::get_plugin_translations' );
		self::set_all_translations();

		return self::$all_translations->projects;
	}

	/**
	 * Downloads and installs the translations for the specified plugin.
	 *
	 * @since 2.5
	 *
	 * @param string $slug   The plugin slug.
	 * @param string $locale The locale when the site locale is changed or an empty string to install all the user available locales.
	 */
	public static function download_package( $slug, $locale = '' ) {
		self::get_instance( $slug )->install( $locale );
	}

	/**
	 * Triggers translation installation, if required.
	 *
	 * @since 2.5.6
	 *
	 * @param string $locale The locale when the site locale is changed or an empty string to install all the user available locales.
	 */
	public function install( $locale = '' ) {
		if ( $locale && in_array( $locale, $this->installed ) ) {
			return;
		}

		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			GFCommon::log_error( __METHOD__ . sprintf( '(): Aborting; No translations list for %s.', $this->slug ) );

			return;
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation, $locale ) ) {
				continue;
			}

			$this->install_translation( $translation );

			if ( $locale ) {
				return;
			}
		}
	}

	/**
	 * Downloads and installs the given translation.
	 *
	 * @since 2.5.6
	 *
	 * @param array $translation The translation data.
	 */
	private function install_translation( $translation ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/admin.php';

			if ( ! \WP_Filesystem() ) {
				GFCommon::log_error( __METHOD__ . '(): Aborting; unable to init WP_Filesystem.' );

				return;
			}
		}

		$lang_dir = $this->get_path();
		if ( ! $wp_filesystem->is_dir( $lang_dir ) ) {
			$wp_filesystem->mkdir( $lang_dir, FS_CHMOD_DIR );
		}

		GFCommon::log_debug( __METHOD__ . '(): Downloading: ' . $translation['package'] );
		$temp_file = download_url( $translation['package'] );

		if ( is_wp_error( $temp_file ) ) {
			GFCommon::log_error( __METHOD__ . '(): Error downloading package. Code: ' . $temp_file->get_error_code() . '; Message: ' . $temp_file->get_error_message() );

			return;
		}

		$zip_path    = $lang_dir . $this->slug . '-' . $translation['language'] . '.zip';
		$copy_result = $wp_filesystem->copy( $temp_file, $zip_path, true, FS_CHMOD_FILE );
		$wp_filesystem->delete( $temp_file );

		if ( ! $copy_result ) {
			GFCommon::log_error( __METHOD__ . '(): Unable to move package to: ' . $lang_dir );

			return;
		}

		$result = unzip_file( $zip_path, $lang_dir );
		@unlink( $zip_path );

		if ( is_wp_error( $result ) ) {
			GFCommon::log_error( __METHOD__ . '(): Error extracting package. Code: ' . $result->get_error_code() . '; Message: ' . $result->get_error_message() );

			return;
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Installed %s translation for %s.', $translation['language'], $this->slug ) );
		$this->installed[] = $translation['language'];
	}

	/**
	 * Logs which locales WordPress installs translations for.
	 *
	 * @since 2.5.6
	 *
	 * @param object $upgrader_object WP_Upgrader Instance.
	 * @param array  $hook_extra      Item update data.
	 */
	public function upgrader_process_complete( $upgrader_object, $hook_extra ) {
		if ( rgar( $hook_extra, 'type' ) !== 'translation' || empty( $hook_extra['translations'] ) || empty( $upgrader_object->result ) || is_wp_error( $upgrader_object->result ) ) {
			return;
		}

		$locales = array();

		foreach ( $hook_extra['translations'] as $translation ) {
			if ( rgar( $translation, 'type' ) !== 'plugin' || rgar( $translation, 'slug' ) !== $this->slug ) {
				continue;
			}

			$locales[] = $translation['language'];
		}

		if ( empty( $locales ) ) {
			return;
		}

		$this->installed = $locales;
		GFCommon::log_debug( __METHOD__ . sprintf( '(): WordPress installed %s translation(s) for %s.', implode( ', ', $locales ), $this->slug ) );
	}

	/**
	 * Returns an array of locales the site has installed.
	 *
	 * @since 2.5.6
	 *
	 * @return array
	 */
	private function get_available_languages() {
		static $languages = array();

		if ( empty( $languages ) ) {
			$languages = get_available_languages();
		}

		return $languages;
	}

	/**
	 * Returns the header data from the installed translations for the current plugin.
	 *
	 * @since 2.5.6
	 *
	 * @return array
	 */
	private function get_installed_translations_data() {
		static $data = array();

		if ( isset( $data[ $this->slug ] ) ) {
			return $data[ $this->slug ];
		}

		$data[ $this->slug ] = array();
		$translations        = GFCommon::get_installed_translations( $this->slug, true );

		foreach ( $translations as $locale => $mo_file ) {
			$po_file = str_replace( '.mo', '.po', $mo_file );
			if ( ! file_exists( $po_file ) ) {
				continue;
			}
			$data[ $this->slug ][ $locale ] = wp_get_pomo_file_data( $po_file );
		}

		return $data[ $this->slug ];
	}

	/**
	 * Returns the path to where plugin translations are stored.
	 *
	 * @since 2.5.6
	 *
	 * @return string
	 */
	private function get_path() {
		return WP_LANG_DIR . '/plugins/';
	}

	/**
	 * Determines if a translation should be installed.
	 *
	 * @since 2.5.6
	 *
	 * @param array  $translation The translation data.
	 * @param string $locale      The locale when the site locale is changed or an empty string to check all the user available locales.
	 *
	 * @return bool
	 */
	private function should_install( $translation, $locale = '' ) {
		if ( ( $locale && $locale !== $translation['language'] ) || ! in_array( $translation['language'], $this->get_available_languages() ) ) {
			return false;
		}

		if ( empty( $translation['updated'] ) ) {
			return true;
		}

		$installed = $this->get_installed_translations_data();

		if ( ! isset( $installed[ $translation['language'] ] ) ) {
			return true;
		}

		$local  = date_create( $installed[ $translation['language'] ]['PO-Revision-Date'] );
		$remote = date_create( $translation['updated'] );

		return $remote > $local;
	}

}

TranslationsPress_Updater::get_instance( 'gravityforms' );
