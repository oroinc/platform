<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\Config\FieldConfigProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig\FieldConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FieldConfigProvider
{
    /** @var FieldConfigProcessor */
    protected $processor;

    /** @var array */
    protected $cache = [];

    /**
     * @param FieldConfigProcessor $processor
     */
    public function __construct(FieldConfigProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets a config for the given version of an entity.
     *
     * @param string $className     The FQCN of an entity
     * @param string $fieldName     The name of a field
     * @param string $version       The version of a config
     * @param string $requestType   The type of API request, for example "rest", "soap", "odata", etc.
     * @param string $requestAction The request action, for example "get", "get_list", etc.
     *
     * @return array|null
     */
    public function getFieldConfig($className, $fieldName, $version, $requestType, $requestAction)
    {
        $cacheKey = $requestType . $version . $className . '::' . $fieldName;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var FieldConfigContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->setRequestType($requestType);
        $context->setRequestAction($requestAction);
        $context->setClassName($className);
        $context->setFieldName($fieldName);

        $this->processor->process($context);

        $config = [];
        if ($context->hasResult()) {
            $config[ConfigUtil::DEFINITION] = $context->getResult();
        }
        if ($context->hasFilters()) {
            $config[ConfigUtil::FILTERS] = $context->getFilters();
        }
        if ($context->hasSorters()) {
            $config[ConfigUtil::SORTERS] = $context->getSorters();
        }

        $this->cache[$cacheKey] = $config;

        return $config;
    }
}
