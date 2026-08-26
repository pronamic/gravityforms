<?php

declare(strict_types=1);

namespace Gravity_Forms\Gravity_Forms\WP\McpSchema\Server\Prompts\DTO;

use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\AbstractDataTransferObject;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Factory\ContentBlockFactory;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Traits\ValidatesRequiredFields;

/**
 * Describes a message returned as part of a prompt.
 *
 * This is similar to `SamplingMessage`, but also supports the embedding of
 * resources from the MCP server.
 *
 * @since 2024-11-05
 * @last-updated 2025-11-25 (modified properties: content, role)
 *
 * @mcp-domain Server
 * @mcp-subdomain Prompts
 * @mcp-version 2025-11-25
 */
class PromptMessage extends AbstractDataTransferObject
{
    use ValidatesRequiredFields;

    /**
     * @since 2024-11-05
     *
     * @var 'user'|'assistant'
     */
    protected string $role;

    /**
     * @since 2024-11-05
     *
     * @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface
     */
    protected ContentBlockInterface $content;

    /**
     * @param 'user'|'assistant' $role @since 2024-11-05
     * @param \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface $content @since 2024-11-05
     */
    public function __construct(
        string $role,
        ContentBlockInterface $content
    ) {
        $this->role = $role;
        $this->content = $content;
    }

    /**
     * Creates an instance from an array.
     *
     * @param array{
     *     role: 'user'|'assistant',
     *     content: array<string, mixed>|\Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface
     * } $data
     * @phpstan-param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        self::assertRequired($data, ['role', 'content']);

        /** @var 'user'|'assistant' $role */
        $role = self::asString($data['role']);

        /** @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface $content */
        $content = is_array($data['content'])
            ? ContentBlockFactory::fromArray(self::asArray($data['content']))
            : $data['content'];

        return new self(
            $role,
            $content
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

        $result['role'] = $this->role;
        $result['content'] = $this->content->toArray();

        return $result;
    }

    /**
     * @return 'user'|'assistant'
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Protocol\Union\ContentBlockInterface
     */
    public function getContent(): ContentBlockInterface
    {
        return $this->content;
    }
}
