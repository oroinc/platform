<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\DataAuditBundle\Loggable\LoggableManager;

class KernelListener implements EventSubscriberInterface
{
    /** @var TokenStorage */
    private $tokenStorage = false;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker = false;

    /** @var LoggableManager */
    private $loggableManager = false;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        $token = $storage->getToken();
        if (null !== $token && $this->getAuthorizationChecker()->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->getLoggableManager()->setUsername($token);
        }
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
     * @return TokenStorage
     */
    protected function getTokenStorage()
    {
        if ($this->tokenStorage === false) {
            $this->tokenStorage = $this->container->get('security.token_storage');
        }

        return $this->tokenStorage;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        if ($this->authorizationChecker === false) {
            $this->authorizationChecker = $this->container->get('security.authorization_checker');
        }

        return $this->authorizationChecker;
    }

    /**
     * @return LoggableManager
     */
    protected function getLoggableManager()
    {
        if ($this->loggableManager === false) {
            $this->loggableManager = $this->container->get('oro_dataaudit.loggable.loggable_manager');
        }

        return $this->loggableManager;
    }
}
