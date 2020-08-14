<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Functional\Command;

use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class InstallCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    /**
     * If the application is already installed (and it is already installed when the functional tests are running),
     * the installer command should display an error and stop.
     */
    public function testDisplaysErrorAndTerminates()
    {
        $commandTester = $this->doExecuteCommand(InstallCommand::getDefaultName(), []);

        $this->assertProducedError($commandTester, 'application is already installed');
    }
}
