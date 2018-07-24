<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the type of entities specified
 * in the "class" property of the context is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class EntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var AclGroupProviderInterface */
    protected $aclGroupProvider;

    /** @var string */
    protected $permission;

    /** @var bool */
    protected $forcePermissionUsage;

    /**
     * @param DoctrineHelper                $doctrineHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AclGroupProviderInterface     $aclGroupProvider
     * @param string                        $permission
     * @param bool                          $forcePermissionUsage
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        AclGroupProviderInterface $aclGroupProvider,
        $permission,
        $forcePermissionUsage = false
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclGroupProvider = $aclGroupProvider;
        $this->permission = $permission;
        $this->forcePermissionUsage = $forcePermissionUsage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $config = $context->getConfig();

        $isGranted = true;
        if (null !== $config && $config->hasAclResource()) {
            $aclResource = $config->getAclResource();
            if ($aclResource) {
                if ($this->forcePermissionUsage) {
                    $isGranted = $this->isPermissionGranted($context->getClassName());
                } else {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource);
                }
            }
        } else {
            $isGranted = $this->isPermissionGranted($context->getClassName());
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isPermissionGranted($entityClass)
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return true;
        }

        return $this->authorizationChecker->isGranted(
            $this->permission,
            new ObjectIdentity(
                'entity',
                ObjectIdentityHelper::buildType($entityClass, $this->aclGroupProvider->getGroup())
            )
        );
    }
}
