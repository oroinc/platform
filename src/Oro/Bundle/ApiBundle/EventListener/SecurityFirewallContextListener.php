<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Give an additional chance to authorise user from session context if
 * the current request is AJAX request (has valid "X-CSRF-Header" header)
 * and it has session identifier in cookies.
 * This is required because API can work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class SecurityFirewallContextListener implements ListenerInterface
{
    /** @var ListenerInterface */
    private $innerListener;

    /** @var array */
    private $sessionOptions;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CsrfRequestManager */
    private $csrfRequestManager;

    /**
     * @param ListenerInterface     $innerListener
     * @param array                 $sessionOptions
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        ListenerInterface $innerListener,
        array $sessionOptions,
        TokenStorageInterface $tokenStorage
    ) {
        $this->innerListener = $innerListener;
        $this->sessionOptions = $sessionOptions;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param CsrfRequestManager $csrfRequestManager
     */
    public function setCsrfRequestManager(CsrfRequestManager $csrfRequestManager)
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            if ($this->isValidRequest($event->getRequest())) {
                $this->processEvent($event);
            }
        } elseif ($token instanceof AnonymousToken) {
            if ($this->isValidRequest($event->getRequest())) {
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
    private function isValidRequest(Request $request)
    {
        $isGetRequest = $request->isMethod('GET');

        return
            $request->cookies->has($this->sessionOptions['name'])
            && (
                (!$isGetRequest && $this->csrfRequestManager->isRequestTokenValid($request, false))
                || ($isGetRequest && $request->headers->has('X-CSRF-Header'))
            );
    }

    /**
     * @param GetResponseEvent $event
     */
    protected function processEvent(GetResponseEvent $event): void
    {
        $this->innerListener->handle($event);
        $this->csrfRequestManager->refreshRequestToken();
    }
}
