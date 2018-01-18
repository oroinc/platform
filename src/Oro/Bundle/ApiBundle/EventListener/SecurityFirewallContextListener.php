<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
        $request = $event->getRequest();
        // in case if has no token and cookies has session cookie and request has X-CSRF-Header header (ajax request) -
        // give additional chance to authorise user from session context.
        if (null === $this->tokenStorage->getToken()
            && $request->cookies->has($this->sessionOptions['name'])
            && $request->headers->has('X-CSRF-Header')
        ) {
            $this->innerListener->handle($event);
        }
    }
}
