<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Items;

use \GFCommon;

final class GF_Form_Product_Item extends GF_Order_Item {

	/**
	 * GF_Form_Product_Item constructor.
	 *
	 * @since 2.6
	 *
	 * @param string|int $id    The product ID
	 * @param array      $data  The product data.
	 */
	public function __construct( $id, $data = array() ) {
		parent::__construct( $id, $data );
	}

	/**
	 * Returns the base price of the item.
	 *
	 * @since 2.6
	 *
	 * @return float
	 */
	public function get_base_price() {
		$this->price = GFCommon::to_number( $this->price, $this->currency );
		return $this->price + $this->get_options_total();
	}

	/**
	 * Calculates and returns the total price of the product options.
	 *
	 * @since 2.6
	 *
	 * @return float
	 */
	private function get_options_total() {
		$options_total = 0;
		if ( is_array( $this->options ) ) {
			foreach ( $this->options as $option ) {
				$option['price'] = GFCommon::to_number( $option['price'], $this->currency );
				$options_total  += $option['price'];
			}
		}

		return $options_total;
	}
}
