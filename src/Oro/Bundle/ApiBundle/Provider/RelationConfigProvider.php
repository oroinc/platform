<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\RelationConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides the configuration for an entity that can be uses as a part of other Data API resources.
 */
class RelationConfigProvider extends AbstractConfigProvider
{
    /** @var ActionProcessorInterface */
    private $processor;

    /** @var array */
    private $cache = [];

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
    public function getRelationConfig(
        string $className,
        string $version,
        RequestType $requestType,
        array $extras = []
    ): Config {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $cacheKey = $this->buildCacheKey($className, $version, $requestType, $extras);
        if (array_key_exists($cacheKey, $this->cache)) {
            return clone $this->cache[$cacheKey];
        }

        /** @var RelationConfigContext $context */
        $context = $this->processor->createContext();
        $this->initContext($context, $className, $version, $requestType, $extras);

        $this->processor->process($context);

        $config = $this->buildResult($context);

        $this->cache[$cacheKey] = $config;

        return clone $config;
    }

    /**
     * Removes all already built configs from the internal cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildConfigKey(string $className, array $extras): string
    {
        return 'relation|' . parent::buildConfigKey($className, $extras);
    }
}
