<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * Provides a method to resolve the identifier for an entity passed to a security voter.
 */
class EntityIdentifierResolverUtil
{
    /**
     * @throws NotManageableEntityException if the given object is not a manageable entity
     */
    public static function getEntityIdentifier(object $object, DoctrineHelper $doctrineHelper): mixed
    {
        if ($object instanceof FieldVote) {
            $object = $object->getDomainObject();
        }

        if ($object instanceof ObjectIdentityInterface) {
            $identifier = $object->getIdentifier();

            return filter_var($identifier, FILTER_VALIDATE_INT)
                ? (int)$identifier
                : null;
        }

        return $doctrineHelper->getSingleEntityIdentifier($object, false);
    }
}
