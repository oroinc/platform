<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ConfigProvider extends AbstractConfigProvider
{
    /** @var ActionProcessorInterface */
    protected $processor;

    /** @var array */
    protected $cache = [];

    /**
     * @param ActionProcessorInterface $processor
     */
    public function __construct(ActionProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets a config for the given version of an entity.
     *
     * @param string                 $className   The FQCN of an entity
     * @param string                 $version     The version of a config
     * @param RequestType            $requestType The request type, for example "rest", "soap", etc.
     * @param ConfigExtraInterface[] $extras      Requests for configuration data
     *
     * @return Config
     */
    public function getConfig($className, $version, RequestType $requestType, array $extras = [])
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $cacheKey = $this->buildCacheKey($className, $version, $requestType, $extras);
        if (array_key_exists($cacheKey, $this->cache)) {
            return clone $this->cache[$cacheKey];
        }

        /** @var ConfigContext $context */
        $context = $this->processor->createContext();
        $this->initContext($context, $className, $version, $requestType, $extras);

        $this->processor->process($context);

        $config = $this->buildResult($context);

        $this->cache[$cacheKey] = $config;

        return clone $config;
    }
}
