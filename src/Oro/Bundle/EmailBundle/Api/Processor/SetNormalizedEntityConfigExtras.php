<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Expands email users relationship for a created email.
 */
class SetNormalizedEntityConfigExtras implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $context->setNormalizedEntityConfigExtras([
            new ExpandRelatedEntitiesConfigExtra(['emailUsers'])
        ]);
    }
}
