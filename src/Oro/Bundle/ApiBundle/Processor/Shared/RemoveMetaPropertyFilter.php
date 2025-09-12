<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes "meta" filter if a requesting of additional meta properties is disabled.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter
 */
class RemoveMetaPropertyFilter implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $config = $context->getConfig();
        if (null === $config || $config->isMetaPropertiesEnabled()) {
            // the "meta" filter is enabled
            return;
        }

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getMetaPropertyFilterName();
        if (!$filterName) {
            // the "meta" filter is not supported
            return;
        }

        $filterCollection = $context->getFilters();
        if ($filterCollection->has($filterName)) {
            $filterCollection->remove($filterName);
        }
    }
}
