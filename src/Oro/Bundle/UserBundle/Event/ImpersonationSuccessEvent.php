<?php

namespace Oro\Bundle\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Allows to react on successful impersonation login
 */
class ImpersonationSuccessEvent extends Event
{
    const EVENT_NAME = 'oro_user.impersonation_success';

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @param UserInterface $user Impersonated user
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
