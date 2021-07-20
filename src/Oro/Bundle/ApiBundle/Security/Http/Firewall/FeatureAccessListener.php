<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Throws NotFoundHttpException if an API request is executed in a security context without a token.
 * This listener is required because 404 status code should be returned
 * instead of 401 status code if API feature is disabled.
 */
class FeatureAccessListener implements ListenerInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(GetResponseEvent $event): void
    {
        if (null === $this->tokenStorage->getToken()) {
            throw new NotFoundHttpException();
        }
    }
}
