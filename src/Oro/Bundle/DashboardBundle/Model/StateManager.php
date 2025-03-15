<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Manages WidgetState entity in scope of current user.
 */
class StateManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenAccessorInterface $tokenAccessor
    ) {
    }

    public function getWidgetState(Widget $widget): WidgetState
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            $state = new WidgetStateNullObject();
            $state->setWidget($widget);

            return $state;
        }

        $state = $this->entityManager->getRepository(WidgetState::class)
            ->findOneBy(['owner' => $user, 'widget' => $widget]);
        if (null === $state) {
            $state = new WidgetState();
            $state->setOwner($user);
            $state->setWidget($widget);
            $this->entityManager->persist($state);
        }

        return $state;
    }
}
