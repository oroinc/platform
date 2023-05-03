<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Deletes an entity by the delete handler.
 */
class DeleteEntityByDeleteHandler implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityDeleteHandlerRegistry $deleteHandlerRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityDeleteHandlerRegistry $deleteHandlerRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasResult()) {
            // result deleted or not supported
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $deleteHandler = $this->deleteHandlerRegistry->getHandler($entityClass);
        $this->processDelete($context->getResult(), $deleteHandler);
        $context->removeResult();
    }

    private function processDelete(mixed $data, EntityDeleteHandlerInterface $handler): void
    {
        if (!\is_object($data)) {
            throw new \RuntimeException(sprintf(
                'The result property of the context should be an object, "%s" given.',
                get_debug_type($data)
            ));
        }

        $handler->delete($data);
    }
}
