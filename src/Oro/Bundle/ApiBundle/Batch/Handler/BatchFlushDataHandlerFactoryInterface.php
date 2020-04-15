<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

/**
 * Represents a factory that creates a flush data handler for a batch operation.
 */
interface BatchFlushDataHandlerFactoryInterface
{
    /**
     * @param string $entityClass
     *
     * @return BatchFlushDataHandlerInterface|null
     */
    public function createHandler(string $entityClass): ?BatchFlushDataHandlerInterface;
}
