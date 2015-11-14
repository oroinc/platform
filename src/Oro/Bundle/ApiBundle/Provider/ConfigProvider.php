<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\ConfigProcessor;
use Oro\Bundle\ApiBundle\Request\Version;

class ConfigProvider
{
    /** @var ConfigProcessor */
    protected $processor;

    /** @var array */
    protected $cache = [];

    /**
     * @param ConfigProcessor $processor
     */
    public function __construct(ConfigProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets a config for the given version of an entity.
     *
     * @param string $className     The FQCN of an entity
     * @param string $version       The version of a config
     * @param string $requestType   The type of API request, for example "rest", "soap", "odata", etc.
     * @param string $requestAction The request action, for example "get", "get_list", etc.
     *
     * @return array|null
     */
    public function getConfig($className, $version, $requestType, $requestAction)
    {
        if ($version === Version::LATEST) {
            $version = '';
        }

        $cacheKey = $requestType . $version . $className;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var ConfigContext $context */
        $context = $this->processor->createContext();
        $context->setRequestType($requestType);
        $context->setRequestAction($requestAction);
        $context->setClassName($className);
        if (!empty($version)) {
            $context->setVersion($version);
        }

        $this->processor->process($context);

        $config = [];
        if ($context->hasResult()) {
            $config['definition'] = $context->getResult();
        }
        if ($context->hasFilters()) {
            $config['filters'] = $context->getFilters();
        }
        if ($context->hasSorters()) {
            $config['sorters'] = $context->getSorters();
        }

        $this->cache[$cacheKey] = $config;

        return $config;
    }
}
