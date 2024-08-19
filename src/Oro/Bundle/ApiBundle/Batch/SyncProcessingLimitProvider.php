<?php

namespace Oro\Bundle\ApiBundle\Batch;

/**
 * Provides a way to get limitations for batch operations that should be processed in the synchronous mode.
 */
class SyncProcessingLimitProvider
{
    private int $defaultLimit;
    /** @var int[] [entity class => limit, ...] */
    private array $entityLimits;
    private int $defaultIncludedDataLimit;
    /** @var int[] [entity class => limit, ...] */
    private array $entityIncludedDataLimits;

    public function __construct(
        int $defaultLimit,
        array $entityLimits,
        int $defaultIncludedDataLimit,
        array $entityIncludedDataLimits
    ) {
        $this->defaultLimit = $defaultLimit;
        $this->entityLimits = $entityLimits;
        $this->defaultIncludedDataLimit = $defaultIncludedDataLimit;
        $this->entityIncludedDataLimits = $entityIncludedDataLimits;
    }

    /**
     * Gets the maximum number of objects that can be processed by a batch operation in the synchronous mode.
     */
    public function getLimit(string $entityClass): int
    {
        return $this->entityLimits[$entityClass] ?? $this->defaultLimit;
    }

    /**
     * Gets the maximum number of included objects that can be processed by a batch operation in the synchronous mode.
     */
    public function getIncludedDataLimit(string $entityClass): int
    {
        return $this->entityIncludedDataLimits[$entityClass] ?? $this->defaultIncludedDataLimit;
    }
}
