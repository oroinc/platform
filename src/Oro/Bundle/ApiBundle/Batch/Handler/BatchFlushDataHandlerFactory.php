<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * The default implementation of the factory that creates a flush data handler for a batch operation.
 */
class BatchFlushDataHandlerFactory implements BatchFlushDataHandlerFactoryInterface
{
    private DoctrineHelper $doctrineHelper;
    private FlushDataHandlerInterface $flushDataHandler;

    public function __construct(DoctrineHelper $doctrineHelper, FlushDataHandlerInterface $flushDataHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->flushDataHandler = $flushDataHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function createHandler(string $entityClass): ?BatchFlushDataHandlerInterface
    {
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return new BatchFlushDataHandler($entityClass, $this->doctrineHelper, $this->flushDataHandler);
        }

        return null;
    }
}
