<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;

/**
 * Disables maintenance mode after start isolators and enables it before isolators after tests
 */
class DisableMaintenanceModeIsolator extends MaintenanceModeIsolator
{
    /**
     * @inheritDoc
     */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<comment>Disabling maintenance mode.</comment>');
        $this->runCommand('oro:maintenance:unlock');
    }

    /**
     * @inheritDoc
     */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<comment>Enabling maintenance mode.</comment>');
        $this->runCommand('oro:maintenance:lock');
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'DisableMaintenanceMode';
    }

    /**
     * @inheritDoc
     */
    public function getTag()
    {
        return 'disable_maintenance_mode';
    }
}
