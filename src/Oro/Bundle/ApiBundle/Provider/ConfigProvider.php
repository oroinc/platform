<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides the configuration for a specific Data API resource.
 */
class ConfigProvider extends AbstractConfigProvider
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
        if (!$className) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $identifierFieldsOnly = false;
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $className;
        foreach ($extras as $extra) {
            $part = $extra->getCacheKeyPart();
            if ($part) {
                $cacheKey .= self::KEY_DELIMITER . $part;
            }
            if ($extra instanceof FilterIdentifierFieldsConfigExtra) {
                $identifierFieldsOnly = true;
            }
        }

        if (!$identifierFieldsOnly) {
            return $this->loadConfig($className, $version, $requestType, $extras, false, $cacheKey);
        }

        if (\array_key_exists($cacheKey, $this->cache)) {
            return clone $this->cache[$cacheKey];
        }

        $config = $this->loadConfig($className, $version, $requestType, $extras, true, $cacheKey);
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
     * Resets an object to its initial state.
     */
    public function reset()
    {
        $this->cache = [];
    }

    /**
     * @param string      $className
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $extras
     * @param bool        $identifierFieldsOnly
     * @param string      $cacheKey
     *
     * @return Config
     */
    private function loadConfig(
        string $className,
        string $version,
        RequestType $requestType,
        array $extras,
        bool $identifierFieldsOnly,
        string $cacheKey
    ): Config {
        if (isset($this->processing[$cacheKey])) {
            throw new RuntimeException(sprintf(
                'Cannot build the configuration of "%s" because this causes the circular dependency.',
                $className
            ));
        }

        /** @var ConfigContext $context */
        $context = $this->processor->createContext();
        $this->initContext($context, $className, $version, $requestType, $extras);
        $context->set(FilterIdentifierFieldsConfigExtra::NAME, $identifierFieldsOnly);

        $this->processing[$cacheKey] = true;
        try {
            $this->processor->process($context);
        } finally {
            unset($this->processing[$cacheKey]);
        }

        $config = $this->buildResult($context);

        if ($identifierFieldsOnly) {
            $definition = $config->getDefinition();
            if (null !== $definition) {
                $definition->setKey($this->buildConfigKey($context->getClassName(), $context->getExtras()));
            }
        }

        return $config;
    }
}
