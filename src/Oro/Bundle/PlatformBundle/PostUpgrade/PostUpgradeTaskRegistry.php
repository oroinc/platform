<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\PostUpgrade;

/**
 * Registry of all post-upgrade tasks
 */
class PostUpgradeTaskRegistry
{
    /**
     * @var array<string,PostUpgradeTaskInterface>
     */
    private array $tasks = [];

    /**
     * @param iterable<PostUpgradeTaskInterface> $tasks
     */
    public function __construct(iterable $tasks)
    {
        foreach ($tasks as $task) {
            $this->tasks[$task->getName()] = $task;
        }
    }

    /**
     * @return PostUpgradeTaskInterface[]
     */
    public function getAllTasks(): array
    {
        return array_values($this->tasks);
    }

    public function getTaskByName(string $name): ?PostUpgradeTaskInterface
    {
        return $this->tasks[$name] ?? null;
    }

    public function hasTask(string $name): bool
    {
        return isset($this->tasks[$name]);
    }
}
