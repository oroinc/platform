<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;

/**
 * Abstract class for the listeners that can disable some event listeners during installation demo fixtures
 */
class AbstractDemoDataFixturesListener extends AbstractDataFixturesListener
{
    /**
     * {@inheritdoc}
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            parent::onPreLoad($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            parent::onPostLoad($event);
        }
    }
}
