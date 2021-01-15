<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Environment;

use Doctrine\Common\Cache\Cache;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager as BaseNotificationManager;

class NotificationManager extends BaseNotificationManager
{
    /** @var iterable|EventHandlerInterface[] */
    private $handlers;

    /**
     * @param iterable|EventHandlerInterface[] $handlers
     * @param Cache                            $cache
     * @param ManagerRegistry                  $doctrine
     */
    public function __construct(iterable $handlers, Cache $cache, ManagerRegistry $doctrine)
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
