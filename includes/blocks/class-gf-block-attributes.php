<?php

namespace Gravity_Forms\Gravity_Forms\Blocks;

/**
 * Gravity Forms Block Attributes class.
 *
 * @since 2.7.4
 *
 * Class GF_Block_Attributes
 */
class GF_Block_Attributes {

	public function store( $attributes ) {
		add_filter( 'gform_form_block_attribute_values', function( $attr ) use ( $attributes ) {
			$form_id = rgar( $attributes, 'formId', 0 );

			if ( ! array_key_exists( $form_id, $attr ) ) {
				$attr[ $form_id ] = array();
			}

			$attr[ $form_id ][] = $attributes;
			return $attr;
		} );
	}


}
