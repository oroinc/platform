<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class MetadataProvider
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
     * Gets metadata for the given version of an entity.
     *
     * @param string                   $className   The FQCN of an entity
     * @param string                   $version     The version of a config
     * @param RequestType              $requestType The request type, for example "rest", "soap", etc.
     * @param EntityDefinitionConfig   $config      The configuration of an entity
     * @param MetadataExtraInterface[] $extras      Requests for additional metadata information
     *
     * @return EntityMetadata|null
     */
    public function getMetadata(
        $className,
        $version,
        RequestType $requestType,
        EntityDefinitionConfig $config,
        array $extras = []
    ) {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $configKey = $config->getKey();
        if (!$configKey) {
            return $this->loadMetadata($className, $version, $requestType, $config, $extras);
        }
        
        $cacheKey = $this->buildCacheKey($className, $version, $requestType, $extras, $configKey);
        if (array_key_exists($cacheKey, $this->cache)) {
            return clone $this->cache[$cacheKey];
        }

        $metadata = $this->loadMetadata($className, $version, $requestType, $config, $extras);
        $this->cache[$cacheKey] = $metadata;

        if (null === $metadata) {
            return null;
        }

        return clone $metadata;
    }

    /**
     * @param string                   $className
     * @param string                   $version
     * @param RequestType              $requestType
     * @param EntityDefinitionConfig   $config
     * @param MetadataExtraInterface[] $extras
     *
     * @return EntityMetadata|null
     */
    protected function loadMetadata(
        $className,
        $version,
        RequestType $requestType,
        EntityDefinitionConfig $config,
        array $extras
    ) {
        /** @var MetadataContext $context */
        $context = $this->processor->createContext();
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        $context->setConfig($config);
        if (!empty($extras)) {
            $context->setExtras($extras);
        }

        $this->processor->process($context);

        $result = null;
        if ($context->hasResult()) {
            $result = $context->getResult();
        }

        return $result;
    }

    /**
     * @param string                   $className
     * @param string                   $version
     * @param RequestType              $requestType
     * @param MetadataExtraInterface[] $extras
     * @param string                   $configKey
     *
     * @return string
     */
    protected function buildCacheKey($className, $version, RequestType $requestType, array $extras, $configKey)
    {
        $cacheKey = (string)$requestType . '|' . $version . '|' . $className;
        foreach ($extras as $extra) {
            $part = $extra->getCacheKeyPart();
            if (!empty($part)) {
                $cacheKey .= '|' . $part;
            }
        }
        $cacheKey .= '|' . $configKey;

        return $cacheKey;
    }
}
