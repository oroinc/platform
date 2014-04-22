<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\UserBundle\Entity\User;

class StateManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param EntityManager $em
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        EntityManager $em,
        SecurityFacade $securityFacade
    ) {
        $this->entityManager = $em;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param Widget $widget
     * @return WidgetState
     */
    public function getWidgetState(Widget $widget)
    {
        $user = $this->securityFacade->getLoggedUser();

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
