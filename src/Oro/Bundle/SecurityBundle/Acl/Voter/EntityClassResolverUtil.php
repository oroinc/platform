<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * Provides a method to resolve class name for an entity passed to a security voter.
 */
class EntityClassResolverUtil
{
    public static function getEntityClass(object $object): string
    {
        if ($object instanceof FieldVote) {
            $object = $object->getDomainObject();
        }

        if ($object instanceof ObjectIdentityInterface) {
            return ClassUtils::getRealClass(ObjectIdentityHelper::removeGroupName($object->getType()));
        }

        return ClassUtils::getRealClass($object);
    }
}
