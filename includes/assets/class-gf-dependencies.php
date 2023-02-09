<?php

namespace Gravity_Forms\Gravity_Forms\Assets;

abstract class GF_Dependencies {

	/**
	 * Items to enqueue
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * The method for actually enqueueing the items.
	 *
	 * @since 2.6
	 *
	 * @param $handle
	 *
	 * @return mixed
	 */
	abstract protected function do_enqueue( $handle );

	/**
	 * Get the dependency items.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Enqueue the items.
	 *
	 * @since 2.6
	 *
	 * @param $items
	 */
	public function enqueue() {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		foreach ( $this->items as $handle ) {
			$this->do_enqueue( $handle );
		}
	}

	/**
	 * Override to determine whether the assets outlined should be enqueued.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	protected function should_enqueue() {
		return true;
	}

}