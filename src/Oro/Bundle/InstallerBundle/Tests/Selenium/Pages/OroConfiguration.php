<?php

namespace Oro\Bundle\InstallerBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class OroConfiguration
 *
 * @package Oro\Bundle\InstallerBundle\Tests\Selenium\Pages
 */
class OroConfiguration extends AbstractPage
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
        $this->host = $this->test->byId('oro_installer_configuration_database_oro_installer_database_host');
        $this->port = $this->test->byId('oro_installer_configuration_database_oro_installer_database_port');
        $this->password = $this->test->byId('oro_installer_configuration_database_oro_installer_database_password');
        $this->user = $this->test->byId('oro_installer_configuration_database_oro_installer_database_user');
        $this->database = $this->test->byId('oro_installer_configuration_database_oro_installer_database_name');
    }

    public function next()
    {
        $this->test->moveto($this->test->byXpath("//button[@class = 'primary button next']"));
        $this->test->byXpath("//button[@class = 'primary button next']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Database initialization - Oro Application installation');
        $s = microtime(true);
        do {
            sleep(5);
            $this->waitPageToLoad();
            $e = microtime(true);
            $this->test->assertTrue(($e-$s) <= MAX_EXECUTION_TIME);
        } while ($this->isElementPresent("//a[@class = 'button next primary disabled']"));

        $this->test->moveto($this->test->byXpath("//a[@class = 'button next primary']"));
        $this->test->byXpath("//a[@class = 'button next primary']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Administration setup - Oro Application installation');
        return new OroAdministration($this);
    }

    public function setPassword($value)
    {
        $this->password->clear();
        $this->password->value($value);
        return $this;
    }

    public function setUser($value)
    {
        $this->user->clear();
        $this->user->value($value);
        return $this;
    }

    public function setDatabase($value)
    {
        $this->database->clear();
        $this->database->value($value);
        return $this;
    }
}
