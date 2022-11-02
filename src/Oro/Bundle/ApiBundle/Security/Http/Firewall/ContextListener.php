<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Gives an additional chance to authorise user from the session context if
 * the current request is AJAX request (has valid "X-CSRF-Header" header)
 * and it has session identifier in cookies.
 * It is required because API can work in two modes, stateless and stateful.
 * The stateful mode is used when API is called internally from web pages as AJAX request.
 */
class ContextListener
{
    private object $innerListener;
    private TokenStorageInterface $tokenStorage;
    private CsrfRequestManager $csrfRequestManager;
    private CsrfProtectedRequestHelper $csrfProtectedRequestHelper;

    public function __construct(
        object $innerListener,
        TokenStorageInterface $tokenStorage
    ) {
        $this->innerListener = $innerListener;
        $this->tokenStorage = $tokenStorage;
    }

    public function setCsrfRequestManager(CsrfRequestManager $csrfRequestManager): void
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    public function setCsrfProtectedRequestHelper(CsrfProtectedRequestHelper $csrfProtectedRequestHelper): void
    {
        $this->csrfProtectedRequestHelper = $csrfProtectedRequestHelper;
    }

    public function __invoke(RequestEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            if ($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($event->getRequest())) {
                $this->processEvent($event);
            }
        } elseif ($token instanceof AnonymousToken) {
            if ($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($event->getRequest())) {
                $this->processEvent($event);
                if (null === $this->tokenStorage->getToken()) {
                    $this->tokenStorage->setToken($token);
                }
            }
        }
    }

    private function processEvent(RequestEvent $event): void
    {
        ($this->innerListener)($event);
        $this->csrfRequestManager->refreshRequestToken();
    }
}
