<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\CredentialsResetException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Stop user from accessing the system if they are deactivated
 */
class DisabledLoginSubscriber implements EventSubscriberInterface
{
    protected TokenStorageInterface|bool $tokenStorage = false;

    /** @var array Disallowed auth statuses */
    protected static array $disallowed = [
        UserManager::STATUS_RESET,
    ];

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
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
