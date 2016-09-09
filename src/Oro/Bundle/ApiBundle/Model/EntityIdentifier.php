<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class can be used if you need a base class for an association that can
 * contain different types of entities that are not implemented via Doctrine table inheritance
 * and as result do not have a common superclass.
 */
class EntityIdentifier
{
    /** @var mixed */
    protected $id;

    /**
     * @param mixed|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * Gets an identifier of the entity.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets an identifier of the entity.
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
