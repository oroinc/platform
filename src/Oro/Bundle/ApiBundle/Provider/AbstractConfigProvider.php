<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

abstract class AbstractConfigProvider
{
    /**
     * @param ConfigContext          $context
     * @param string                 $className
     * @param string                 $version
     * @param string[]               $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function initContext(ConfigContext $context, $className, $version, array $requestType, array $extras)
    {
        $context->setClassName($className);
        $context->setVersion($version);
        if (!empty($requestType)) {
            $context->setRequestType($requestType);
        }
        if (!empty($extras)) {
            $context->setExtras($extras);
        }
    }

    /**
     * @param string                 $className
     * @param string                 $version
     * @param string[]               $requestType
     * @param ConfigExtraInterface[] $extras
     *
     * @return string
     */
    protected function buildCacheKey($className, $version, array $requestType, array $extras)
    {
        $cacheKey = implode('', $requestType) . '|' . $version . '|' . $className;
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
