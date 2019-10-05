<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides the configuration for a specific API resource.
 */
class ConfigProvider
{
    /** @var ActionProcessorInterface */
    private $processor;

    /** @var array */
    private $cache = [];

    /** @var array */
    private $processing = [];

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
    public function getConfig(
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

        if (isset($this->processing[$cacheKey])) {
            throw new RuntimeException(sprintf(
                'Cannot build the configuration of "%s" because this causes the circular dependency.',
                $className
            ));
        }

        /** @var ConfigContext $context */
        $context = $this->processor->createContext();
        $this->initContext($context, $className, $version, $requestType, $extras);

        $this->processing[$cacheKey] = true;
        try {
            $this->processor->process($context);
        } finally {
            unset($this->processing[$cacheKey]);
        }

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
     * @param ConfigContext          $context
     * @param string                 $className
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function initContext(
        ConfigContext $context,
        string $className,
        string $version,
        RequestType $requestType,
        array $extras
    ): void {
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        if (!empty($extras)) {
            $context->setExtras($extras);
        }
    }

    /**
     * @param string                 $className
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     *
     * @return string
     */
    private function buildCacheKey(
        string $className,
        string $version,
        RequestType $requestType,
        array $extras
    ): string {
        $hasDefinitionExtra = false;
        $cacheKey = (string)$requestType . '|' . $version . '|' . $className;
        foreach ($extras as $extra) {
            $part = $extra->getCacheKeyPart();
            if (!empty($part)) {
                $cacheKey .= '|' . $part;
            }
            if (!$hasDefinitionExtra && $extra instanceof EntityDefinitionConfigExtra) {
                $hasDefinitionExtra = true;
            }
        }
        if (!$hasDefinitionExtra) {
            throw new \LogicException(sprintf(
                'The "%s" config extra must be specified. Class Name: %s.',
                EntityDefinitionConfigExtra::class,
                $className
            ));
        }

        return $cacheKey;
    }

    /**
     * @param string                 $className
     * @param ConfigExtraInterface[] $extras
     *
     * @return string
     */
    private function buildConfigKey(string $className, array $extras): string
    {
        $configKey = $className;
        foreach ($extras as $extra) {
            if ($extra instanceof ConfigExtraSectionInterface) {
                continue;
            }
            $part = $extra->getCacheKeyPart();
            if (!empty($part)) {
                $configKey .= '|' . $part;
            }
        }

        return $configKey;
    }

    /**
     * @param ConfigContext $context
     *
     * @return Config
     */
    private function buildResult(ConfigContext $context): Config
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
        $definition = $config->getDefinition();
        if ($definition) {
            $definition->setKey($this->buildConfigKey($context->getClassName(), $context->getExtras()));
        }

        return $config;
    }
}
