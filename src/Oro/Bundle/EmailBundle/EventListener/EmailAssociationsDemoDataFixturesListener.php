<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables updating email associations during loading of demo data
 * and triggers it after demo data are loaded.
 */
class EmailAssociationsDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param AssociationManager $associationManager
     */
    public function __construct(OptionalListenerManager $listenerManager, AssociationManager $associationManager)
    {
        parent::__construct($listenerManager);

        $this->associationManager = $associationManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
        $event->log('updating email owners');

        $this->associationManager->setQueued(false);
        $this->associationManager->processUpdateAllEmailOwners();
        $this->associationManager->setQueued(true);
    }
}
