<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Install;

use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroAdministration;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroConfiguration;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroFinish;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroInstall;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroRequirements;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class InstallTest extends Selenium2TestCase
{
    const URL = '/install.php';

    protected function setUp()
    {
        parent::setUp();
        //to prevent timeout exception on step pages
        // todo: Remove x2 multipler when CRM-7303 will resolved
        $this->setSeleniumServerRequestsTimeout((int)(MAX_EXECUTION_TIME / 1000 * 2));
    }

    public function testInstallation()
    {
        /** @var OroInstall $installer */
        $installer = new OroInstall($this);
        /** @var OroRequirements $requirements */
        $requirements = $installer->beginInstallation();
        $requirements->assertMandatoryRequirements()
                    ->assertOroRequirements()
                    ->assertPhpSettings();

        /** @var OroConfiguration $configuration */
        $configuration = $requirements->next();
        $configuration->setUser('mysql');
        $configuration->setPassword('mysql');

        /** @var OroAdministration $administration */
        $administration = $configuration->next();
        $administration->setUsername('admin')
                    ->setPasswordFirst('admin1Q8')
                    ->setPasswordSecond('admin1Q8')
                    ->setEmail('admin@example.com')
                    ->setFirstName('John')
                    ->setLastName('Doe')
                    ->setLoadFixtures();

        /** @var OroFinish $finish */
        $finish =   $administration->next();
        $finish->lunch();
    }
}
