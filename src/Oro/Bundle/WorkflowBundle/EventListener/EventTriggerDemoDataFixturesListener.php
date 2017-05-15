<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables workflow events during loading of demo data.
 */
class EventTriggerDemoDataFixturesListener
{
    /**
     * This listener is disabled to prevent additional reindexing of search index
     */
    const EVENT_TRIGGER_COLLECTOR_LISTENER = 'oro_workflow.listener.event_trigger_collector';

    /** @var OptionalListenerManager */
    private $listenerManager;

    /**
     * @param OptionalListenerManager $listenerManager
     */
    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenerManager = $listenerManager;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->disableListener(self::EVENT_TRIGGER_COLLECTOR_LISTENER);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->enableListener(self::EVENT_TRIGGER_COLLECTOR_LISTENER);
        }
    }
}
