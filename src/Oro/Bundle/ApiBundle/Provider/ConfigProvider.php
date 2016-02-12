<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
     * @param string                 $className   The FQCN of an entity
     * @param string                 $version     The version of a config
     * @param string[]               $requestType The type of API request, for example "rest", "soap", "odata", etc.
     * @param ConfigExtraInterface[] $extras      Additional configuration data.
     *
     * @return array|null
     */
    public function getConfig($className, $version, array $requestType = [], array $extras = [])
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $cacheKey = implode('', $requestType) . $version . $className;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var ConfigContext $context */
        $context = $this->processor->createContext();
        $context->setClassName($className);
        $context->setVersion($version);
        if (!empty($requestType)) {
            $context->setRequestType($requestType);
        }
        if (!empty($extras)) {
            $context->setExtras($extras);
        }

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
