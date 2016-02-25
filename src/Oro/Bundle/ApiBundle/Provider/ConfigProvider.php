<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

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
     * @param string[]               $requestType The request type, for example "rest", "soap", etc.
     * @param ConfigExtraInterface[] $extras      Additional configuration data.
     *
     * @return Config
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

        $config = $this->buildResult($context);

        $this->cache[$cacheKey] = $config;

        return $config;
    }

    /**
     * @param ConfigContext $context
     *
     * @return Config
     */
    protected function buildResult(ConfigContext $context)
    {
        $config = new Config();
        if ($context->hasResult()) {
            $config->setDefinition($context->getResult());
        }
        $extras = $context->getExtras();
        foreach ($extras as $extra) {
            $sectionName = $extra->getName();
            if ($extra instanceof ConfigExtraSectionInterface && $context->has($sectionName)) {
                $config->set($sectionName, $context->get($sectionName));
            }
        }

        return $config;
    }
}
