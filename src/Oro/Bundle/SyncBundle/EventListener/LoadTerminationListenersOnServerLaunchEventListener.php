<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Loads termination listeners on server launch to avoid lazy loading as it causes fatal errors when
 * application cache is deleted manually (i.e. rm -rf var/cache/*).
 */
class LoadTerminationListenersOnServerLaunchEventListener
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function onServerLaunched(ServerLaunchedEvent $serverLaunchedEvent): void
    {
        $this->eventDispatcher->getListeners(ConsoleEvents::TERMINATE);
    }
}
