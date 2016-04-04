<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Creates new instance of the entity
 */
class CreateEntity implements ProcessorInterface
{
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

        $context->setResult($this->createEntity($context));
    }

    /**
     * @param Context $context
     *
     * @return object
     */
    protected function createEntity(Context $context)
    {
        $entityClass = $context->getClassName();

        return new $entityClass();
    }
}
