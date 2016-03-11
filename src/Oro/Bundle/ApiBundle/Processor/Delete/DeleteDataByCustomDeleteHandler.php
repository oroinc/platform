<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Deletes object by custom DeleteProcessHandler.
 */
class DeleteDataByCustomDeleteHandler extends DeleteDataByDeleteHandler
{
    /** @var string */
    protected $className;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param DeleteHandler  $deleteHandler
     * @param string         $className
     */
    public function __construct(DoctrineHelper $doctrineHelper, DeleteHandler $deleteHandler, $className)
    {
        $this->className = $className;
        parent::__construct($doctrineHelper, $deleteHandler);
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
        if (!is_a($object, $this->className)) {
            // given object does not supports
            return;
        }

        parent::process($context);
    }
}
