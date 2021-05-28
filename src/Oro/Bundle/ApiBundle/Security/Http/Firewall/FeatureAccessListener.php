<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Throws NotFoundHttpException if an API request is executed in a security context without a token.
 * This listener is required because 404 status code should be returned
 * instead of 401 status code if API feature is disabled.
 */
class FeatureAccessListener
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(RequestEvent $event): void
    {
        if (null === $this->tokenStorage->getToken()) {
            throw new NotFoundHttpException();
        }
    }
}
