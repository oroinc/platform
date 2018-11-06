<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the primary entity to the collection of included entities
 */
class SetPrimaryEntity implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            // there are no included data in the request
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // no included entities
            return;
        }

        $primaryEntity = $context->getResult();
        if (null !== $primaryEntity) {
            $includedEntities->setPrimaryEntity($primaryEntity, $context->getMetadata());
        }
    }
}
