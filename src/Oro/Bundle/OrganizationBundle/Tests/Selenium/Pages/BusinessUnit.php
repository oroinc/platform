<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class BusinessUnit
 *
 * @package Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 */
class BusinessUnit extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $businessUnitName;

    public function init()
    {
        $this->businessUnitName = $this->test->byXpath("//*[@data-ftid='oro_business_unit_form_name']");

        return $this;
    }

    /**
     * @param $unitName
     * @return $this
     */
    public function setBusinessUnitName($unitName)
    {
        $this->businessUnitName->clear();
        $this->businessUnitName->value($unitName);
        return $this;
    }

    /**
     * @return string
     */
    public function getBusinessUnitName()
    {
        return $this->businessUnitName->value();
    }

    public function edit()
    {
        $this->test
            ->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit Business Unit']")
            ->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->init();
        return $this;
    }

    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new BusinessUnits($this->test, false);
    }
}
