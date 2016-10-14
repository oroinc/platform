<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsSubscriber implements EventSubscriberInterface
{
    /** @var LoginAttemptsProvider */
    protected $loginAttemptsProvider;

    /** @var BaseUserManager */
    protected $userManager;

    /** @var Processor */
    protected $mailProcessor;

    /**
     * @param LoginAttemptsProvider $loginAttemptsProvider
     * @param BaseUserManager $userManager
     * @param Processor $mailProcessor
     */
    public function __construct(
        LoginAttemptsProvider $loginAttemptsProvider,
        BaseUserManager $userManager,
        Processor $mailProcessor
    ) {
        $this->loginAttemptsProvider = $loginAttemptsProvider;
        $this->userManager = $userManager;
        $this->mailProcessor = $mailProcessor;
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
        if (!$user) {
            return;
        }

        $this->trackLoginFailure($user);
    }

    /**
     * @param  InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->resetFailedLoginCounters($event->getAuthenticationToken()->getUser());
    }

    /**
     * @param  UserInterface $user
     */
    protected function resetFailedLoginCounters(UserInterface $user)
    {
        $user->setFailedLoginCount(0);
        $user->setDailyFailedLoginCount(0);
        $this->userManager->updateUser($user);
    }

    /**
     * Update login counter and deactivate the user when limits are exceeded
     *
     * @param  UserInterface $user
     */
    protected function trackLoginFailure(UserInterface $user)
    {
        $user->setFailedLoginCount($user->getFailedLoginCount() + 1);
        $user->setDailyFailedLoginCount($user->getDailyFailedLoginCount() + 1);

        if (!$this->loginAttemptsProvider->hasRemainingAttempts($user)) {
            $this->deactivateUser($user);
        }

        $this->userManager->updateUser($user);
    }

    /**
     * Disable/Deactivate an user and sends notification email to them and to administrators
     *
     * @param  UserInterface $user
     */
    protected function deactivateUser(UserInterface $user)
    {
        $user->setEnabled(false);

        if ($this->loginAttemptsProvider->getRemainingCumulativeLoginAttempts($user) <= 0) {
            $this->mailProcessor->sendAutoDeactivateEmail(
                $user,
                $this->loginAttemptsProvider->getMaxCumulativeLoginAttempts($user)
            );

            return;
        }

        if ($this->loginAttemptsProvider->getRemainingDailyLoginAttempts($user) <= 0) {
            $this->mailProcessor->sendAutoDeactivateDailyEmail(
                $user,
                $this->loginAttemptsProvider->getMaxDailyLoginAttempts($user)
            );

            return;
        }
    }
}
