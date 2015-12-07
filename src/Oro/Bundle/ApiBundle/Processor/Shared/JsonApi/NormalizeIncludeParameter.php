<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * The responsibility of this processor is finding "include" parameters and setting them into context.
 */
class NormalizeIncludeParameter implements ProcessorInterface
{
    const REQUEST_PARAMETER_NAME = 'include';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filterValues = $context->getFilterValues();

        if ($filterValues->has(self::REQUEST_PARAMETER_NAME)) {
            $includes = explode(',', $filterValues->get(self::REQUEST_PARAMETER_NAME)->getValue());

            $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra($includes));
        }
    }
}
