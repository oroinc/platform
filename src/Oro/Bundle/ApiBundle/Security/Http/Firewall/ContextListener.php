<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Gives an additional chance to authorise user from the session context if
 * the current request is AJAX request (has valid "X-CSRF-Header" header)
 * and it has session identifier in cookies.
 * It is required because API can work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class ContextListener implements ListenerInterface
{
    /** @var ListenerInterface */
    private $innerListener;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var SessionInterface|null */
    private $session;

    /** @var CsrfRequestManager */
    private $csrfRequestManager;

    public function __construct(
        ListenerInterface $innerListener,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session = null
    ) {
        $this->innerListener = $innerListener;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
    }

    public function setCsrfRequestManager(CsrfRequestManager $csrfRequestManager)
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event): void
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
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAjaxRequest(Request $request)
    {
        $isGetRequest = $request->isMethod('GET');

        return
            null !== $this->session
            && $request->cookies->has($this->session->getName())
            && (
                (!$isGetRequest && $this->csrfRequestManager->isRequestTokenValid($request))
                || ($isGetRequest && $request->headers->has(CsrfRequestManager::CSRF_HEADER))
            );
    }

    protected function processEvent(GetResponseEvent $event): void
    {
        $this->innerListener->handle($event);
        $this->csrfRequestManager->refreshRequestToken();
    }
}
