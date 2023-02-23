<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Holds all the metadata for API documentation.
 */
class ApiDocMetadata
{
    private string $action;
    private EntityMetadata $metadata;
    private EntityDefinitionConfig $config;
    private RequestType $requestType;

    public function __construct(
        string $action,
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ) {
        $this->action = $action;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->requestType = $requestType;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMetadata(): EntityMetadata
    {
        return $this->metadata;
    }

    public function getConfig(): EntityDefinitionConfig
    {
        return $this->config;
    }

    public function getRequestType(): RequestType
    {
        return $this->requestType;
    }

    public function __serialize(): array
    {
        // do nothing because this class do not need serialization
        // and implements Serializable interface just to avoid failure of CachingApiDocExtractor
        return [];
    }

    public function __unserialize(array $serialized): void
    {
        // do nothing because this class do not need serialization
        // and implements Serializable interface just to avoid failure of CachingApiDocExtractor
    }
}
