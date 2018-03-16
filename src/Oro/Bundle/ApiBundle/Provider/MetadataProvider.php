<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides the metadata for a specific Data API resource.
 */
class MetadataProvider
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
     * Gets metadata for the given version of an entity.
     *
     * @param string                   $className              The FQCN of an entity
     * @param string                   $version                The version of a config
     * @param RequestType              $requestType            The request type, for example "rest", "soap", etc.
     * @param EntityDefinitionConfig   $config                 The configuration of an entity
     * @param MetadataExtraInterface[] $extras                 Requests for additional metadata information
     * @param bool                     $withExcludedProperties Whether excluded fields and associations
     *                                                         should not be removed
     *
     * @return EntityMetadata|null
     */
    public function getMetadata(
        string $className,
        string $version,
        RequestType $requestType,
        EntityDefinitionConfig $config,
        array $extras = [],
        bool $withExcludedProperties = false
    ): ?EntityMetadata {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $configKey = $config->getKey();
        if (!$configKey) {
            return $this->loadMetadata(
                $className,
                $version,
                $requestType,
                $config,
                $extras,
                $withExcludedProperties
            );
        }
        
        $cacheKey = $this->buildCacheKey(
            $className,
            $version,
            $requestType,
            $extras,
            $withExcludedProperties,
            $configKey
        );
        if (array_key_exists($cacheKey, $this->cache)) {
            $metadata = $this->cache[$cacheKey];
        } else {
            $metadata = $this->loadMetadata(
                $className,
                $version,
                $requestType,
                $config,
                $extras,
                $withExcludedProperties
            );
            $this->cache[$cacheKey] = $metadata;
        }

        if (null === $metadata) {
            return null;
        }

        return clone $metadata;
    }

    /**
     * Removes all already built metadatas from the internal cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * @param string                   $className
     * @param string                   $version
     * @param RequestType              $requestType
     * @param EntityDefinitionConfig   $config
     * @param MetadataExtraInterface[] $extras
     * @param bool                     $withExcludedProperties
     *
     * @return EntityMetadata|null
     */
    private function loadMetadata(
        string $className,
        string $version,
        RequestType $requestType,
        EntityDefinitionConfig $config,
        array $extras,
        bool $withExcludedProperties
    ): ?EntityMetadata {
        /** @var MetadataContext $context */
        $context = $this->processor->createContext();
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        $context->setConfig($config);
        if (!empty($extras)) {
            $context->setExtras($extras);
        }
        $context->setWithExcludedProperties($withExcludedProperties);

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
     * @param bool                     $withExcludedProperties
     * @param string                   $configKey
     *
     * @return string
     */
    private function buildCacheKey(
        string $className,
        string $version,
        RequestType $requestType,
        array $extras,
        bool $withExcludedProperties,
        string $configKey
    ): string {
        $cacheKey = (string)$requestType
            . '|' . $version
            . '|' . $className
            . '|' . ($withExcludedProperties ? '1' : '0');
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
