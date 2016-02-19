<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiResource
{
    /** @var string */
    protected $entityClass;

    /**
     * @param $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Returns a string representation of this resource.
     *
     * @return string A string representation of the Resource
     */
    public function __toString()
    {
        return $this->entityClass;
    }
}
