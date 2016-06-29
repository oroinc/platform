<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

/**
 * Creates new instance of the entity
 */
class CreateEntity implements ProcessorInterface
{
    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /**
     * @param EntityInstantiator $entityInstantiator
     */
    public function __construct(EntityInstantiator $entityInstantiator)
    {
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // the entity already exists
            return;
        }

        $context->setResult($this->entityInstantiator->instantiate($context->getClassName()));
    }
}
