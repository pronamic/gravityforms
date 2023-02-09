<?php

namespace Gravity_Forms\Gravity_Forms\Template_Library\Templates;

use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Template_Library_Template;

interface GF_Templates_Store {
	/**
	 * Retrieves raw data and store it in memory, returns it if it already exists.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function get_templates();

	/**
	 * Return a template by its ID.
	 *
	 * @since 2.7
	 *
	 * @param string $id The id of the template.
	 *
	 * @return GF_Template_Library_Template
	 */
	public function get( $id );

	/**
	 * Returns all the templates as an array.
	 *
	 * @since 2.7
	 *
	 * @param bool $include_meta whether to include the template form meta or not.
	 *
	 * @return array
	 */
	public function all( $include_meta );
}
