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
    /**
     * @var string Entity class name
     */
    protected $className;

    /**
     * @var int Entity id
     */
    protected $objectId;

    /**
     * @var int Owner id
     */
    protected $ownerId;

    /**
     * @var int Organization id
     */
    protected $organizationId;

    /**
     * @param string $className
     * @param int    $objectId
     * @param int    $ownerId
     * @param int    $organizationId
     */
    public function __construct($className, $objectId, $ownerId, $organizationId)
    {
        $this->className = $className;
        $this->ownerId = (int)$ownerId;
        $this->organizationId = (int)$organizationId;
        $this->objectId = (int)$objectId;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(ObjectIdentityInterface $identity)
    {
        return $this->objectId == $identity->getIdentifier()
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
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }
}
