<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;

/**
 * Enables maintenance mode after start isolators and disables it before isolators after tests
 */
class EnableMaintenanceModeIsolator extends MaintenanceModeIsolator
{
    #[\Override]
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<comment>Enabling maintenance mode</comment>');
        $this->runCommand('oro:maintenance:lock');
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<comment>Disabling maintenance mode</comment>');
        $this->runCommand('oro:maintenance:unlock');
    }

    #[\Override]
    public function getName()
    {
        return 'EnableMaintenanceMode';
    }

    #[\Override]
    public function getTag()
    {
        return 'enable_maintenance_mode';
    }
}
