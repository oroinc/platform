<?php

namespace Oro\Bundle\DataAuditBundle\Model;

/**
 * Represents a reference to an entity object.
 * Can be used as part of lazy loading of an entity.
 */
class EntityReference
{
    /** @var string */
    private $className;

    /** @var mixed */
    private $id;

    /** @var mixed */
    private $entity;

    /**
     * @param string|null $className The FQCN of the entity
     * @param mixed|null  $id        The identifier of the entity
     */
    public function __construct($className = null, $id = null)
    {
        $this->className = $className;
        $this->id = $id;
        $this->entity = $className ? false : null;
    }

    /**
     * Gets FQCN of the referenced entity.
     *
     * @return string|null
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Gets an identifier of the referenced entity.
     *
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets a value that indicates whether the referenced entity have been loaded.
     * Note that this method returns true even if the referenced entity does not exist.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return false !== $this->entity;
    }

    /**
     * Gets the referenced entity or null if it does not exist.
     *
     * @return object|null
     */
    public function getEntity()
    {
        if (!$this->isLoaded()) {
            throw new \LogicException('The entity is not loaded yet. Call "setEntity" method before.');
        }

        return $this->entity;
    }

    /**
     * Sets the referenced entity.
     *
     * @param object|null $entity An entity object or null if the entity does not exist
     *
     * @throws \LogicException if set if the entity is not allowed
     * @throws \InvalidArgumentException if invalid entity type is passed
     */
    public function setEntity($entity)
    {
        if (!$this->className) {
            throw new \LogicException('An entity cannot be set to "null" reference object.');
        }
        if ($this->isLoaded()) {
            throw new \LogicException('The entity is already loaded.');
        }
        if (null === $entity || (is_object($entity) && is_a($entity, $this->className))) {
            $this->entity = $entity;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument of type "null or instance of %s", "%s" given.',
                    $this->className,
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }
    }
}
