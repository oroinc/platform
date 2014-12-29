<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;

class EventDispatcher extends ImmutableEventDispatcher
{
    /**
     * @param string $eventName
     * @param GridEventInterface|GridConfigurationEventInterface|Event $event
     *
     * @return Event
     * @throws InvalidArgumentException
     */
    public function dispatch($eventName, Event $event = null)
    {
        /** @var DatagridConfiguration $config */
        if ($event instanceof GridEventInterface) {
            $config = $event->getDatagrid()->getConfig();
        } elseif ($event instanceof GridConfigurationEventInterface) {
            $config = $event->getConfig();
        } else {
            throw new InvalidArgumentException(
                'Unexpected event type. Expected instance of GridEventInterface or GridConfigurationEventInterface'
            );
        }

        // get all parents
        $invokedGrids   = $config->offsetGetOr(SystemAwareResolver::KEY_EXTENDED_FROM, []);
        $invokedGrids[] = $config->getName();

        parent::dispatch($eventName, $event);
        /*
         * Dispatch named events in reverse order from parent to child
         * e.g. parent1 -> parent2 -> target grid
         */
        foreach ($invokedGrids as $grid) {
            parent::dispatch($eventName . '.' . $grid, $event);
        }

        return $event;
    }
}
