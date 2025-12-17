<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * User entity placeholder filter.
 *
 * Usage examples:
 *
 *      applicable: "@oro_user.placeholder.filter->isPasswordManageEnabled($entity$)"
 *      applicable: "@oro_user.placeholder.filter->isPasswordResetEnabled($entity$)"
 *      applicable: "@oro_user.placeholder.filter->isUserApplicable()"
 *
 */
class PlaceholderFilter
{
    public function __construct(
        protected readonly TokenAccessorInterface $tokenAccessor,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function isPasswordManageEnabled(?object $entity): bool
    {
        if ($entity instanceof User &&
            $entity->getAuthStatus() &&
            $entity->getAuthStatus()->getInternalId() !== UserManager::STATUS_ACTIVE
        ) {
            return false;
        }

        if (!$entity instanceof User || !$entity->isEnabled()) {
            return false;
        }

        // Dispatch event to allow extensions to prevent password change
        $event = new PasswordChangeEvent($entity);
        $this->eventDispatcher->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_CHANGE);

        return $event->isAllowed();
    }

    public function isPasswordResetEnabled(?object $entity): bool
    {
        if (!$entity instanceof User
            || !$entity->isEnabled()
            || $this->tokenAccessor->getUserId() === $entity->getId()
        ) {
            return false;
        }

        // Dispatch event to allow extensions to prevent password reset
        $event = new PasswordChangeEvent($entity);
        $this->eventDispatcher->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_RESET);

        return $event->isAllowed();
    }

    public function isUserApplicable(): bool
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }
}
