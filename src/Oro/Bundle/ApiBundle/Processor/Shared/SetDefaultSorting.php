<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default sorting for different kind of requests.
 * The default sorting expression is "identifier field ASC".
 */
class SetDefaultSorting implements ProcessorInterface
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

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $config = $context->getConfig();
        if (null !== $config && $config->isSortingEnabled()) {
            $this->addSortFilter(
                $this->filterNamesRegistry->getFilterNames($context->getRequestType())->getSortFilterName(),
                $context->getFilters(),
                $config
            );
        }
    }

    /**
     * @param string                 $filterName
     * @param FilterCollection       $filters
     * @param EntityDefinitionConfig $config
     */
    protected function addSortFilter(
        string $filterName,
        FilterCollection $filters,
        EntityDefinitionConfig $config
    ): void {
        if (!$filters->has($filterName)) {
            $filters->add(
                $filterName,
                new SortFilter(
                    DataType::ORDER_BY,
                    $this->getSortFilterDescription(),
                    function () use ($config) {
                        return $this->getDefaultValue($config);
                    },
                    function ($value) {
                        return $this->convertDefaultValueToString($value);
                    }
                )
            );
        }
    }

    /**
     * @return string
     */
    protected function getSortFilterDescription(): string
    {
        return 'Result sorting. Comma-separated fields, e.g. \'field1,-field2\'.';
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return array [field name => direction, ...]
     */
    protected function getDefaultValue(EntityDefinitionConfig $config): array
    {
        $orderBy = $config->getOrderBy();
        if (empty($orderBy)) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                foreach ($idFieldNames as $fieldName) {
                    $field = $config->getField($fieldName);
                    if (null !== $field) {
                        $fieldName = $field->getPropertyPath($fieldName);
                    }
                    $orderBy[$fieldName] = Criteria::ASC;
                }
            }
        }

        return $orderBy;
    }

    /**
     * @param array|null $value
     *
     * @return string
     */
    protected function convertDefaultValueToString(?array $value): string
    {
        $result = [];
        if (null !== $value) {
            foreach ($value as $field => $order) {
                $result[] = (Criteria::DESC === $order ? '-' : '') . $field;
            }
        }

        return \implode(',', $result);
    }
}
