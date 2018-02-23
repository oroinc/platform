<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class StateManager
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param EntityManager          $em
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        EntityManager $em,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->entityManager = $em;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param Widget $widget
     * @return WidgetState
     */
    public function getWidgetState(Widget $widget)
    {
        $user = $this->tokenAccessor->getUser();

        if (!$user instanceof User) {
            $state = new WidgetStateNullObject();
            $state->setWidget($widget);
            return $state;
        }

        $state = $this->entityManager
            ->getRepository('OroDashboardBundle:WidgetState')
            ->findOneBy(
                [
                    'owner'  => $user,
                    'widget' => $widget
                ]
            );

        if (!$state) {
            $state = $this->createWidgetState($widget, $user);
        }

        return $state;
    }

    /**
     * @param Widget $widget
     * @param User $user
     * @return WidgetState
     */
    protected function createWidgetState(Widget $widget, User $user)
    {
        $state = new WidgetState();
        $state
            ->setOwner($user)
            ->setWidget($widget);

        $this->entityManager->persist($state);

        return $state;
    }
}
