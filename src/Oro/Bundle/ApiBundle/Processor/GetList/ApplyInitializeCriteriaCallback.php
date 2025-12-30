<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Applies an initialize criteria callback from the context to the Criteria object.
 */
class ApplyInitializeCriteriaCallback implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $initializeCriteriaCallback = $context->getInitializeCriteriaCallback();
        if (null !== $initializeCriteriaCallback) {
            $initializeCriteriaCallback($criteria);
        }
    }
}
