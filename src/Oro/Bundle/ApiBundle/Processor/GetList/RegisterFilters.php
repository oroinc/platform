<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RegisterFilters implements ProcessorInterface
{
    /** @var FilterFactoryInterface */
    protected $filterFactory;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param FilterFactoryInterface $filterFactory
     * @param ConfigProvider         $configProvider
     */
    public function __construct(FilterFactoryInterface $filterFactory, ConfigProvider $configProvider)
    {
        $this->filterFactory  = $filterFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $config = $this->configProvider->getConfig(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType(),
            $context->getAction()
        );
        if (null === $config) {
            // a configuration was not found
            return;
        }

        $filters = ConfigUtil::getFilters($config);
        if (empty($filters)) {
            // a filters' configuration does not exist
            return;
        }

        if (!ConfigUtil::isExcludeAll($filters)) {
            // it seems that filters' configuration was not normalized
            // default normalization can be found in {@see Oro\Bundle\ApiBundle\Processor\GetConfig\NormalizeFilters}
            throw new \RuntimeException(
                sprintf(
                    'Expected "all" exclusion policy for filters. Got: %s.',
                    ConfigUtil::getExclusionPolicy($filters)
                )
            );
        }

        $fields           = ConfigUtil::getFields($filters);
        $filterCollection = $context->getFilters();
        foreach ($fields as $field => $fieldConfig) {
            if ($filterCollection->has($field)) {
                continue;
            }
            $filter = $this->createFilter($field, $fieldConfig);
            if (null !== $filter) {
                $filterCollection->add($field, $filter);
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
