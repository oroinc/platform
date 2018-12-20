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

        $pageNumberFilterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getPageNumberFilterName();

        if (!$context->getFilters()->has($pageNumberFilterName)) {
            // the pagination is not supported
            return;
        }

        $infoRecords = $context->getInfoRecords();
        if (!\is_array($infoRecords)) {
            $infoRecords = [];
        }
        $infoRecords[''][ConfigUtil::PAGE_NUMBER] = $this->getPageNumber(
            $context->getFilterValues(),
            $pageNumberFilterName
        );
        $context->setInfoRecords($infoRecords);
    }

    /**
     * @param FilterValueAccessorInterface $filterValues
     * @param string                       $pageNumberFilterName
     *
     * @return int
     */
    protected function getPageNumber(FilterValueAccessorInterface $filterValues, string $pageNumberFilterName): int
    {
        $pageNumber = null;
        $pageNumberFilterValue = $filterValues->get($pageNumberFilterName);
        if (null !== $pageNumberFilterValue) {
            $pageNumber = $pageNumberFilterValue->getValue();
        }
        if (null === $pageNumber) {
            $pageNumber = 1;
        }

        return (int)$pageNumber;
    }
}
