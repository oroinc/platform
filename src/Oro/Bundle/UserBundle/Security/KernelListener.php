<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;

class KernelListener implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var TokenStorage */
    protected $tokenStorage  = false;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $storage = $this->getTokenStorage();

        if (null === $storage) {
            return;
        }

        /** @var TokenInterface $token */
        $token = $storage->getToken();

        if (!$token) {
            return;
        }

        $user = $token->getUser();

        if ($user && $user instanceof User && $user->isLoginDisabled()) {
            $storage->setToken(null);
            $exception = new PasswordChangedException('Invalid password.');
            $exception->setUser($user);
            /** @var Request $request */
            $request = $this->container->get('request_stack')->getCurrentRequest();

            if ($request->getSession()) {
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }
        }
    }

    /**
     * @return TokenStorage
     */
    protected function getTokenStorage()
    {
        if (false === $this->tokenStorage) {
            $this->tokenStorage = $this->container->get('security.token_storage');
        }

        return $this->tokenStorage;
    }
}
