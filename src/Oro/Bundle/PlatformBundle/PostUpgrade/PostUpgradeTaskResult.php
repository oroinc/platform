<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\PostUpgrade;

/**
 * Result of post-upgrade task execution
 */
class PostUpgradeTaskResult
{
    public function __construct(
        private readonly string $taskName,
        private readonly bool $executed,
        private readonly ?int $scheduledCount = null,
        private readonly ?string $message = null
    ) {
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function getScheduledCount(): ?int
    {
        return $this->scheduledCount;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
