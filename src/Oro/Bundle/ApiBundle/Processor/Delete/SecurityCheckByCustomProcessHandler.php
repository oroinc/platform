<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Component\ChainProcessor\ContextInterface;

class SecurityCheckByCustomProcessHandler extends SecurityCheckByProcessHandler
{
    /** @var string */
    protected $className;

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

        $object = $context->getObject();
        if (!is_a($object, $this->className)) {
            // given object does not supports
            return;
        }

        parent::process($context);
    }
}
