<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class BusinessUnits
 *
 * @package Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 * @method BusinessUnits openBusinessUnits(string $bundlePath)
 * @method BusinessUnit add()
 * @method BusinessUnit open(array $filter)
 */
class BusinessUnits extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Business Unit']";
    const URL = 'organization/business_unit';

    public function entityNew()
    {
        $businessUnit = new BusinessUnit($this->test);
        return $businessUnit->init();
    }

    public function entityView()
    {
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
        if ($this->isElementPresent("//td[contains(@class,'action-cell')]//a[contains(., '...')]")) {
            $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]")->click();
            $this->waitForAjax();
        }
        return $this->assertElementNotPresent("//td[contains(@class,'action-cell')]//a[@title= '{$contextName}']");
    }
}
