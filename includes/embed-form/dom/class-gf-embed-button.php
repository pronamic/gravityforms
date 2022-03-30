<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Dom;

/**
 * Handle outputting the Embed Button in the UI.
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Embed_Form\Dom
 */
class GF_Embed_Button {

	/**
	 * Output the HTML for the Embed Button.
	 */
	public function output_button() {
		?>
		<button data-js="embed-flyout-trigger" class="gform-button gform-button--white gform-button--icon-leading">
			<i class="gform-button__icon gform-icon gform-icon--embed-alt"></i>
			<?php _e( 'Embed', 'gravityforms' ); ?>
		</button>
		<?php
	}

}
