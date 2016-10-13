<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;

class DisabledLoginSubscriber implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var TokenStorageInterface */
    protected $tokenStorage  = false;

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
        /** @var TokenInterface $token */
        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        $user = $token->getUser();

        if ($user instanceof User && $user->isLoginDisabled()) {
            $this->tokenStorage->setToken(null);
            $exception = new PasswordChangedException('Invalid password.');
            $exception->setUser($user);
            /** @var Request $request */
            $request = $event->getRequest();

            if ($request->hasSession()) {
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }
        }
    }
}
