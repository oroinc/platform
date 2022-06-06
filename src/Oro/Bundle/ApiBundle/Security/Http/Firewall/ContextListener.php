<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
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

    public function __invoke(RequestEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            if ($this->isAjaxRequest($event->getRequest())) {
                $this->processEvent($event);
            }
        } elseif ($token instanceof AnonymousToken) {
            if ($this->isAjaxRequest($event->getRequest())) {
                $this->processEvent($event);
                if (null === $this->tokenStorage->getToken()) {
                    $this->tokenStorage->setToken($token);
                }
            }
        }
    }

    /**
     * Checks whether the request is AJAX request
     * (cookies has the session cookie and the request has "X-CSRF-Header" header with valid CSRF token).
     */
    private function isAjaxRequest(Request $request): bool
    {
        $isGetRequest = $request->isMethod('GET');

        return
            $request->hasSession()
            && $request->cookies->has($request->getSession()->getName())
            && (
                (!$isGetRequest && $this->csrfRequestManager->isRequestTokenValid($request))
                || ($isGetRequest && $request->headers->has(CsrfRequestManager::CSRF_HEADER))
            );
    }

    protected function processEvent(RequestEvent $event): void
    {
        ($this->innerListener)($event);
        $this->csrfRequestManager->refreshRequestToken();
    }
}
