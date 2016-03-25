<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;

/**
 * Deletes objects list by DeleteHandler.
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
