<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This listener turns off browser cache for every response.
 */
class TurnOffCachingListener
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof AnonymousToken) {
            return;
        }

        $response = $event->getResponse();
        if ($response->isCacheable()) {
            return;
        }

        $response->headers->set('Expires', '0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
    }
}
