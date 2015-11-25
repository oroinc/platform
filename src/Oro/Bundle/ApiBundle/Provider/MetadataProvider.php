<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Processor\MetadataProcessor;

class MetadataProvider
{
    /** @var MetadataProcessor */
    protected $processor;

    /** @var array */
    protected $cache = [];

    /**
     * @param MetadataProcessor $processor
     */
    public function __construct(MetadataProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets metadata for the given version of an entity.
     *
     * @param string     $className   The FQCN of an entity
     * @param string     $version     The version of a config
     * @param string     $requestType The type of API request, for example "rest", "soap", "odata", etc.
     * @param string[]   $extras      Additional metadata information, for example "descriptions"
     * @param array|null $config      The configuration of an entity
     *
     * @return EntityMetadata|null
     */
    public function getMetadata($className, $version, $requestType, array $extras = [], $config = null)
    {
        $cacheKey = $requestType . $version . $className;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var MetadataContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->setRequestType($requestType);
        $context->setClassName($className);
        $context->setExtras($extras);
        if (!empty($config)) {
            $context->setConfig($config);
        }

        $this->processor->process($context);

        $result = null;
        if ($context->hasResult()) {
            $result = $context->getResult();
        }

        $this->cache[$cacheKey] = $result;

        return $result;
    }
}
