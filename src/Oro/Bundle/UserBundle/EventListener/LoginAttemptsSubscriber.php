<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\FailedLoginInfoInterface;
use Oro\Bundle\UserBundle\Security\LoginAttemptsManager;

class LoginAttemptsSubscriber implements EventSubscriberInterface
{
    /** @var BaseUserManager */
    protected $userManager;

    /** @var LoginAttemptsManager $attemptsManager */
    protected $attemptsManager;

    /**
     * @param BaseUserManager $userManager
     * @param LoginAttemptsManager $attemptsManager
     */
    public function __construct(BaseUserManager $userManager, LoginAttemptsManager $attemptsManager)
    {
        $this->userManager = $userManager;
        $this->attemptsManager = $attemptsManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        );
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUser();
        $user = $this->userManager->findUserByUsernameOrEmail($username);

        if ($user instanceof FailedLoginInfoInterface) {
            $this->attemptsManager->trackLoginFailure($user);
        }
    }

    /**
     * @param  InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof FailedLoginInfoInterface) {
            $this->attemptsManager->trackLoginSuccess($user);
        }
    }
}
