<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class EmailTemplates
 *
 * @package Oro\Bundle\EmailBundle\Tests\Selenium\Pages
 * @method EmailTemplates openEmailTemplates(string $bundlePath)
 * @method EmailTemplates assertTitle($title, $message = '')
 * @method EmailTemplate add()
 * @method EmailTemplate open(array $filter)
 */
class EmailTemplates extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Template']";
    const URL = 'email/emailtemplate';

    public function entityNew()
    {
        return new EmailTemplate($this->test);
    }

    public function entityView()
    {
        return new EmailTemplate($this->test);
    }

    public function cloneEntity($filterBy, $entityName)
    {
        $this->filterBy($filterBy, $entityName);
        $this->waitForAjax();
        if ($this->isElementPresent("//td[contains(@class,'action-cell')]//a[contains(., '...')]")) {
            $action = $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]");
            $action->click();
            $action->click();
            $this->waitForAjax();
        }
        $this->test->byXpath("//td[contains(@class,'action-cell')]//a[@title= 'Clone']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new EmailTemplate($this->test);
    }

    /**
     * @param $entityName
     * @param $contextName
     * @return $this
     */
    public function checkContextMenu($entityName, $contextName)
    {
        $this->filterBy('Recipient email', $entityName);
        $this->waitForAjax();
        if ($this->isElementPresent("//td[contains(@class,'action-cell')]//a[contains(., '...')]")) {
            $this->test->byXpath("//td[contains(@class,'action-cell')]//a[contains(., '...')]")->click();
            $this->waitForAjax();
        }
        return $this->assertElementNotPresent("//td[contains(@class,'action-cell')]//a[@title= '{$contextName}']");
    }
}
