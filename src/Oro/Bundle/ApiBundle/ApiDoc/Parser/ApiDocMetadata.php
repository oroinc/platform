<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ApiDocMetadata implements \Serializable
{
    /** @var string */
    protected $action;

    /** @var EntityMetadata */
    protected $metadata;

    /** @var EntityDefinitionConfig */
    protected $config;

    /** @var RequestType */
    protected $requestType;

    /**
     * @param string                 $action
     * @param EntityMetadata         $metadata
     * @param EntityDefinitionConfig $config
     * @param RequestType            $requestType
     */
    public function __construct(
        $action,
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ) {
        $this->action = $action;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->requestType = $requestType;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return EntityDefinitionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return RequestType
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Implementation of \Serializable.
     * Do not serialize any data because it already was processed
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([]);
    }

    /**
     * Implementation of \Serializable.
     * Do not unserialize any data because it already was processed
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        unserialize($serialized);
    }
}
