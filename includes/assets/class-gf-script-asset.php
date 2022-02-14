<?php
/**
 * Gravity Forms Script Asset
 *
 * @since 2.5
 * @package gravityforms
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Script_Asset
 */
class GF_Script_Asset extends GF_Asset {

	/**
	 * The data for any localized information.
	 *
	 * @since 2.5
	 *
	 * @var array $localize_data
	 */
	private $localize_data = array();

	/**
	 * Localize data to this script.
	 *
	 * @since 2.5
	 *
	 * @param string $object_name
	 * @param array $data
	 *
	 * @return void
	 */
	public function add_localize_data( $object_name, $data ) {
		$this->localize_data[ $object_name ] = $data;
	}

	/**
	 * Enqueue the asset.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function enqueue_asset() {
		wp_enqueue_script( $this->handle, $this->url );

		if ( empty( $this->localize_data ) ) {
			return;
		}

		foreach ( $this->localize_data as $object_name => $data ) {
			wp_localize_script( $this->handle, $object_name, $data );
		}
	}

	/**
	 * Print the asset.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function print_asset() {
		$this->enqueue_asset();

		wp_print_scripts( $this->handle );
	}
}