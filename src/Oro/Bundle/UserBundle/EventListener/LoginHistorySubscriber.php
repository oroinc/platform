<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginHistoryManager;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginHistorySubscriber implements EventSubscriberInterface
{
    /** @var LoginHistoryManager $loginHistoryManager */
    protected $loginHistoryManager;

    /** @var LoginAttemptsProvider */
    protected $loginAttemptsProvider;

    /** @var BaseUserManager */
    protected $userManager;

    /** @var Processor */
    protected $mailProcessor;

    /**
     * @param LoginHistoryManager $loginHistoryManager
     * @param LoginAttemptsProvider $loginAttemptsProvider
     * @param BaseUserManager $userManager
     */
    public function __construct(
        LoginHistoryManager $loginHistoryManager,
        LoginAttemptsProvider $loginAttemptsProvider,
        BaseUserManager $userManager,
        Processor $mailProcessor
    ) {
        $this->loginHistoryManager = $loginHistoryManager;
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
        );
    }
 
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUser();
        $user = $this->userManager->findUserByUsernameOrEmail($username);
        if (!$user) {
            return;
        }

        $this->loginHistoryManager->logUserLogin($user, false);
        $remainingAttempts = $this->loginAttemptsProvider->getByUser($user);

        if (0 === $remainingAttempts) {
            $user->setEnabled(false);
            $this->userManager->updateUser($user);
            $this->mailProcessor->sendAutoDeactivateEmail($user);
        }
    }
}
