<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "include" filter that can be used to specify which related entities should be returned.
 * As this filter has influence on the entity configuration, it is handled by a separate processor.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleIncludeFilter
 */
class AddIncludeFilter implements ProcessorInterface
{
    public const FILTER_DESCRIPTION =
        'A list of related entities to be included. Comma-separated paths, e.g. \'comments,comments.author\'.';

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /**
     * @param FilterNamesRegistry $filterNamesRegistry
     */
    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getIncludeFilterName();
        if (!$filterName) {
            // the "include" filter is not supported
            return;
        }

        $filters = $context->getFilters();
        if ($filters->has($filterName)) {
            // the "include" filter is already added
            return;
        }

        $config = $context->getConfig();
        if (null === $config || !$config->isInclusionEnabled()) {
            // the "include" filter is disabled
            return;
        }

        $filter = new IncludeFilter(DataType::STRING, self::FILTER_DESCRIPTION);
        $filter->setArrayAllowed(true);
        $filters->add($filterName, $filter);
    }
}
