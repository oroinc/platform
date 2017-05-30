<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables data audit during loading of demo data.
 */
class DemoDataFixturesListener
{
    /**
     * This listener is disabled to disable data audit of the loading data
     */
    const DATA_COLLECTOR_LISTENER = 'oro_dataaudit.listener.send_changed_entities_to_message_queue';

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
            $this->listenerManager->disableListener(self::DATA_COLLECTOR_LISTENER);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->enableListener(self::DATA_COLLECTOR_LISTENER);
        }
    }
}
