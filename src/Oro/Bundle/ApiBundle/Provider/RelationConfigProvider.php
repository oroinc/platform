<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\RelationConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\RelationConfigProcessor;

class RelationConfigProvider
{
    /** @var RelationConfigProcessor */
    protected $processor;

    /** @var array */
    protected $cache = [];

    /**
     * @param RelationConfigProcessor $processor
     */
    public function __construct(RelationConfigProcessor $processor)
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
    public function getRelationConfig($className, $fieldName, $version, $requestType, $requestAction)
    {
        $cacheKey = $requestType . $version . $className . '::' . $fieldName;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var RelationConfigContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->setRequestType($requestType);
        $context->setRequestAction($requestAction);
        $context->setClassName($className);
        $context->setFieldName($fieldName);

        $this->processor->process($context);

        $config = $context->getResult();

        $this->cache[$cacheKey] = $config;

        return $config;
    }
}
