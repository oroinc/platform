<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

/**
 * Represents a factory that creates a flush data handler for a batch operation.
 */
interface BatchFlushDataHandlerFactoryInterface
{
    public function createHandler(string $entityClass): ?BatchFlushDataHandlerInterface;
}
