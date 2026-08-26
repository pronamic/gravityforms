<?php
/* HEADER */ // phpcs:ignore

/**
 * This class manages the files and dependencies of the autoloader.
 */
class Gravity_Forms_Gravity_Forms_Container {

	/**
	 * Since each autoloader's class files exist within their own namespace we need a map to
	 * convert between the local class and a shared key. Note that no version checking is
	 * performed on these dependencies and the first autoloader to register will be the
	 * one that is utilized.
	 */
	const SHARED_DEPENDENCY_KEYS = array(
		Gravity_Forms_Gravity_Forms_Hook_Manager::class => 'Gravity_Forms_Gravity_Forms_Hook_Manager',
	);

	/**
	 * A map of all the dependencies we've registered with the container and created.
	 *
	 * @var array
	 */
	protected $dependencies;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->dependencies = array();

		$this->register_shared_dependencies();
		$this->register_dependencies();
		$this->initialize_globals();
	}

	/**
	 * Gets a dependency out of the container.
	 *
	 * @param string $class The class to fetch.
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException When a class that isn't registered with the container is fetched.
	 */
	public function get( $class ) {
		if ( ! isset( $this->dependencies[ $class ] ) ) {
			throw new \InvalidArgumentException( "Class '$class' is not registered with the container." );
		}

		return $this->dependencies[ $class ];
	}

	/**
	 * Registers all of the dependencies that are shared between all instances of the autoloader.
	 */
	private function register_shared_dependencies() {
		global $jetpack_autoloader_container_shared;
		if ( ! isset( $jetpack_autoloader_container_shared ) ) {
			$jetpack_autoloader_container_shared = array();
		}

		$key = self::SHARED_DEPENDENCY_KEYS[ Gravity_Forms_Gravity_Forms_Hook_Manager::class ];
		if ( ! isset( $jetpack_autoloader_container_shared[ $key ] ) ) {
			require_once __DIR__ . '/class-hook-manager.php';
			$jetpack_autoloader_container_shared[ $key ] = new Gravity_Forms_Gravity_Forms_Hook_Manager();
		}
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Hook_Manager::class ] = &$jetpack_autoloader_container_shared[ $key ];
	}

	/**
	 * Registers all of the dependencies with the container.
	 */
	private function register_dependencies() {
		require_once __DIR__ . '/class-path-processor.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Path_Processor::class ] = new Gravity_Forms_Gravity_Forms_Path_Processor();

		require_once __DIR__ . '/class-plugin-locator.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Plugin_Locator::class ] = new Gravity_Forms_Gravity_Forms_Plugin_Locator(
			$this->get( Gravity_Forms_Gravity_Forms_Path_Processor::class )
		);

		require_once __DIR__ . '/class-version-selector.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Version_Selector::class ] = new Gravity_Forms_Gravity_Forms_Version_Selector();

		require_once __DIR__ . '/class-autoloader-locator.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Autoloader_Locator::class ] = new Gravity_Forms_Gravity_Forms_Autoloader_Locator(
			$this->get( Gravity_Forms_Gravity_Forms_Version_Selector::class )
		);

		require_once __DIR__ . '/class-php-autoloader.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_PHP_Autoloader::class ] = new Gravity_Forms_Gravity_Forms_PHP_Autoloader();

		require_once __DIR__ . '/class-manifest-reader.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Manifest_Reader::class ] = new Gravity_Forms_Gravity_Forms_Manifest_Reader(
			$this->get( Gravity_Forms_Gravity_Forms_Version_Selector::class )
		);

		require_once __DIR__ . '/class-plugins-handler.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Plugins_Handler::class ] = new Gravity_Forms_Gravity_Forms_Plugins_Handler(
			$this->get( Gravity_Forms_Gravity_Forms_Plugin_Locator::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Path_Processor::class )
		);

		require_once __DIR__ . '/class-autoloader-handler.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Autoloader_Handler::class ] = new Gravity_Forms_Gravity_Forms_Autoloader_Handler(
			$this->get( Gravity_Forms_Gravity_Forms_PHP_Autoloader::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Hook_Manager::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Manifest_Reader::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Version_Selector::class )
		);

		require_once __DIR__ . '/class-latest-autoloader-guard.php';
		$this->dependencies[ Gravity_Forms_Gravity_Forms_Latest_Autoloader_Guard::class ] = new Gravity_Forms_Gravity_Forms_Latest_Autoloader_Guard(
			$this->get( Gravity_Forms_Gravity_Forms_Plugins_Handler::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Autoloader_Handler::class ),
			$this->get( Gravity_Forms_Gravity_Forms_Autoloader_Locator::class )
		);

		// Register any classes that we will use elsewhere.
		require_once __DIR__ . '/class-version-loader.php';
		require_once __DIR__ . '/class-shutdown-handler.php';
	}

	/**
	 * Initializes any of the globals needed by the autoloader.
	 */
	private function initialize_globals() {
		/*
		 * This global was retired in version 2.9. The value is set to 'false' to maintain
		 * compatibility with older versions of the autoloader.
		 */
		global $jetpack_autoloader_including_latest;
		$jetpack_autoloader_including_latest = false;

		// Not all plugins can be found using the locator. In cases where a plugin loads the autoloader
		// but was not discoverable, we will record them in this array to track them as "active".
		global $jetpack_autoloader_activating_plugins_paths;
		if ( ! isset( $jetpack_autoloader_activating_plugins_paths ) ) {
			$jetpack_autoloader_activating_plugins_paths = array();
		}
	}
}
