<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\GetList\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AddIncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AddFieldsFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;

/**
 * Sets "id" as a default value for "fields[configuration]" filter.
 * Adds "fields[configurationoptions]" filter if it is not added yet.
 */
class SetDefaultValueForFieldsFilter implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();

        $filterKey = sprintf(
            AddFieldsFilter::FILTER_KEY_TEMPLATE,
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
        $filterKey = sprintf(AddFieldsFilter::FILTER_KEY_TEMPLATE, $entityType);
        if (!$filters->has($filterKey)) {
            $filter = new FieldsFilter(
                DataType::STRING,
                sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, $entityType)
            );
            $filter->setArrayAllowed(true);
            $filters->add($filterKey, $filter);
        }
        if (!$filters->has(AddIncludeFilter::FILTER_KEY)) {
            $filter = new IncludeFilter(DataType::STRING, AddIncludeFilter::FILTER_DESCRIPTION);
            $filter->setArrayAllowed(true);
            $filters->add(AddIncludeFilter::FILTER_KEY, $filter);
        }
    }
}
