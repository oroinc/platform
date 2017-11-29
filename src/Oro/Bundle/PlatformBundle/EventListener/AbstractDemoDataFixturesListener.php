<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class AbstractDemoDataFixturesListener
{
    const LISTENERS = [];

    /** @var OptionalListenerManager */
    protected $listenerManager;

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
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->disableListeners(static::LISTENERS);
        $this->onPreLoadActions($event);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function onPreLoadActions(MigrationDataFixturesEvent $event)
    {
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->enableListeners(static::LISTENERS);
        $this->onPostLoadActions($event);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function onPostLoadActions(MigrationDataFixturesEvent $event)
    {
    }
}
