<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * This class allows to get the class of a domain object
 */
class ObjectIdAccessor
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

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
        } elseif ($this->doctrineHelper->isManageableEntity($domainObject)) {
            $id = $this->doctrineHelper->getSingleEntityIdentifier($domainObject, false);
            if ($id) {
                return $id;
            }
        }

        throw new InvalidDomainObjectException(
            '$domainObject must either implement the DomainObjectInterface, object that have a method named "getId" '
            . 'or single identifier entity.'
        );
    }
}
