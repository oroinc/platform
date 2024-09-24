<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;

/**
 * Disables maintenance mode after start isolators and enables it before isolators after tests
 */
class DisableMaintenanceModeIsolator extends MaintenanceModeIsolator
{
    #[\Override]
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<comment>Disabling maintenance mode.</comment>');
        $this->runCommand('oro:maintenance:unlock');
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<comment>Enabling maintenance mode.</comment>');
        $this->runCommand('oro:maintenance:lock');
    }

    #[\Override]
    public function getName()
    {
        return 'DisableMaintenanceMode';
    }

    #[\Override]
    public function getTag()
    {
        return 'disable_maintenance_mode';
    }
}
