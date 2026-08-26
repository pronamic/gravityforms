<?php

/**
 * Determines whether the current WordPress version supports the Abilities API.
 *
 * @since 3.1.0
 */
class GF_Abilities_Compatibility {

	/**
	 * Determine if the Abilities API is supported.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return GF_SUPPORTS_ABILITIES_API;
	}
}
