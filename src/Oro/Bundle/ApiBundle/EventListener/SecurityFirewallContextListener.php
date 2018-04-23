<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class SecurityFirewallContextListener implements ListenerInterface
{
    /** @var ListenerInterface */
    protected $innerListener;

    /** @var array */
    protected $sessionOptions;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

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
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        // in case if has no token or the token is an anonymous one
        // and the current request is AJAX -
        // give additional chance to authorise user from session context
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
            $request->cookies->has($this->sessionOptions['name'])
            && $request->headers->has('X-CSRF-Header');
    }
}
