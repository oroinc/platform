<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

abstract class AbstractConfigProvider
{
    /**
     * @param ConfigContext          $context
     * @param string                 $className
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function initContext(
        ConfigContext $context,
        $className,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType->toArray());
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
    protected function buildCacheKey($className, $version, RequestType $requestType, array $extras)
    {
        $cacheKey = (string)$requestType . '|' . $version . '|' . $className;
        foreach ($extras as $extra) {
            $part = $extra->getCacheKeyPart();
            if (!empty($part)) {
                $cacheKey .= '|' . $part;
            }
        }

        return $cacheKey;
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
