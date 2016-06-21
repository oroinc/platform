<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiSubresource
{
    /** @var string */
    protected $targetClassName;

    /** @var string[] */
    protected $acceptableTargetClassNames;

    /** @var bool */
    protected $isCollection;

    /** @var string[] */
    protected $excludedActions = [];

    /**
     * Gets FQCN of an association target.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->targetClassName;
    }

    /**
     * Sets FQCN of an association target.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->targetClassName = $className;
    }

    /**
     * Gets FQCN of acceptable association targets.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames()
    {
        return null !== $this->acceptableTargetClassNames
            ? $this->acceptableTargetClassNames
            : [];
    }

    /**
     * Sets FQCN of acceptable association targets.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames)
    {
        $this->acceptableTargetClassNames = $classNames;
    }

    /**
     * Whether an association represents "to-many" or "to-one" relation.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->isCollection;
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relation.
     *
     * @param bool $isCollection TRUE for "to-many" relation, FALSE for "to-one" relation
     */
    public function setIsCollection($isCollection)
    {
        $this->isCollection = $isCollection;
    }

    /**
     * Gets a list of actions that must not be available for the related entity.
     *
     * @return string[]
     */
    public function getExcludedActions()
    {
        return $this->excludedActions;
    }

    /**
     * Sets a list of actions that must not be available for the related entity.
     *
     * @param string[] $excludedActions
     */
    public function setExcludedActions(array $excludedActions)
    {
        $this->excludedActions = $excludedActions;
    }
}
