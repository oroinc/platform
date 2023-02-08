<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the parent entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ValidateParentEntityObjectAccess implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private string $permission;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        string $permission
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        $isGranted = true;
        $parentEntity = $context->getParentEntity();
        if ($parentEntity && $this->isParentEntityShouldBeChecked($context)) {
            $isGranted = $this->authorizationChecker->isGranted($this->permission, $parentEntity);
        }

        if (!$isGranted) {
            throw new AccessDeniedException(sprintf(
                'No access by "%s" permission to the parent entity.',
                $this->permission
            ));
        }
    }

    private function isParentEntityShouldBeChecked(ChangeRelationshipContext $context): bool
    {
        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            return true;
        }

        return false === $parentConfig->hasAclResource() || null !== $parentConfig->getAclResource();
    }
}
