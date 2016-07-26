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
     * @param string                      $className   The FQCN of an entity
     * @param string                      $version     The version of a config
     * @param RequestType                 $requestType The request type, for example "rest", "soap", etc.
     * @param MetadataExtraInterface[]    $extras      Requests for additional metadata information
     * @param EntityDefinitionConfig|null $config      The configuration of an entity
     *
     * @return EntityMetadata|null
     */
    public function getMetadata(
        $className,
        $version,
        RequestType $requestType,
        array $extras = [],
        EntityDefinitionConfig $config = null
    ) {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $configKey = $config
            ? $config->getKey()
            : 'no_config';
        if (!$configKey) {
            return $this->loadMetadata($className, $version, $requestType, $extras, $config);
        }
        
        $cacheKey = $this->buildCacheKey($className, $version, $requestType, $extras, $configKey);
        if (array_key_exists($cacheKey, $this->cache)) {
            return clone $this->cache[$cacheKey];
        }

        $metadata = $this->loadMetadata($className, $version, $requestType, $extras, $config);
        $this->cache[$cacheKey] = $metadata;

        if (null === $metadata) {
            return null;
        }

        return clone $metadata;
    }

    /**
     * @param string                      $className
     * @param string                      $version
     * @param RequestType                 $requestType
     * @param MetadataExtraInterface[]    $extras
     * @param EntityDefinitionConfig|null $config
     *
     * @return EntityMetadata|null
     */
    protected function loadMetadata(
        $className,
        $version,
        RequestType $requestType,
        array $extras = [],
        EntityDefinitionConfig $config = null
    ) {
        /** @var MetadataContext $context */
        $context = $this->processor->createContext();
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        if (!empty($extras)) {
            $context->setExtras($extras);
        }
        if (!empty($config)) {
            $context->setConfig($config);
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
