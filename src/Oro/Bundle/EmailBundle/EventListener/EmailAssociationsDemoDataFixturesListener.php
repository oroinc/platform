<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables updating email associations during loading of demo data
 * and triggers it after demo data are loaded.
 */
class EmailAssociationsDemoDataFixturesListener
{
    /**
     * This listener is disabled to prevent a lot of UpdateEmailAssociations messages
     */
    const ENTITY_LISTENER = 'oro_email.listener.entity_listener';

    /** @var OptionalListenerManager */
    private $listenerManager;

    /** @var AssociationManager */
    private $associationManager;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param AssociationManager $associationManager
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        AssociationManager $associationManager
    ) {
        $this->listenerManager = $listenerManager;
        $this->associationManager = $associationManager;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->disableListener(self::ENTITY_LISTENER);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->enableListener(self::ENTITY_LISTENER);

            $event->log('updating email owners');

            $this->associationManager->setQueued(false);
            $this->associationManager->processUpdateAllEmailOwners();
            $this->associationManager->setQueued(true);
        }
    }
}
