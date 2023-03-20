<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Functional\Command;

use Oro\Bundle\MaintenanceBundle\Command\MaintenanceLockCommand;
use Oro\Bundle\MaintenanceBundle\Command\MaintenanceUnlockCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class MaintenanceLockUnlockCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testMaintenanceLock(): void
    {
        $this->assertResponseCode(200);

        $commandTester = $this->doExecuteCommand(MaintenanceLockCommand::getDefaultName());

        $this->assertOutputContains($commandTester, 'Maintenance mode is turned on.');
        $this->assertResponseCode(503);
    }

    /**
     * @depends testMaintenanceLock
     */
    public function testMaintenanceUnlock(): void
    {
        $commandTester = $this->doExecuteCommand(MaintenanceUnlockCommand::getDefaultName());

        $this->assertOutputContains($commandTester, 'Maintenance mode is turned off.');
        $this->assertResponseCode(200);
    }

    private function assertResponseCode(int $code): void
    {
        $this->client->request('GET', $this->getUrl('oro_user_security_login'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, $code);
    }
}
