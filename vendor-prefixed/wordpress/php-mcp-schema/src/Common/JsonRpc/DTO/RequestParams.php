<?php

declare(strict_types=1);

namespace Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO;

use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\AbstractDataTransferObject;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Traits\ValidatesRequiredFields;

/**
 * Common params for any request.
 *
 * @since 2025-11-25
 *
 * @mcp-domain Common
 * @mcp-subdomain JsonRpc
 * @mcp-version 2025-11-25
 */
class RequestParams extends AbstractDataTransferObject
{
    use ValidatesRequiredFields;

    /**
     * See [General fields: `_meta`](/specification/2025-11-25/basic/index#meta) for notes on `_meta` usage.
     *
     * @since 2025-11-25
     *
     * @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null
     */
    protected ?RequestParamsMeta $_meta;

    /**
     * @param \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null $_meta @since 2025-11-25
     */
    public function __construct(
        ?RequestParamsMeta $_meta = null
    ) {
        $this->_meta = $_meta;
    }

    /**
     * Creates an instance from an array.
     *
     * @param array{
     *     _meta?: array<string, mixed>|\Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null
     * } $data
     * @phpstan-param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        /** @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null $_meta */
        $_meta = isset($data['_meta'])
            ? (is_array($data['_meta'])
                ? RequestParamsMeta::fromArray(self::asArray($data['_meta']))
                : $data['_meta'])
            : null;

        return new self(
            $_meta
        );
    }

    /**
     * Converts the instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->_meta !== null) {
            $result['_meta'] = $this->_meta->toArray();
        }

        return $result;
    }

    /**
     * @return \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null
     */
    public function get_meta(): ?RequestParamsMeta
    {
        return $this->_meta;
    }
}
