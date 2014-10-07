<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;

abstract class AbstractImportStrategy implements StrategyInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param object $entity
     * @return object
     */
    protected function beforeProcessEntity($entity)
    {
        $event = new StrategyEvent($this, $entity);
        $this->eventDispatcher->dispatch(StrategyEvent::PROCESS_BEFORE, $event);
        return $event->getEntity();
    }

    /**
     * @param object $entity
     * @return object
     */
    protected function afterProcessEntity($entity)
    {
        $event = new StrategyEvent($this, $entity);
        $this->eventDispatcher->dispatch(StrategyEvent::PROCESS_AFTER, $event);
        return $event->getEntity();
    }
}
