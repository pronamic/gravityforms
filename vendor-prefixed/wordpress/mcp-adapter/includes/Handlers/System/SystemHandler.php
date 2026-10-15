<?php
/**
 * System method handlers for MCP requests.
 *
 * @package McpAdapter
 */

declare( strict_types=1 );

namespace Gravity_Forms\Gravity_Forms\WP\MCP\Handlers\System;

use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\AbstractDataTransferObject;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\DTO\Result;

/**
 * Handles system-related MCP methods.
 */
class SystemHandler {
	/**
	 * Handles the ping request.
	 *
	 * @return \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\AbstractDataTransferObject Empty result DTO per MCP specification.
	 */
	public function ping(): AbstractDataTransferObject {
		return Result::fromArray( array() );
	}
}
