<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Validates whether an access to the type of entities specified
 * in the "parentClass" property of the Context is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ParentEntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var string */
    protected $permission;

    /** @var bool */
    protected $forcePermissionUsage;

    /**
     * @param DoctrineHelper                $doctrineHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string                        $permission
     * @param bool                          $forcePermissionUsage
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        $permission,
        $forcePermissionUsage = false
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
        $this->forcePermissionUsage = $forcePermissionUsage;
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
                    $isGranted = $this->isPermissionGranted($context->getParentClassName());
                } else {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource);
                }
            }
        } else {
            $isGranted = $this->isPermissionGranted($context->getParentClassName());
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
            new ObjectIdentity('entity', $entityClass)
        );
    }
}
