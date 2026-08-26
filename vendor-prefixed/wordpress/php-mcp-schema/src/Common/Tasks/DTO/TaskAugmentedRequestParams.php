<?php

declare(strict_types=1);

namespace Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Tasks\DTO;

use Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParams;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta;
use Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\Traits\ValidatesRequiredFields;

/**
 * Common params for any task-augmented request.
 *
 * @since 2025-11-25
 *
 * @mcp-domain Common
 * @mcp-subdomain Tasks
 * @mcp-version 2025-11-25
 */
class TaskAugmentedRequestParams extends RequestParams
{
    use ValidatesRequiredFields;

    /**
     * If specified, the caller is requesting task-augmented execution for this request.
     * The request will return a CreateTaskResult immediately, and the actual result can be
     * retrieved later via tasks/result.
     *
     * Task augmentation is subject to capability negotiation - receivers MUST declare support
     * for task augmentation of specific request types in their capabilities.
     *
     * @since 2025-11-25
     *
     * @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata|null
     */
    protected ?TaskMetadata $task;

    /**
     * @param \Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null $_meta @since 2025-11-25
     * @param \Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata|null $task @since 2025-11-25
     */
    public function __construct(
        ?RequestParamsMeta $_meta = null,
        ?TaskMetadata $task = null
    ) {
        parent::__construct($_meta);
        $this->task = $task;
    }

    /**
     * Creates an instance from an array.
     *
     * @param array{
     *     _meta?: array<string, mixed>|\Gravity_Forms\Gravity_Forms\WP\McpSchema\Common\JsonRpc\DTO\RequestParamsMeta|null,
     *     task?: array<string, mixed>|\Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata|null
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

        /** @var \Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata|null $task */
        $task = isset($data['task'])
            ? (is_array($data['task'])
                ? TaskMetadata::fromArray(self::asArray($data['task']))
                : $data['task'])
            : null;

        return new self(
            $_meta,
            $task
        );
    }

    /**
     * Converts the instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = parent::toArray();

        if ($this->task !== null) {
            $result['task'] = $this->task->toArray();
        }

        return $result;
    }

    /**
     * @return \Gravity_Forms\Gravity_Forms\WP\McpSchema\Client\Tasks\DTO\TaskMetadata|null
     */
    public function getTask(): ?TaskMetadata
    {
        return $this->task;
    }
}
