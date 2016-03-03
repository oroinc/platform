<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Processor\MetadataProcessor;
use Oro\Bundle\ApiBundle\Request\RequestType;

class MetadataProvider
{
    /** @var MetadataProcessor */
    protected $processor;

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

        /** @var MetadataContext $context */
        $context = $this->processor->createContext();
        $context->setClassName($className);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType->toArray());
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
}
