<?php

namespace Oro\Bundle\InstallerBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class OroAdministration extends AbstractPage
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $companyShort;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $company;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $username;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $passwordFirst;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $passwordSecond;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $email;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $firstName;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $lastName;

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $loadFixtures;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);

        $this->organization = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_organization_name']");
        $this->username = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_username']");
        $this->passwordFirst = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_plainPassword_first']");
        $this->passwordSecond = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_plainPassword_second']");
        $this->email = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_email']");
        $this->firstName = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_firstName']");
        $this->lastName = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_lastName']");
        $this->loadFixtures = $this->test->byXPath("//*[@data-ftid='oro_installer_setup_loadFixtures']");
    }

    public function next()
    {
        $this->test->moveto($this->test->byXPath("//button[@class = 'primary button icon-settings']"));
        $this->test->byXPath("//button[@class = 'primary button icon-settings']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Installation - Oro Application installation');
        //waiting
        $startTime = microtime(true);
        do {
            sleep(5);
            $endTime = microtime(true);
            $this->test->assertLessThanOrEqual(
                (int)(MAX_EXECUTION_TIME / 1000),
                $endTime-$startTime,
                "Maximal time execution is exceeded"
            );
        } while ($this->isElementPresent("//a[@class = 'button next primary disabled']"));

        $this->test->moveto($this->test->byXPath("//a[@class = 'button next primary']"));
        $this->test->byXPath("//a[@class = 'button next primary']")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Finish - Oro Application installation');

        return new OroFinish($this->test);
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
