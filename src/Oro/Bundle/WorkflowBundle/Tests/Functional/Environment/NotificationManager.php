<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Environment;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager as BaseNotificationManager;
use Symfony\Contracts\Cache\CacheInterface;

class NotificationManager extends BaseNotificationManager
{
    /** @var iterable|EventHandlerInterface[] */
    private $handlers;

    /**
     * @param iterable|EventHandlerInterface[] $handlers
     * @param CacheInterface                   $cache
     * @param ManagerRegistry                  $doctrine
     */
    public function __construct(iterable $handlers, CacheInterface $cache, ManagerRegistry $doctrine)
    {
        parent::__construct($handlers, $cache, $doctrine);
        $this->handlers = $handlers;
    }

    /**
     * @return EventHandlerInterface[]
     */
    public function getHandlers(): array
    {
        return iterator_to_array($this->handlers);
    }
}
