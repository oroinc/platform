<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

class ConsoleCommandListener
{
    /** @var ResourcesCache */
    protected $resourcesCache;

    /**
     * @param ResourcesCache $resourcesCache
     */
    public function __construct(ResourcesCache $resourcesCache)
    {
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ('router:cache:clear' === $event->getCommand()->getName()) {
            $this->resourcesCache->clear();
        }
    }
}
