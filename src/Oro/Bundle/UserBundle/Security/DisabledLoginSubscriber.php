<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\CredentialsResetException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Stop user from accessing the system if they are deactivated
 */
class DisabledLoginSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage  = false;

    /** @var array Disallowed auth statuses */
    protected static $disallowed = [
        UserManager::STATUS_EXPIRED,
    ];

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        // allow `null` or statues that are not included in `self::$disallowed`
        $isAllowed = $user->getAuthStatus() ? !in_array($user->getAuthStatus()->getId(), self::$disallowed) : true;

        if ($isAllowed) {
            return;
        }

        $this->tokenStorage->setToken(null);
        $exception = new CredentialsResetException('Password reset.');
        $exception->setUser($user);

        $request = $event->getRequest();

        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }
    }
}
