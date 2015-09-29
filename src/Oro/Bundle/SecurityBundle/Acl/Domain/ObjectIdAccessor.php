<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * This class allows to get the class of a domain object
 */
class ObjectIdAccessor
{
    /**
     * Gets id for the given domain object
     *
     * @param  object                       $domainObject
     * @return int|string
     * @throws InvalidDomainObjectException
     */
    public function getId($domainObject)
    {
        $domainObject = $domainObject instanceof FieldVote ? $domainObject->getDomainObject() : $domainObject;

        if ($domainObject instanceof DomainObjectInterface) {
            return $domainObject->getObjectIdentifier();
        } elseif (method_exists($domainObject, 'getId')) {
            return $domainObject->getId();
        }
        throw new InvalidDomainObjectException(
            '$domainObject must either implement the DomainObjectInterface, or have a method named "getId".'
        );
    }
}
