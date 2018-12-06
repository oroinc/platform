<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The main authorization point of the Security component for Data API.
 * @see \Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker
 */
class AuthorizationChecker implements AuthorizationCheckerInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AclGroupProviderInterface */
    private $aclGroupProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AclGroupProviderInterface     $aclGroupProvider
     * @param DoctrineHelper                $doctrineHelper
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AclGroupProviderInterface $aclGroupProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->aclGroupProvider = $aclGroupProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted($attributes, $subject = null)
    {
        if (\is_string($subject)) {
            if (\class_exists($subject) && $this->doctrineHelper->isManageableEntityClass($subject)) {
                $group = $this->aclGroupProvider->getGroup();
                $subject = new ObjectIdentity(
                    EntityAclExtension::NAME,
                    $group ? ObjectIdentityHelper::buildType($subject, $group) : $subject
                );
            }
        } elseif (\is_object($subject) && !$subject instanceof ObjectIdentityInterface) {
            $className = ClassUtils::getRealClass($subject);
            if ($this->doctrineHelper->isManageableEntityClass($className)) {
                $group = $this->aclGroupProvider->getGroup();
                if ($group) {
                    $subject = new DomainObjectWrapper(
                        $subject,
                        new ObjectIdentity(
                            EntityAclExtension::NAME,
                            ObjectIdentityHelper::buildType($className, $group)
                        )
                    );
                }
            }
        }

        return $this->authorizationChecker->isGranted($attributes, $subject);
    }
}
