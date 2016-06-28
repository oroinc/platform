<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;

/**
 * Deletes entities by DeleteHandler.
 */
class DeleteListDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete(ContextInterface $context, DeleteHandler $handler)
    {
        /** @var DeleteListContext $context */

        $entityList = $context->getResult();
        if (!is_array($entityList) && !$entityList instanceof \Traversable) {
            throw new RuntimeException(
                sprintf(
                    'The result property of the Context should be array or Traversable, "%s" given.',
                    is_object($entityList) ? get_class($entityList) : gettype($entityList)
                )
            );
        }

        $entityManager = $this->doctrineHelper->getEntityManagerForClass($context->getClassName());
        $entityManager->getConnection()->beginTransaction();
        try {
            foreach ($entityList as $entity) {
                $handler->processDelete($entity, $entityManager);
            }
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();

            throw $e;
        }
    }
}
