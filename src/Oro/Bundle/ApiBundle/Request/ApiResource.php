<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiResource
{
    /** @var string */
    protected $entityClass;

    /**
     * List of excluded actions
     *
     * @var array
     */
    protected $excludedActions;

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

    /**
     * @return array
     */
    public function getExcludedActions()
    {
        return $this->excludedActions;
    }

    /**
     * @param array $excludedActions
     */
    public function setExcludedActions($excludedActions)
    {
        $this->excludedActions = $excludedActions;
    }
}
