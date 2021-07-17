<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * The default implementation of the factory that creates a flush data handler for a batch operation.
 */
class BatchFlushDataHandlerFactory implements BatchFlushDataHandlerFactoryInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function createHandler(string $entityClass): ?BatchFlushDataHandlerInterface
    {
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return new BatchFlushDataHandler($entityClass, $this->doctrineHelper);
        }

        return null;
    }
}
