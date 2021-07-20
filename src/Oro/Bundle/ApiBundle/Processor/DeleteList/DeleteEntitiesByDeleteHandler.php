<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Deletes a list of entities by the delete handler.
 */
class DeleteEntitiesByDeleteHandler implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityDeleteHandlerRegistry */
    private $deleteHandlerRegistry;

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
    public function process(ContextInterface $context)
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
        $this->processDelete(
            $context->getResult(),
            $deleteHandler,
            $this->doctrineHelper->getEntityManagerForClass($entityClass)
        );
        $context->removeResult();
    }

    /**
     * @param mixed                        $data
     * @param EntityDeleteHandlerInterface $handler
     * @param EntityManagerInterface       $em
     */
    private function processDelete(
        $data,
        EntityDeleteHandlerInterface $handler,
        EntityManagerInterface $em
    ): void {
        if (!\is_array($data) && !$data instanceof \Traversable) {
            throw new \RuntimeException(\sprintf(
                'The result property of the context should be array or Traversable, "%s" given.',
                \is_object($data) ? \get_class($data) : \gettype($data)
            ));
        }

        $em->getConnection()->beginTransaction();
        try {
            $flushAllOptions = [];
            foreach ($data as $entity) {
                $flushAllOptions[] = $handler->delete($entity, false);
            }
            $handler->flushAll($flushAllOptions);
            $em->getConnection()->commit();
        } catch (\Throwable $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }
}
