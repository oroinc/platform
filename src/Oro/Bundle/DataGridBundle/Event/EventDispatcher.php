<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcher extends ImmutableEventDispatcher
{
    /**
     * @param GridEventInterface|GridConfigurationEventInterface|Event $event
     * @param string|null $eventName
     *
     * @return Event
     * @throws InvalidArgumentException
     */
    public function dispatch($event/*, string $eventName = null*/)
    {
        $eventName = 1 < \func_num_args() ? func_get_arg(1) : null;

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

        parent::dispatch($event, $eventName);
        /*
         * Dispatch named events in reverse order from parent to child
         * e.g. parent1 -> parent2 -> target grid
         */
        foreach ($invokedGrids as $grid) {
            parent::dispatch($event, $eventName . '.' . $grid);
        }

        return $event;
    }
}
