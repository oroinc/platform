<?php

namespace Oro\Bundle\EntityPaginationBundle\Navigation;

/**
 * Represents the result of an entity pagination navigation operation.
 *
 * This value object encapsulates the outcome of navigating to a related entity in a
 * paginated collection. It holds the entity identifier and flags indicating whether the
 * entity is available (exists) and accessible (user has permission to view it). These
 * flags allow callers to distinguish between entities that don't exist and those that
 * exist but are not accessible due to security restrictions.
 */
class NavigationResult
{
    /** @var int|null  */
    protected $id = null;

    /** @var bool  */
    protected $available = true;

    /** @var bool  */
    protected $accessible = true;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param boolean $accessible
     */
    public function setAccessible($accessible)
    {
        $this->accessible = $accessible;
    }

    /**
     * @param boolean $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * @return boolean
     */
    public function isAccessible()
    {
        return $this->accessible;
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
