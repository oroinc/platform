<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Sets the maximum number of related entities that can be retrieved.
 */
class SetDefaultMaxRelatedEntities implements ProcessorInterface
{
    const DEFAULT_MAX_RELATED_ENTITIES = 100;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasConfigExtra(MaxRelatedEntitiesConfigExtra::NAME)) {
            $context->addConfigExtra(
                new MaxRelatedEntitiesConfigExtra(self::DEFAULT_MAX_RELATED_ENTITIES)
            );
        }
    }
}
