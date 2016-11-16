<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * An instance of this class can be used instead of a domain object in case if
 * the object identity cannot be extracted from the domain object.
 */
class DomainObjectWrapper
{
    /** @var object */
    private $domainObject;

    /** @var ObjectIdentityInterface */
    private $oid;

    /**
     * @param object                  $domainObject
     * @param ObjectIdentityInterface $oid
     */
    public function __construct($domainObject, ObjectIdentityInterface $oid)
    {
        $this->domainObject = $domainObject;
        $this->oid = $oid;
    }

    /**
     * Gets an domain object.
     *
     * @return mixed
     */
    public function getDomainObject()
    {
        return $this->domainObject;
    }

    /**
     * Gets the identity of an domain object.
     *
     * @return ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->oid;
    }
}
