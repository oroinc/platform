<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the current page number to an info record of a primary objects collection in the context.
 */
class AddPageNumberToInfoRecord implements ProcessorInterface
{
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

        $pageNumberFilterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getPageNumberFilterName();

        if (!$context->getFilters()->has($pageNumberFilterName)) {
            // the pagination is not supported
            return;
        }

        $infoRecords = $context->getInfoRecords() ?? [];
        $infoRecords[''][ConfigUtil::PAGE_NUMBER] = $this->getPageNumber(
            $context->getFilterValues(),
            $pageNumberFilterName
        );
        $context->setInfoRecords($infoRecords);
    }

    protected function getPageNumber(FilterValueAccessorInterface $filterValues, string $pageNumberFilterName): int
    {
        $pageNumber = $filterValues->get($pageNumberFilterName)?->getValue();
        if (null === $pageNumber) {
            $pageNumber = 1;
        }

        return (int)$pageNumber;
    }
}
