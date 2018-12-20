<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * Deletes an entity by DeleteHandler.
 */
class DeleteDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete($data, DeleteHandler $handler, EntityManagerInterface $em)
    {
        if (!\is_object($data)) {
            throw new \RuntimeException(\sprintf(
                'The result property of the context should be an object, "%s" given.',
                \is_object($data) ? \get_class($data) : \gettype($data)
            ));
        }

        $handler->processDelete($data, $em);
    }
}
