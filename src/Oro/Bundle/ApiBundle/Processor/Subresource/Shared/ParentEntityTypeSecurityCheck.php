<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
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
                    $isGranted = $this->authorizationChecker->isGranted(
                        $this->permission,
                        $context->getParentClassName()
                    );
                } else {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource);
                }
            }
        } else {
            $isGranted = $this->authorizationChecker->isGranted(
                $this->permission,
                $context->getParentClassName()
            );
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
