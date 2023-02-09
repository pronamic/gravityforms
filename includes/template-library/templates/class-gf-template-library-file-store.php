<?php

namespace Gravity_Forms\Gravity_Forms\Template_Library\Templates;

use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Templates_Store;
use Unirest\Exception;

/**
 * Class GF_Template_Library_File_Store
 *
 * Loads the templates' library data from a JSON file.
 *
 * @package Gravity_Forms\Gravity_Forms\Template_Library;
 */
class GF_Template_Library_File_Store implements GF_Templates_Store {
	/**
	 * The templates.
	 *
	 * @since 2.7
	 *
	 * @var mixed $data The templates.
	 */
	protected $templates;

	/**
	 * The raw data returned from the source.
	 *
	 * @since 2.7
	 *
	 * @var array $raw_data The raw data returned from the data source.
	 */
	protected $raw_data;

	/**
	 * The store configurations array.
	 *
	 * @since 2.7
	 *
	 * @var array $config The store configurations array.
	 */
	protected $config;

	/**
	 * Store constructor.
	 *
	 * @param array $config The store configurations array.
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 * Retrieves raw data and decodes it, returns an array of templates.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function get_templates() {

		if ( is_array( $this->templates ) ) {
			return $this->templates;
		}

		$uri = rgar( $this->config, 'uri' );
		try {
			$this->raw_data = @file_get_contents( $uri );
		} catch ( Exception $e ) {
			return array();
		}

		$this->templates = json_decode( $this->raw_data, true );
		if ( ! is_array( $this->templates ) ) {
			return array();
		}

		return $this->templates;
	}

	/**
	 * Returns a template by its ID.
	 *
	 * @since 2.7
	 *
	 * @param string $id The id of the template.
	 *
	 * @return GF_Template_Library_Template|false
	 */
	public function get( $id ) {
		$template_data = rgar( $this->get_templates(), $id );
		if ( ! $template_data ) {
			return false;
		}

		return new GF_Template_Library_Template( $template_data );
	}

	/**
	 * Returns the all the templates as an array.
	 *
	 * @since 2.7
	 *
	 * @param bool $include_meta whether to include the template form meta or not.
	 *
	 * @return array
	 */
	public function all( $include_meta = false ) {
		if ( $include_meta ) {
			return $this->get_templates();
		}

		$templates_data = array_map(
			function( $template_data ) {
				unset( $template_data['form_meta'] );
				unset( $template_data['version'] );
				return $template_data;
			},
			$this->get_templates()
		);

		return $templates_data;
	}
}
