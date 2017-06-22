<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Validates whether an access to the parent entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ParentEntityObjectSecurityCheck implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var string */
    protected $permission;

    /**
     * @param DoctrineHelper                $doctrineHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string                        $permission
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        $permission
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $isGranted = true;
        $parentEntity = $context->getParentEntity();
        if ($parentEntity && $this->doctrineHelper->isManageableEntityClass($context->getParentClassName())) {
            $isGranted = $this->authorizationChecker->isGranted($this->permission, $parentEntity);
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
