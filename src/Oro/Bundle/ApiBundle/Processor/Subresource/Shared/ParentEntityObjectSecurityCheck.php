<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the parent entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ParentEntityObjectSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var string */
    protected $permission;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string                        $permission
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        $permission
    ) {
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
        if ($parentEntity) {
            $isGranted = $this->authorizationChecker->isGranted($this->permission, $parentEntity);
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
