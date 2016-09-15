<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class SecurityFirewallContextListener implements ListenerInterface
{
    /** @var ListenerInterface */
    protected $innerListener;

    /** @var array */
    protected $sessionOptions;

    /**
     * @param ListenerInterface $innerListener
     * @param array            $sessionOptions
     */
    public function __construct(ListenerInterface $innerListener, array $sessionOptions)
    {
        $this->innerListener = $innerListener;
        $this->sessionOptions = $sessionOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        if ($event->getRequest()->cookies->has($this->sessionOptions['name'])) {
            $this->innerListener->handle($event);
        }
    }
}
