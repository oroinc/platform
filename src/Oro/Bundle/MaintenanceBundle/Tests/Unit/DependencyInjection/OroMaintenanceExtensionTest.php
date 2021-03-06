<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MaintenanceBundle\Command\MaintenanceLockCommand;
use Oro\Bundle\MaintenanceBundle\Command\MaintenanceUnlockCommand;
use Oro\Bundle\MaintenanceBundle\DependencyInjection\OroMaintenanceExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroMaintenanceExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroMaintenanceExtension());

        $expectedDefinitions = [
            'oro_maintenance.driver.factory',
            'oro_maintenance.maintenance_listener',
            MaintenanceLockCommand::class,
            MaintenanceUnlockCommand::class,
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [
            'oro_maintenance.driver',
            'oro_maintenance.authorized.path',
            'oro_maintenance.authorized.host',
            'oro_maintenance.authorized.ips',
            'oro_maintenance.authorized.query',
            'oro_maintenance.authorized.cookie',
            'oro_maintenance.authorized.route',
            'oro_maintenance.authorized.attributes',
            'oro_maintenance.response.http_code',
            'oro_maintenance.response.http_status',
            'oro_maintenance.response.exception_message',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
