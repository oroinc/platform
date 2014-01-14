<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class BusinessUnits
 *
 * @package Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 */
class BusinessUnits extends AbstractPageFilteredGrid
{
    const URL = 'organization/business_unit';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    public function add()
    {
        $this->test->byXPath("//a[@title='Create business unit']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $businessUnit = new BusinessUnit($this->test);
        return $businessUnit->init();
    }

    /**
     * @param array $entityData
     * @return BusinessUnit
     */
    public function open($entityData = array())
    {
        $contact = $this->getEntity($entityData);
        $contact->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new BusinessUnit($this->test);
    }

    /**
     * @param $unitName
     * @param $contextName
     * @return $this
     */
    public function checkContextMenu($unitName, $contextName)
    {
        $this->filterBy('Name', $unitName);
        $this->waitForAjax();
        if ($this->isElementPresent("//td[@class='action-cell']//a[contains(., '...')]")) {
            $this->test->byXpath("//td[@class='action-cell']//a[contains(., '...')]")->click();
            $this->waitForAjax();
            return $this->assertElementNotPresent("//td[@class='action-cell']//a[@title= '{$contextName}']");
        }

        return $this;
    }
}
