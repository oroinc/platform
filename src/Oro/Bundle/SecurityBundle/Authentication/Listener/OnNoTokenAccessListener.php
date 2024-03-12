<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Authentication\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Http\Event\LazyResponseEvent;
use Symfony\Component\Security\Http\Firewall\AbstractListener;

/**
 * Check access if the token is missing.
 */
class OnNoTokenAccessListener extends AbstractListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $accessAttributes = (array)$request->attributes->get('_access_control_attributes');

        return !\in_array(AuthenticatedVoter::PUBLIC_ACCESS, $accessAttributes)
            && null === $this->tokenStorage->getToken();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestEvent $event): void
    {
        if (!$event instanceof LazyResponseEvent) {
            throw new AuthenticationCredentialsNotFoundException('A Token was not found in the TokenStorage.');
        }
    }

    public static function getPriority(): int
    {
        return -200; // before Symfony\Component\Security\Http\Firewall\AccessListener
    }
}
