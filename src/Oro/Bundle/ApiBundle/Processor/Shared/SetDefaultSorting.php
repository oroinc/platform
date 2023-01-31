<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default sorting filter.
 * The default sorting expression is "identifier field ASC".
 */
class SetDefaultSorting implements ProcessorInterface
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

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $config = $context->getConfig();
        if (null !== $config && $config->isSortingEnabled()) {
            $this->addSortFilter(
                $this->filterNamesRegistry->getFilterNames($context->getRequestType())->getSortFilterName(),
                $context->getFilters(),
                $config,
                $context->getConfigOfSorters()
            );
        }
    }

    protected function addSortFilter(
        string $filterName,
        FilterCollection $filters,
        EntityDefinitionConfig $config,
        ?SortersConfig $configOfSorters
    ): void {
        if (!$filters->has($filterName)) {
            $filters->add(
                $filterName,
                new SortFilter(
                    DataType::ORDER_BY,
                    $this->getSortFilterDescription(),
                    function () use ($config, $configOfSorters) {
                        return $this->getDefaultValue($config, $configOfSorters);
                    },
                    function ($value) {
                        return $this->convertDefaultValueToString($value);
                    }
                ),
                false
            );
        }
    }

    protected function getSortFilterDescription(): string
    {
        return 'Result sorting. Comma-separated fields, e.g. \'field1,-field2\'.';
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param SortersConfig|null     $configOfSorters
     *
     * @return array [field name => direction, ...]
     */
    protected function getDefaultValue(EntityDefinitionConfig $config, ?SortersConfig $configOfSorters): array
    {
        $orderBy = $config->getOrderBy();
        if (!$orderBy) {
            $idFieldNames = $config->getIdentifierFieldNames();
            foreach ($idFieldNames as $fieldName) {
                $field = $config->getField($fieldName);
                if (null !== $field) {
                    $fieldName = $field->getPropertyPath($fieldName);
                }
                if ($this->isSorterEnabled($fieldName, $configOfSorters)) {
                    $orderBy[$fieldName] = Criteria::ASC;
                }
            }
        }

        return $orderBy;
    }

    protected function isSorterEnabled(
        string $fieldName,
        ?SortersConfig $configOfSorters,
        bool $byPropertyPath = true
    ): bool {
        if (null === $configOfSorters) {
            return false;
        }
        $sorter = $configOfSorters->findField($fieldName, $byPropertyPath);
        if (null === $sorter) {
            return false;
        }

        return !$sorter->isExcluded();
    }

    protected function convertDefaultValueToString(?array $value): string
    {
        $result = [];
        if (null !== $value) {
            foreach ($value as $field => $order) {
                $result[] = (Criteria::DESC === $order ? '-' : '') . $field;
            }
        }

        return implode(',', $result);
    }
}
