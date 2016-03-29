<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Component\ChainProcessor\ContextInterface;

use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;

/**
 * Deletes entity by DeleteHandler.
 */
class DeleteDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete(ContextInterface $context, DeleteHandler $handler)
    {
        $handler->processDelete(
            $context->getResult(),
            $this->doctrineHelper->getEntityManagerForClass($context->getClassName())
        );
    }
}
