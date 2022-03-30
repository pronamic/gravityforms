<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Exporters;

use \Gravity_Forms\Gravity_Forms\Orders\GF_Order;
use \Gravity_Forms\Gravity_Forms\Orders\Items\GF_Order_Item;
use \Gravity_Forms\Gravity_Forms\Orders\Items\GF_Form_Product_Item;

use \GFCommon;

class GF_Save_Entry_Order_Exporter extends GF_Order_Exporter {

	/**
	 * GF_Default_Order_Formatter constructor.
	 *
	 * @param GF_Order $order  The order to be formatted.
	 * @param array    $config Any specific configurations required while formatting the order.
	 */
	public function __construct( $order, $config = array() ) {
		parent::__construct( $order, $config );
	}

	/**
	 * Extracts a set of raw data from the order.
	 *
	 * @since 2.6
	 */
	protected function format() {

		foreach ( $this->order->get_items() as $item ) {

			if ( ! isset( $this->data['rows'][ $item->belongs_to ] ) ) {
				$this->data['rows'][ $item->belongs_to ] = array();
			}

			if ( $item->is_line_item ) {
				// If form product item, we don't need to store pricing info, name and options as they are already stored.
				$exclude_properties                        = is_a( $item, GF_Form_Product_Item::class ) ? array( 'name', 'price', 'quantity', 'sub_total', 'options' ) : array();
				$this->data['rows'][ $item->belongs_to ][] = $this->filter_item_data( $item, $exclude_properties );
			}
		}

		// No need to save totals now as they will be calculated later.
		// In the future when we have dynamically calculated totals we may save them.
		unset( $this->data['totals'] );
		// Versioning will help identify this when it changes later, which is very likely.
		$this->data['v'] = '0.1';
	}

}
