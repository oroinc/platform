<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * An instance of this class can be used instead of a domain object in case if
 * you does not have an instance of a domain object,
 * but know its class name, identifier, owner identifier and organization identifier.
 */
class DomainObjectReference implements ObjectIdentityInterface
{
    /** @var string */
    protected $className;

    /** @var int */
    protected $objectId;

    /** @var int */
    protected $ownerId;

    /** @var int|null */
    protected $organizationId;

    /**
     * @param string   $className      The class name of the referenced entity
     * @param int      $objectId       The identifier of the referenced entity
     * @param int      $ownerId        The identifier of an entity which owns the referenced entity
     * @param int|null $organizationId The identifier of organization the referenced entity belongs
     */
    public function __construct($className, $objectId, $ownerId, $organizationId = null)
    {
        $this->className = $className;
        $this->objectId = (int)$objectId;
        $this->ownerId = (int)$ownerId;
        if (null !== $organizationId) {
            $this->organizationId = (int)$organizationId;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function equals(ObjectIdentityInterface $identity)
    {
        return
            $this->objectId == $identity->getIdentifier()
            && $this->className === $identity->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->objectId;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->className;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @return int|null
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }
}
