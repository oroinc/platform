<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\DeleteDataByDeleteHandler as BaseProcessor;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * Deletes entity by DeleteHandler.
 */
class DeleteDataByDeleteHandler extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function processDelete(Context $context, DeleteHandler $handler)
    {
        $handler->processDelete(
            $context->getResult(),
            $this->doctrineHelper->getEntityManagerForClass($context->getClassName())
        );
    }
}
