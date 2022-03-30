<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Items;

use \GFCommon;

class GF_Order_Item {

	/**
	 * The item ID.
	 *
	 * @since 2.6
	 *
	 * @var string|int
	 */
	private $id;

	/**
	 * A collection of item properties.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	protected $data;


	/**
	 * Returns the default item properties.
	 *
	 * No properties can be set other than these ones.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	protected final function get_default_properties() {
		return array(
			'is_discount'  => false,
			'is_shipping'  => false,
			'is_trial'     => false,
			'is_setup'     => false,
			'is_line_item' => false,
			'is_recurring' => false,
			'belongs_to'   => 'body',
			'price'        => 0,
			'quantity'     => 1,
			'sub_total'    => 0,
			'currency'     => GFCommon::get_currency(),
			'name'         => '',
			'description'  => '',
			'options'      => array(),
			'type'         => '',
		);
	}

	/**
	 * GF_Order_Item constructor.
	 *
	 * @since 2.6
	 *
	 * @param string|int $id    The item ID.
	 * @param array      $data  The item data.
	 */
	public function __construct( $id, $data = array() ) {

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$this->id = $id;

		if ( ! isset( $data['type'] ) ) {
			$data['type'] = static::class;
		}

		$this->data = array_intersect_key( $data, $this->get_default_properties() );
	}

	/**
	 * Returns the item ID.
	 *
	 * @since 2.6
	 *
	 * @return int|string The item ID
	 */
	public function get_id() {
		return $this->id;
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
		return $this->price;
	}

	/**
	 * Calculates the item final total.
	 *
	 * @since 2.6
	 *
	 * @return float|int The item final total.
	 */
	public function get_total() {
		$this->quantity  = GFCommon::to_number( $this->quantity, $this->currency );
		$this->sub_total = $this->get_base_price() * $this->quantity;
		return $this->sub_total;
	}


	/**
	 * Overrides the item properties with a new set of properties.
	 *
	 * @since 2.6
	 *
	 * @param array $data   The new data.
	 * @param array $except A group of keys to be skipped while overriding.
	 */
	public final function override_properties( $data, $except = array() ) {
		$except     = array_merge( array( 'id', 'currency' ), $except );
		$data       = array_filter(
			$data,
			function ( $value, $key ) use ( $except ) {
				if ( in_array( $key, $except ) ) {
					return false;
				}
				return true;
			},
			1
		);
		$this->data = array_merge( $this->data, $data );
	}

	/**
	 * Returns the item properties as an array.
	 *
	 * @since 2.6
	 *
	 * @return array The item properties
	 */
	public final function to_array() {

		$this->get_total();
		$data = array();

		foreach ( array_keys( $this->get_default_properties() ) as $key ) {
			$data[ $key ] = $this->__get( $key );
		}

		$data['id'] = $this->get_id();

		return $data;
	}

	/**
	 * Returns a property from the item's data.
	 *
	 * @since 2.6
	 *
	 * @param string $key The property name to look for.
	 *
	 * @return mixed|null The property value or null if nothing found.
	 */
	public final function __get( $key ) {

		if ( ! array_key_exists( $key, $this->get_default_properties() ) ) {
			return null;
		}

		return rgar( $this->data, $key, rgar( $this->get_default_properties(), $key ) );
	}

	/**
	 * Sets the value of a property in the item's data.
	 *
	 * @since 2.6
	 *
	 * @param string $key   The property name to look for.
	 * @param mixed  $value The property value.
	 */
	public final function __set( $key, $value ) {
		if ( array_key_exists( $key, $this->get_default_properties() ) ) {
			$this->data[ $key ] = $value;
		}
	}
}
