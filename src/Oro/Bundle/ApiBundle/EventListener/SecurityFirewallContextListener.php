<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Give an additional chance to authorise user from session context if
 * the current request is AJAX request (has "X-CSRF-Header" header)
 * and it has session identifier in cookies.
 * This is required because API can work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class SecurityFirewallContextListener implements ListenerInterface
{
    /** @var ListenerInterface */
    private $innerListener;

    /** @var array */
    private $sessionName;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param ListenerInterface     $innerListener
     * @param string                $sessionName
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        ListenerInterface $innerListener,
        string $sessionName,
        TokenStorageInterface $tokenStorage
    ) {
        $this->innerListener = $innerListener;
        $this->sessionName = $sessionName;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            if ($this->isAjaxRequest($event->getRequest())) {
                $this->innerListener->handle($event);
            }
        } elseif ($token instanceof AnonymousToken) {
            if ($this->isAjaxRequest($event->getRequest())) {
                $this->innerListener->handle($event);
                if (null === $this->tokenStorage->getToken()) {
                    $this->tokenStorage->setToken($token);
                }
            }
        }
    }

    /**
     * Checks whether the request is AJAX request
     * (cookies has the session cookie and the request has "X-CSRF-Header" header).
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAjaxRequest(Request $request)
    {
        return
            $request->cookies->has($this->sessionName)
            && $request->headers->has('X-CSRF-Header');
    }
}
