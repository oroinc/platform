<?php

namespace Oro\Bundle\SecurityBundle\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationCheckerTrait;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Layout ACL provider Class
 */
class AclProvider
{
    use AuthorizationCheckerTrait;

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @param string|string[] $attributes
     * @param mixed           $object
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null): bool
    {
        if (\is_object($object)) {
            $class = ClassUtils::getRealClass($object);
            $objectManager = $this->doctrine->getManagerForClass($class);
            if ($objectManager instanceof EntityManagerInterface) {
                $unitOfWork = $objectManager->getUnitOfWork();
                if ($unitOfWork->isScheduledForInsert($object) || !$unitOfWork->isInIdentityMap($object)) {
                    $object = ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $class);
                }
            }
        }

        return $this->isAttributesGranted($this->authorizationChecker, $attributes, $object);
    }
}
