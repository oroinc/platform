<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class AbstractDemoDataFixturesListener
{
    /** @var OptionalListenerManager */
    protected $listenerManager;

    /** @var array */
    protected $listeners = [];

    /**
     * @param OptionalListenerManager $listenerManager
     */
    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenerManager = $listenerManager;
    }

    /**
     * @param string $listener
     */
    public function disableListener($listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->beforeDisableListeners($event);
        $this->listenerManager->disableListeners($this->listeners);
        $this->afterDisableListeners($event);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function beforeDisableListeners(MigrationDataFixturesEvent $event)
    {
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function afterDisableListeners(MigrationDataFixturesEvent $event)
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

        $this->beforeEnableListeners($event);
        $this->listenerManager->enableListeners($this->listeners);
        $this->afterEnableListeners($event);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function beforeEnableListeners(MigrationDataFixturesEvent $event)
    {
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
    }
}
