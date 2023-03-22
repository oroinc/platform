<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * It is used to disable and enable optional listener to not have new rows in audit table after fixtures loaded.
 * Only for the test environment
 */
class DataFixturesExecutingListener
{
    private const AUDIT_LISTENER_SERVICE_ID = 'oro_dataaudit.listener.send_changed_entities_to_message_queue';

    private OptionalListenerManager $listenerManager;

    private ApplicationState $applicationState;

    public function __construct(OptionalListenerManager $listenerManager, ApplicationState $applicationState)
    {
        $this->listenerManager = $listenerManager;
        $this->applicationState = $applicationState;
    }

    public function onPreLoad(MigrationDataFixturesEvent $event): void
    {
        if (!$this->applicationState->isInstalled()) {
            return;
        }

        $this->listenerManager->disableListener(
            self::AUDIT_LISTENER_SERVICE_ID
        );
    }

    public function onPostLoad(MigrationDataFixturesEvent $event): void
    {
        if (!$this->applicationState->isInstalled()) {
            return;
        }

        $this->listenerManager->enableListener(
            self::AUDIT_LISTENER_SERVICE_ID
        );
    }
}
