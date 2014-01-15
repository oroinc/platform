<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Install;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroAdministration;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroConfiguration;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroFinish;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroInstall;
use Oro\Bundle\InstallerBundle\Tests\Selenium\Pages\OroRequirements;

class InstallTest extends Selenium2TestCase
{
    protected function setUp()
    {
        parent::setUp();
        //to prevent timeout exception on step pages
        $this->setSeleniumServerRequestsTimeout(MAX_EXECUTION_TIME);
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
                    ->setPasswordFirst('admin')
                    ->setPasswordSecond('admin')
                    ->setEmail('admin@example.com')
                    ->setFirstName('John')
                    ->setlastName('Doe')
                    ->setLoadFixtures();

        /** @var OroFinish $finish */
        $finish =   $administration->next();
        $finish->lunch();
    }
}
