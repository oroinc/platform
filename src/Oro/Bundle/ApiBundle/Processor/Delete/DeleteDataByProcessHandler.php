<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Deletes object by DeleteProcessHandler.
 */
class DeleteDataByProcessHandler implements ProcessorInterface
{
    /** @var DeleteHandler */
    protected $deleteHandler;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DeleteHandler $deleteHandler
     */
    public function __construct(DoctrineHelper $doctrineHelper, DeleteHandler $deleteHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteContext $context */

        if (!$context->hasResult()) {
            // entity already deleted
            return;
        }

        $object = $context->getResult();

        if (!is_object($object)) {
            // entity already deleted or not supported
            return;
        }

        $this->deleteHandler->processDelete($object, $this->doctrineHelper->getEntityManager($object));
        $context->removeResult();
    }
}
