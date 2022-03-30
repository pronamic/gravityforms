<?php

namespace Gravity_Forms\Gravity_Forms\Orders;

use  Gravity_Forms\Gravity_Forms\Orders\Items\GF_Order_Item;
use  Gravity_Forms\Gravity_Forms\Orders\Items\GF_Form_Product_Item;

final class GF_Order {

	/**
	 * The order items.
	 *
	 * Contains the items grouped by item type.
	 *
	 * @since 2.6
	 *
	 * @var GF_Order_Item[]
	 */
	private $items = array();

	/**
	 * Contains all the calculated totals for the order, like sub total, discounts, and final total.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	private $totals = array();

	/**
	 * The order items groups.
	 *
	 * Contains the items grouped by their location in the final order summary.
	 *
	 * @since 2.6
	 *
	 * @var array[]
	 */
	private $groups = array(
		'body'   => array(),
		'footer' => array(),
	);

	/**
	 * The order currency.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	public $currency;


	/**
	 * Adds a group of items to the order.
	 *
	 * @since 2.6
	 *
	 * @param GF_Order_Item[] $items
	 */
	public function add_items( $items = array() ) {
		foreach ( $items as $item ) {
			$this->add_item( $item );
		}
	}

	/**
	 * Adds a single item to the order.
	 *
	 * @since 2.6
	 *
	 * @param GF_Order_Item $item
	 *
	 * @return bool
	 */
	public function add_item( $item ) {

		if ( ! is_a( $item, 'Gravity_Forms\Gravity_Forms\Orders\Items\GF_Order_Item' ) ) {
			return false;
		}

		$item->currency = $this->currency;

		if ( ! isset( $this->items[ $item->type ] ) ) {
			$this->items[ $item->type ] = array();
		}

		if ( isset( $this->items[ $item->type ][ $item->get_id() ] ) ) {
			return false;
		}

		$this->items[ $item->type ][ $item->get_id() ] = $item;

		if ( ! isset( $this->groups[ $item->belongs_to ] ) ) {
			$this->groups[ $item->belongs_to ] = array();
		}

		$this->groups[ $item->belongs_to ][ $item->get_id() ] = $item;

		return true;

	}


	/**
	 * Returns a collection of items by item type or all items.
	 *
	 * @since 2.6
	 *
	 * @param null|string $type The item type to look for.
	 *
	 * @return array|GF_Order_Item An empty array if no items found or the items.
	 */
	public function get_items( $type = null ) {
		if ( $type ) {
			return $this->items[ $type ];
		} else {
			$all_items = array();
			foreach ( $this->items as $items ) {
				foreach ( $items as $item ) {
					$all_items[ $item->get_id() ] = $item;
				}
			}
			return $all_items;
		}

		return array();
	}

	/**
	 * Returns a collection of items by item class type.
	 *
	 * Item types can be represented by a string, for example a collection of GF_Form_Product_Item added to a type called "recurring".
	 *
	 * @since 2.6
	 *
	 * @param string $type The item class to look for.
	 *
	 * @return array|GF_Order_Item An empty array if no items found or the items.
	 */
	public function get_items_by_class_type( $type ) {
		$items    = $this->get_items();
		$filtered = array();
		foreach ( $items as $item ) {
			if ( is_a( $item, $type ) ) {
				$filtered[ $item->get_id() ] = $item;
			}
		}

		return $filtered;
	}

	/**
	 * Returns a collection of items excluding a certain class type.
	 *
	 * @since 2.6
	 *
	 * @param string $type The item class to look for.
	 *
	 * @return array|GF_Order_Item An empty array if no items found or the items.
	 */
	public function get_items_exclude_class_type( $type ) {
		$items    = $this->get_items();
		$filtered = $this->get_items_by_class_type( $type );
		return array_diff_key( $items, $filtered );
	}

	/**
	 * Deletes an item from the order.
	 *
	 * @since 2.6
	 *
	 * @param string $id The item ID.
	 *
	 * @return bool
	 */
	public function delete_item( $id ) {

		$item = $this->get_item( $id );
		if ( $item ) {
			unset( $this->items[ $item->type ][ $id ] );
			unset( $this->groups[ $item->belongs_to ][ $id ] );
			$this->totals = array();
			return true;
		}

		return false;
	}

	/**
	 * Gets an item from the order.
	 *
	 * @since 2.6
	 *
	 * @param string $id The item ID.
	 *
	 * @return false|GF_Order_Item The found item or false.
	 */
	public function get_item( $id ) {
		return $this->loop_items_return(
			function ( $item ) use ( $id ) {
				return $id == $item->get_id();
			}
		);
	}

	/**
	 * Searches for an item by a property and its values and returns the first found item.
	 *
	 * @since 2.6
	 *
	 * @param string $property The property name to look for.
	 * @param string $value    The property value to look for.
	 *
	 * @return false|GF_Order_Item false if no items are found or the order item.
	 */
	public function get_item_by_property( $property, $value ) {
		return $this->loop_items_return(
			function( $item ) use ( $property, $value ) {
				return $item->{$property} == $value;
			}
		);

	}

	/**
	 * Loops over the order item and returns one if it matches the provided callback.
	 *
	 * @since 2.6
	 *
	 * @param callable $callback The callback to use to evaluate the item.
	 *
	 * @return false|GF_Order_Item false if no items are found or the order item.
	 */
	protected function loop_items_return( $callback ) {
		foreach ( $this->items as $item_type => $items ) {
			foreach ( $items as $item_id => $item ) {
				if ( $callback( $item ) ) {
					return $item;
				}
			}
		}

		return false;
	}

	/**
	 * Returns the order items in a certain group.
	 *
	 * @since 2.6
	 *
	 * @param string $group The group to look for.
	 *
	 * @return array|GF_Order_Item An empty array if no items found or the items.
	 */
	public function get_group( $group ) {
		return rgar( $this->groups, $group, array() );
	}

	/**
	 * Returns the order groups.
	 *
	 * @since 2.6
	 *
	 * @return array[]
	 */
	public function get_groups() {
		return $this->groups;
	}


	/**
	 * Calculates the order totals.
	 *
	 * @since 2.6
	 *
	 * @return array an assoc array that contains each total label and its value.
	 */
	public function get_totals() {

		if ( empty( $this->totals ) ) {

			// Sub total is all items in the summary body.
			$this->totals['sub_total'] = array_sum(
				array_map(
					function ( $item ) {
						return $item->get_total();
					},
					$this->groups['body']
				)
			);

			// Final total is the sub total plus everything in footer.
			$this->totals['total'] = $this->totals['sub_total'] + array_sum(
				array_map(
					function ( $item ) {
						return $item->get_total();
					},
					$this->groups['footer']
				)
			);

			// Later on there will be dynamic totals, which will be calculated depending on certain conditions.
		}

		// Make sure we don't have negative totals.
		foreach ( $this->totals as $label => $total_value ) {
			$this->totals[ $label ] = max( $total_value, 0 );
		}

		return $this->totals;
	}
}
