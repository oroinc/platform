<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether the "include" filter exists and if so,
 * adds the corresponding configuration extra into the context.
 * This filter is used to specify which related entities should be returned.
 */
class HandleIncludeFilter implements ProcessorInterface
{
    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param FilterNamesRegistry $filterNamesRegistry
     * @param ValueNormalizer     $valueNormalizer
     */
    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME)) {
            // the "include" filter is already processed
            return;
        }

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getIncludeFilterName();
        if (!$filterName) {
            // the "include" filter is not supported
            return;
        }

        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            // expanding of related entities was not requested
            return;
        }

        $includes = $this->valueNormalizer->normalizeValue(
            $filterValue->getValue(),
            DataType::STRING,
            $context->getRequestType(),
            true
        );
        if (empty($includes)) {
            // expanding of related entities was not requested
            return;
        }

        $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra((array)$includes));
    }
}
