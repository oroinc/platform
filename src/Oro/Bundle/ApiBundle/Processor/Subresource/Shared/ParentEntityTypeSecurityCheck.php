<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the type of entities specified
 * in the "parentClass" property of the context is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ParentEntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var string */
    private $permission;

    /** @var bool */
    private $forcePermissionUsage;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AclGroupProviderInterface */
    private $aclGroupProvider;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string                        $permission
     * @param bool                          $forcePermissionUsage
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        $permission,
        $forcePermissionUsage = false
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
        $this->forcePermissionUsage = $forcePermissionUsage;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AclGroupProviderInterface $aclGroupProvider
     */
    public function setAclGroupProvider(AclGroupProviderInterface $aclGroupProvider)
    {
        $this->aclGroupProvider = $aclGroupProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentConfig = $context->getParentConfig();

        $isGranted = true;
        if ($parentConfig && $parentConfig->hasAclResource()) {
            $aclResource = $parentConfig->getAclResource();
            if ($aclResource) {
                if ($this->forcePermissionUsage) {
                    $isGranted = $isGranted = $this->isGrantedForClass($context);
                } else {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource);
                }
            }
        } else {
            $isGranted = $isGranted = $this->isGrantedForClass($context);
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param SubresourceContext $context
     *
     * @return bool
     */
    private function isGrantedForClass(SubresourceContext $context): bool
    {
        $isGranted = true;

        $className = $this->doctrineHelper->getManageableEntityClass(
            $context->getParentClassName(),
            $context->getParentConfig()
        );
        if ($className) {
            $isGranted = $this->authorizationChecker->isGranted(
                $this->permission,
                ObjectIdentityHelper::encodeIdentityString(
                    EntityAclExtension::NAME,
                    ObjectIdentityHelper::buildType($className, $this->aclGroupProvider->getGroup())
                )
            );
        }

        return $isGranted;
    }
}
