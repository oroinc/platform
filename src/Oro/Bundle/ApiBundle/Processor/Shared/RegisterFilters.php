<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Registers filters according to the "filters" configuration section.
 */
class RegisterFilters implements ProcessorInterface
{
    /** @var FilterFactoryInterface */
    protected $filterFactory;

    /**
     * @param FilterFactoryInterface $filterFactory
     */
    public function __construct(FilterFactoryInterface $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (null === $configOfFilters || $configOfFilters->isEmpty()) {
            // a filters' configuration does not contains any data
            return;
        }

        if (!$configOfFilters->isExcludeAll()) {
            // it seems that filters' configuration was not normalized
            throw new \RuntimeException(
                sprintf(
                    'Expected "all" exclusion policy for filters. Got: %s.',
                    $configOfFilters->getExclusionPolicy()
                )
            );
        }

        $filters = $context->getFilters();
        $fields  = $configOfFilters->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($filters->has($fieldName)) {
                continue;
            }
            $filter = $this->createFilter($fieldName, $field);
            if (null !== $filter) {
                $filters->add($fieldName, $filter);
            }
        }
    }

    /**
     * @param string            $fieldName
     * @param FilterFieldConfig $field
     *
     * @return StandaloneFilter|null
     */
    protected function createFilter($fieldName, FilterFieldConfig $field)
    {
        $filter = $this->filterFactory->createFilter($field->getDataType());
        if (null !== $filter) {
            $filter->setArrayAllowed($field->isArrayAllowed());
            $filter->setDescription($field->getDescription());
            if ($filter instanceof ComparisonFilter) {
                $filter->setField($field->getPropertyPath() ?: $fieldName);
            }
        }

        return $filter;
    }
}
