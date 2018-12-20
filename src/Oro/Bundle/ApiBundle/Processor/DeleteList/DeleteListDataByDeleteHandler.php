<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * Deletes a list of entities by DeleteHandler.
 */
class DeleteListDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete($data, DeleteHandler $handler, EntityManagerInterface $em)
    {
        if (!\is_array($data) && !$data instanceof \Traversable) {
            throw new \RuntimeException(\sprintf(
                'The result property of the context should be array or Traversable, "%s" given.',
                \is_object($data) ? \get_class($data) : \gettype($data)
            ));
        }

        $em->getConnection()->beginTransaction();
        try {
            foreach ($data as $entity) {
                $handler->processDelete($entity, $em);
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }
}
