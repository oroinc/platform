<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiResourceSubresources
{
    /** @var string */
    protected $entityClass;

    /** @var ApiSubresource[] */
    protected $subresources = [];

    /**
     * @param $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Gets the class name of the entity.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Gets a list of all sub resources.
     *
     * @return ApiSubresource[] [association name => ApiSubresource, ...]
     */
    public function getSubresources()
    {
        return $this->subresources;
    }

    /**
     * Gets a sub resource.
     *
     * @param string $associationName
     *
     * @return ApiSubresource|null
     */
    public function getSubresource($associationName)
    {
        return isset($this->subresources[$associationName])
            ? $this->subresources[$associationName]
            : null;
    }

    /**
     * Adds a sub resource.
     *
     * @param string         $associationName
     * @param ApiSubresource $resource
     */
    public function addSubresource($associationName, ApiSubresource $resource)
    {
        $this->subresources[$associationName] = $resource;
    }

    /**
     * Removes a sub resource.
     *
     * @param string $associationName
     */
    public function removeSubresource($associationName)
    {
        unset($this->subresources[$associationName]);
    }
}
