<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\AddFieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddIncludeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "id" as a default value for "fields[configuration]" filter.
 * Adds "fields[configurationoptions]" filter if it is not added yet.
 */
class SetDefaultValueForFieldsFilter implements ProcessorInterface
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

        $filterNames = $this->filterNamesRegistry->getFilterNames($context->getRequestType());
        $filters = $context->getFilters();

        $fieldsFilterTemplate = $filterNames->getFieldsFilterTemplate();
        if ($fieldsFilterTemplate) {
            $filterKey = sprintf(
                $fieldsFilterTemplate,
                $this->valueNormalizer->normalizeValue(
                    $context->getClassName(),
                    DataType::ENTITY_TYPE,
                    $context->getRequestType()
                )
            );
            /** @var FieldsFilter|null $filter */
            $filter = $filters->get($filterKey);
            if ($filter) {
                $filter->setDefaultValue('id');
                $filter->setDescription(
                    $filter->getDescription() . ' To get configuration options use \'id,options\' or \'options\'.'
                );
            }

            // make sure that "fields[configurationoptions]" and "include" filters are added
            // we need this check as these filters may not be added
            // by AddFieldsFilter and AddIncludeFilter processors
            // because by default "values" association is not returned
            $entityType = $this->valueNormalizer->normalizeValue(
                ConfigurationOption::class,
                DataType::ENTITY_TYPE,
                $context->getRequestType()
            );

            $filterKey = sprintf($fieldsFilterTemplate, $entityType);
            if (!$filters->has($filterKey)) {
                $filter = new FieldsFilter(
                    DataType::STRING,
                    sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, $entityType)
                );
                $filter->setArrayAllowed(true);
                $filters->add($filterKey, $filter);
            }
        }

        $includeFilterName = $filterNames->getIncludeFilterName();
        if ($includeFilterName && !$filters->has($includeFilterName)) {
            $filter = new IncludeFilter(DataType::STRING, AddIncludeFilter::FILTER_DESCRIPTION);
            $filter->setArrayAllowed(true);
            $filters->add($includeFilterName, $filter);
        }
    }
}
