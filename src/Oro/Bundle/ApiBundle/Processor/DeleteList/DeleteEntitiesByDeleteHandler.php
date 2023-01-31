<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes a list of entities by the delete handler.
 */
class DeleteEntitiesByDeleteHandler implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityDeleteHandlerRegistry $deleteHandlerRegistry;
    private LoggerInterface $logger;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityDeleteHandlerRegistry $deleteHandlerRegistry,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
        $this->logger = $logger;
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
        $this->processDelete($context->getResult(), $deleteHandler, $entityClass);
        $context->removeResult();
    }

    private function processDelete(
        mixed $data,
        EntityDeleteHandlerInterface $handler,
        string $entityClass
    ): void {
        if (!\is_array($data) && !$data instanceof \Traversable) {
            throw new \RuntimeException(sprintf(
                'The result property of the context should be array or Traversable, "%s" given.',
                get_debug_type($data)
            ));
        }

        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $flushAllOptions = [];
            foreach ($data as $entity) {
                $flushAllOptions[] = $handler->delete($entity, false);
            }
            $handler->flushAll($flushAllOptions);
            $connection->commit();
        } catch (\Throwable $e) {
            try {
                $connection->rollBack();
            } catch (\Throwable $rollbackException) {
                $this->logger->error(
                    'The database rollback operation failed in delete entities by delete handler API processor.',
                    ['exception' => $rollbackException, 'entityClass' => $entityClass]
                );
            }

            throw $e;
        }
    }
}
