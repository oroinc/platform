<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "meta" filter that can be used to specify which entity meta properties should be returned
 * or which additional operations should be performed.
 * As this filter has influence on the entity configuration, it is handled by a separate processor.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleMetaPropertyFilter
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\ValidateMetaPropertyFilterSupported
 */
class AddMetaPropertyFilter implements ProcessorInterface
{
    public const FILTER_DESCRIPTION = 'A list of meta properties to be returned. Comma-separated names.';

    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getMetaPropertyFilterName();
        $filters = $context->getFilters();
        if ($filters->has($filterName)) {
            // the "meta" filter is already added
            return;
        }

        $filter = new MetaPropertyFilter(DataType::STRING, self::FILTER_DESCRIPTION);
        $filter->setArrayAllowed(true);
        $filter->addAllowedMetaProperty('title', DataType::STRING);
        $filters->add($filterName, $filter, false);
    }
}
