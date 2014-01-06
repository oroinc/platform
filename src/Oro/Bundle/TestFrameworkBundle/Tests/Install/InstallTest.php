<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Install;

use Oro\Bundle\TestFrameworkBundle\Pages\Objects\Login;
use Oro\Bundle\TestFrameworkBundle\Pages\Objects\OroAdministration;
use Oro\Bundle\TestFrameworkBundle\Pages\Objects\OroConfiguration;
use Oro\Bundle\TestFrameworkBundle\Pages\Objects\OroFinish;
use Oro\Bundle\TestFrameworkBundle\Pages\Objects\OroInstall;
use Oro\Bundle\TestFrameworkBundle\Pages\Objects\OroRequirements;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class InstallTest extends Selenium2TestCase
{
    protected function setUp()
    {
        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort(intval(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT));
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
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
