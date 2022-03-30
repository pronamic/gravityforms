<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Multifile Strings
 *
 * @since 2.6
 */
class GF_Config_Multifile extends GF_Config {

	protected $script_to_localize = 'gform_gravityforms';
	protected $name               = 'gform_gravityforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'strings' => array(
				'invalid_file_extension' => wp_strip_all_tags( __( 'This type of file is not allowed. Must be one of the following: ', 'gravityforms' ) ),
				'delete_file'            => wp_strip_all_tags( __( 'Delete this file', 'gravityforms' ) ),
				'in_progress'            => wp_strip_all_tags( __( 'in progress', 'gravityforms' ) ),
				'file_exceeds_limit'     => wp_strip_all_tags( __( 'File exceeds size limit', 'gravityforms' ) ),
				'illegal_extension'      => wp_strip_all_tags( __( 'This type of file is not allowed.', 'gravityforms' ) ),
				'max_reached'            => wp_strip_all_tags( __( 'Maximum number of files reached', 'gravityforms' ) ),
				'unknown_error'          => wp_strip_all_tags( __( 'There was a problem while saving the file on the server', 'gravityforms' ) ),
				'currently_uploading'    => wp_strip_all_tags( __( 'Please wait for the uploading to complete', 'gravityforms' ) ),
				'cancel'                 => wp_strip_all_tags( __( 'Cancel', 'gravityforms' ) ),
				'cancel_upload'          => wp_strip_all_tags( __( 'Cancel this upload', 'gravityforms' ) ),
				'cancelled'              => wp_strip_all_tags( __( 'Cancelled', 'gravityforms' ) )
			),
			'vars'    => array(
				'images_url' => \GFCommon::get_base_url() . '/images'
			)
		);
	}

}