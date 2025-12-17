<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * User entity placeholder filter.
 */
class PlaceholderFilter
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Checks if password management is available
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
    {
        if ($entity instanceof User &&
            $entity->getAuthStatus() &&
            $entity->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE
        ) {
            return false;
        }

        if (!$entity instanceof User || !$entity->isEnabled()) {
            return false;
        }

        // Dispatch event to allow extensions to prevent password change
        $event = new PasswordChangeEvent($entity);
        $this->eventDispatcher?->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_CHANGE);

        return $event->isAllowed();
    }

    /**
     * Checks if password can be reset
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isPasswordResetEnabled($entity)
    {
        if (!$entity instanceof User
            || !$entity->isEnabled()
            || $this->tokenAccessor->getUserId() === $entity->getId()
        ) {
            return false;
        }

        // Dispatch event to allow extensions to prevent password reset
        $event = new PasswordChangeEvent($entity);
        $this->eventDispatcher?->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_RESET);

        return $event->isAllowed();
    }

    /**
     * @return bool
     */
    public function isUserApplicable()
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }
}
