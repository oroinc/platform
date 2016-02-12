<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
        /** @var GetListContext $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (empty($configOfFilters)) {
            // a filters' configuration does not contains any data
            return;
        }

        if (!ConfigUtil::isExcludeAll($configOfFilters)) {
            // it seems that filters' configuration was not normalized
            throw new \RuntimeException(
                sprintf(
                    'Expected "all" exclusion policy for filters. Got: %s.',
                    ConfigUtil::getExclusionPolicy($configOfFilters)
                )
            );
        }

        $fields  = ConfigUtil::getArrayValue($configOfFilters, ConfigUtil::FIELDS);
        $filters = $context->getFilters();
        foreach ($fields as $field => $fieldConfig) {
            if ($filters->has($field)) {
                continue;
            }
            $filter = $this->createFilter(
                ConfigUtil::getPropertyPath($fieldConfig, $field),
                $fieldConfig
            );
            if (null !== $filter) {
                $filters->add($field, $filter);
            }
        }
    }

    /**
     * @param string $field
     * @param array  $fieldConfig
     *
     * @return StandaloneFilter|null
     */
    protected function createFilter($field, array $fieldConfig)
    {
        $filter = $this->filterFactory->createFilter($fieldConfig[ConfigUtil::DATA_TYPE]);
        if (null !== $filter) {
            if ($filter instanceof ComparisonFilter) {
                $filter->setField($field);
            }
            if ($filter instanceof StandaloneFilter) {
                if (isset($fieldConfig[ConfigUtil::ALLOW_ARRAY])) {
                    $filter->setArrayAllowed($fieldConfig[ConfigUtil::ALLOW_ARRAY]);
                }
                if (isset($fieldConfig[ConfigUtil::DESCRIPTION])) {
                    $filter->setDescription($fieldConfig[ConfigUtil::DESCRIPTION]);
                }
                if (isset($fieldConfig[ConfigUtil::DEFAULT_VALUE])) {
                    $filter->setDefaultValue($fieldConfig[ConfigUtil::DEFAULT_VALUE]);
                }
            }
        }

        return $filter;
    }
}
