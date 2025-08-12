<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "searchText", "searchQuery" and "aggregations" filters to all searchable entities.
 */
class AddSearchTextFilter implements ProcessorInterface
{
    public function __construct(
        private readonly SearchMappingProvider $searchMappingProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        if (!$this->searchMappingProvider->isSearchableEntity($entityClass)) {
            return;
        }

        if (\count($context->getResult()->getIdentifierFieldNames()) !== 1) {
            return;
        }

        // add "searchText" filter
        $filter = $context->getFilters()->getOrAddField('searchText');
        $filter->setDataType(DataType::STRING);
        $filter->setType('simpleSearch');
        if ($context->hasExtra(DescriptionsConfigExtra::NAME)) {
            $filter->setDescription('Filter records by a search string.');
        }

        // add "searchQuery" filter
        $searchQueryFilter = $context->getFilters()->getOrAddField('searchQuery');
        $searchQueryFilter->setDataType(DataType::STRING);
        $searchQueryFilter->setType('searchQuery');
        $searchQueryFilter->setOptions(['entity_class' => $entityClass]);
        $availableFieldsDescription = '';
        if ($context->hasExtra(DescriptionsConfigExtra::NAME)) {
            $availableFields = array_keys($this->searchMappingProvider->getSearchFieldTypes($entityClass));
            if ($availableFields) {
                sort($availableFields);
                $availableFieldsDescription = ' Available fields: ' . implode(', ', $availableFields);
            }
            $searchQueryFilter->setDescription(
                'Filter records by a search query.' . $availableFieldsDescription
            );
        }

        if (ApiAction::GET_LIST === $context->getTargetAction()) {
            // add "aggregations" filter
            $aggregationsFilter = $context->getFilters()->getOrAddField('aggregations');
            $aggregationsFilter->setDataType(DataType::STRING);
            $aggregationsFilter->setType('searchAggregation');
            $aggregationsFilter->setOptions(['entity_class' => $entityClass]);
            if ($context->hasExtra(DescriptionsConfigExtra::NAME)) {
                $aggregationsFilter->setDescription(
                    'The filter that is used to request aggregated data.' . $availableFieldsDescription
                );
            }
        }
    }
}
