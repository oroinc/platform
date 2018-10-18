<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the entity mapper from the context.
 */
class RemoveEntityMapper implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $context->setEntityMapper(null);
    }
}
