<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages\Objects;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractEntity;
use Oro\Bundle\TestFrameworkBundle\Pages\Entity;
use Oro\Bundle\TestFrameworkBundle\Pages\Page;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;

class OroAdministration extends Page
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);

        $this->companyShort = $this->test->byId("oro_installer_setup_company_name");
        $this->company = $this->test->byId("oro_installer_setup_company_title");
        $this->username = $this->test->byId("oro_installer_setup_username");
        $this->passwordFirst = $this->test->byId("oro_installer_setup_plainPassword_first");
        $this->passwordSecond = $this->test->byId("oro_installer_setup_plainPassword_second");
        $this->email = $this->test->byId("oro_installer_setup_email");
        $this->firstName = $this->test->byId("oro_installer_setup_firstName");
        $this->lastName = $this->test->byId("oro_installer_setup_lastName");
        $this->loadFixtures = $this->test->byId("oro_installer_setup_loadFixtures");
    }

    public function next()
    {
        $this->test->moveto($this->byXpath("//button[@class = 'primary button icon-settings']"));
        $this->test->byXpath("//button[@class = 'primary button icon-settings']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Installation - Oro Application installation');
        //waiting
        $s = microtime(true);
        do {
            sleep(5);
            $this->waitPageToLoad();
            $e = microtime(true);
            $this->test->assertTrue(($e-$s) <= MAX_EXECUTION_TIME);
        } while ($this->isElementPresent("//a[@class = 'button next primary disabled']"));

        $this->test->moveto($this->byXpath("//a[@class = 'button next primary']"));
        $this->test->byXpath("//a[@class = 'button next primary']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Finish - Oro Application installation');
        return new OroFinish($this);


    }

    public function setPasswordFirst($value)
    {
        $this->passwordFirst->clear();
        $this->passwordFirst->value($value);
        return $this;
    }

    public function setPasswordSecond($value)
    {
        $this->passwordSecond->clear();
        $this->passwordSecond->value($value);
        return $this;
    }

    public function setUsername($value)
    {
        $this->username->clear();
        $this->username->value($value);
        return $this;
    }

    public function setFirstName($value)
    {
        $this->firstName->clear();
        $this->firstName->value($value);
        return $this;
    }

    public function setLastName($value)
    {
        $this->lastName->clear();
        $this->lastName->value($value);
        return $this;
    }

    public function setEmail($value)
    {
        $this->email->clear();
        $this->email->value($value);
        return $this;
    }

    public function setLoadFixtures()
    {
        $this->loadFixtures->click();
        return $this;
    }
}
