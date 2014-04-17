<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\UserBundle\Entity\User;

class StateManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param EntityManager            $em
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(
        EntityManager $em,
        SecurityContextInterface $securityContext
    ) {
        $this->em              = $em;
        $this->securityContext = $securityContext;
    }

    /**
     * @param Widget $widget
     * @return WidgetState
     */
    public function getWidgetState(Widget $widget)
    {
        $state = $this->em
            ->getRepository('OroDashboardBundle:WidgetState')
            ->findOneBy(
                [
                    'owner'  => $this->getUser(),
                    'widget' => $widget
                ]
            );

        if (!$state) {
            $state = $this->createWidgetState($widget);
        }

        return $state;
    }

    /**
     * @param Widget $widget
     * @return WidgetState
     */
    protected function createWidgetState(Widget $widget)
    {
        $state = new WidgetState();
        $state
            ->setOwner($this->getUser())
            ->setWidget($widget)
            ->setExpanded($widget->isExpanded())
            ->setLayoutPosition($widget->getLayoutPosition());

        $this->em->persist($state);

        return $state;
    }

    /**
     * Get the current authenticated user
     *
     * @return UserInterface|User|null
     * @throws InvalidArgumentException
     */
    protected function getUser()
    {
        $token = $this->securityContext->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                return $user;
            }
        }

        throw new InvalidArgumentException(
            'User not logged'
        );
    }
} 
