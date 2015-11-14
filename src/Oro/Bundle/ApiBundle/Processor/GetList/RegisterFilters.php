<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;

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
        if (empty($config['filters'])) {
            // a filters' configuration does not exist
            return;
        }
        if (!isset($config['filters']['exclusion_policy']) || $config['filters']['exclusion_policy'] !== 'all') {
            // it seems that filters' configuration was not normalized
            throw new \RuntimeException(
                sprintf(
                    'Expected "all" exclusion policy for filters. Got: %s.',
                    isset($config['filters']['exclusion_policy']) ? $config['filters']['exclusion_policy'] : 'none'
                )
            );
        }

        if (isset($config['filters']['fields'])) {
            $filters = $context->getFilters();
            foreach ($config['filters']['fields'] as $field => $fieldConfig) {
                if ($filters->has($field)) {
                    continue;
                }
                $filter = $this->createFilter($field, $fieldConfig);
                if (null !== $filter) {
                    $filters->add($field, $filter);
                }
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
        $filter = $this->filterFactory->createFilter($fieldConfig['data_type']);
        if (null !== $filter) {
            if ($filter instanceof ComparisonFilter) {
                $filter->setField($field);
            }
            if ($filter instanceof StandaloneFilter) {
                if (isset($fieldConfig['description'])) {
                    $filter->setDescription($fieldConfig['description']);
                }
                if (isset($fieldConfig['default_value'])) {
                    $filter->setDefaultValue($fieldConfig['default_value']);
                }
            }
        }

        return $filter;
    }
}
