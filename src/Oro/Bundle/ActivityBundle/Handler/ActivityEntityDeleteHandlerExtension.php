<?php

namespace Oro\Bundle\ActivityBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The default implementation of the activity entity delete handler extension.
 */
class ActivityEntityDeleteHandlerExtension implements ActivityEntityDeleteHandlerExtensionInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var EntityDeleteAccessDeniedExceptionFactory */
    private $accessDeniedExceptionFactory;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityDeleteAccessDeniedExceptionFactory $accessDeniedExceptionFactory
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->accessDeniedExceptionFactory = $accessDeniedExceptionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity, $targetEntity): void
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw $this->accessDeniedExceptionFactory->createAccessDeniedException(
                'has no edit permissions for activity entity'
            );
        }
        if (!$this->authorizationChecker->isGranted('VIEW', $targetEntity)) {
            throw $this->accessDeniedExceptionFactory->createAccessDeniedException(
                'has no view permissions for related entity'
            );
        }
    }
}
