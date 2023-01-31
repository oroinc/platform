<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the maximum number of related entities that can be retrieved.
 */
class SetDefaultMaxRelatedEntities implements ProcessorInterface
{
    private int $maxRelatedEntitiesLimit;

    public function __construct(int $maxRelatedEntitiesLimit)
    {
        $this->maxRelatedEntitiesLimit = $maxRelatedEntitiesLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasConfigExtra(MaxRelatedEntitiesConfigExtra::NAME)) {
            $context->addConfigExtra(new MaxRelatedEntitiesConfigExtra($this->maxRelatedEntitiesLimit));
        }
    }
}
