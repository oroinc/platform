<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * Provides static methods to resolve class name for an entity passed to a security voter.
 */
class EntityClassResolverUtil
{
    public static function getEntityClass(object $object): string
    {
        if ($object instanceof FieldVote) {
            $object = $object->getDomainObject();
        }
        if ($object instanceof DomainObjectWrapper) {
            $object = $object->getObjectIdentity();
        }

        if ($object instanceof ObjectIdentityInterface) {
            return ClassUtils::getRealClass(ObjectIdentityHelper::removeGroupName($object->getType()));
        }

        return ClassUtils::getRealClass($object);
    }

    public static function isEntityClass(object $object, string $entityClass): bool
    {
        if ($object instanceof FieldVote) {
            $object = $object->getDomainObject();
        }
        if ($object instanceof DomainObjectWrapper) {
            $object = $object->getObjectIdentity();
        }

        if ($object instanceof ObjectIdentityInterface) {
            return
                EntityAclExtension::NAME === $object->getIdentifier()
                && is_a(ObjectIdentityHelper::removeGroupName($object->getType()), $entityClass, true);
        }

        return is_a($object, $entityClass, true);
    }
}
