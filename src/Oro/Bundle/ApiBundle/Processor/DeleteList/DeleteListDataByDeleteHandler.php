<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * Deletes entities by DeleteHandler.
 */
class DeleteListDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete(Context $context, DeleteHandler $handler)
    {
        /** @var DeleteListContext $context */

        $entities = $context->getResult();
        if (!is_array($entities) && !$entities instanceof \Traversable) {
            throw new RuntimeException(
                sprintf(
                    'The result property of the Context should be array or Traversable, "%s" given.',
                    is_object($entities) ? get_class($entities) : gettype($entities)
                )
            );
        }

        $entityManager = $this->doctrineHelper->getEntityManagerForClass($context->getClassName());
        $entityManager->getConnection()->beginTransaction();
        try {
            foreach ($entities as $entity) {
                $handler->processDelete($entity, $entityManager);
            }
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();

            throw $e;
        }
    }
}
