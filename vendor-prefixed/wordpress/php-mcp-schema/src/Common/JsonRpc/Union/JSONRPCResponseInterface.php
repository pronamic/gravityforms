<?php

declare(strict_types=1);

namespace Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\Union;

use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\Union\JSONRPCMessageInterface;

/**
 * A response to a request, containing either the result or error.
 *
 * Union type members:
 * - JSONRPCResultResponse
 * - JSONRPCErrorResponse
 *
 * @mcp-domain Common
 * @mcp-subdomain JsonRpc
 * @mcp-version 2025-11-25
 */
interface JSONRPCResponseInterface extends JSONRPCMessageInterface
{
}
