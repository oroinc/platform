<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Common functionality for ConfigProvider and RelationConfigProvider.
 */
abstract class AbstractConfigProvider
{
    const KEY_DELIMITER = '|';

    /**
     * @param ConfigContext          $context
     * @param string                 $className
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function initContext(
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
    protected function buildCacheKey(
        string $className,
        string $version,
        RequestType $requestType,
        array $extras
    ): string {
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $className;
        foreach ($extras as $extra) {
            $part = $extra->getCacheKeyPart();
            if ($part) {
                $cacheKey .= self::KEY_DELIMITER . $part;
            }
        }

        return $cacheKey;
    }

    /**
     * @param string                 $className
     * @param ConfigExtraInterface[] $extras
     *
     * @return string
     */
    protected function buildConfigKey(string $className, array $extras): string
    {
        $configKey = $className;
        foreach ($extras as $extra) {
            if ($extra instanceof ConfigExtraSectionInterface) {
                continue;
            }
            $part = $extra->getCacheKeyPart();
            if ($part) {
                $configKey .= self::KEY_DELIMITER . $part;
            }
        }

        return $configKey;
    }

    /**
     * @param ConfigContext $context
     *
     * @return Config
     */
    protected function buildResult(ConfigContext $context): Config
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
