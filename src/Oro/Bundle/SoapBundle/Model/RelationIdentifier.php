<?php

namespace Oro\Bundle\SoapBundle\Model;

/**
 * Represents the identifiers for a relationship between two entities.
 *
 * Encapsulates the class names and IDs of both the owner entity and the target entity
 * in a relationship, providing a value object for passing relation information through
 * the SOAP API processing pipeline.
 */
class RelationIdentifier
{
    /** @var string */
    private $ownerEntityClass;

    /** @var mixed */
    private $ownerEntityId;

    /** @var string */
    private $targetEntityClass;

    /** @var mixed */
    private $targetEntityId;

    /**
     * @param string $ownerEntityClass
     * @param mixed  $ownerEntityId
     * @param string $targetEntityClass
     * @param mixed  $targetEntityId
     */
    public function __construct($ownerEntityClass, $ownerEntityId, $targetEntityClass, $targetEntityId)
    {
        $this->ownerEntityClass  = $ownerEntityClass;
        $this->ownerEntityId     = $ownerEntityId;
        $this->targetEntityClass = $targetEntityClass;
        $this->targetEntityId    = $targetEntityId;
    }

    /**
     * @return string
     */
    public function getOwnerEntityClass()
    {
        return $this->ownerEntityClass;
    }

    /**
     * @return mixed
     */
    public function getOwnerEntityId()
    {
        return $this->ownerEntityId;
    }

    /**
     * @return string
     */
    public function getTargetEntityClass()
    {
        return $this->targetEntityClass;
    }

    /**
     * @return mixed
     */
    public function getTargetEntityId()
    {
        return $this->targetEntityId;
    }
}
