<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class EmailTemplates
 *
 * @package Oro\Bundle\EmailBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\EmailBundle\Tests\Selenium\Pages\EmailTemplates openEmailTemplates() openEmailTemplates()
 * @method \Oro\Bundle\EmailBundle\Tests\Selenium\Pages\EmailTemplates assertTitle() assertTitle($title, $message = '')
 */
class EmailTemplates extends AbstractPageFilteredGrid
{
    const URL = 'email/emailtemplate';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return EmailTemplate
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Template']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new EmailTemplate($this->test);
    }

    /**
     * @param array $entityData
     * @return EmailTemplate
     */
    public function open($entityData = array())
    {
        $emailTemplate = $this->getEntity($entityData);
        $emailTemplate->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new EmailTemplate($this->test);
    }

    /**
     * @param $filterBy
     * @param $entityName
     * @return $this
     */
    public function delete($filterBy, $entityName)
    {
        $this->filterBy($filterBy, $entityName);
        $this->waitForAjax();
        $this->test->byXpath("//td[@class='action-cell']//a[contains(., '...')]")->click();
        $this->waitForAjax();
        $this->test->byXpath("//td[@class='action-cell']//a[@title= 'Delete']")->click();
        $this->waitForAjax();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    public function cloneEntity($filterBy, $entityName)
    {
        $this->filterBy($filterBy, $entityName);
        $this->waitForAjax();
        $this->test->byXpath("//td[@class='action-cell']//a[contains(., '...')]")->click();
        $this->waitForAjax();
        $this->test->byXpath("//td[@class='action-cell']//a[@title= 'Clone']")->click();
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
        if ($this->isElementPresent("//td[@class='action-cell']//a[contains(., '...')]")) {
            $this->test->byXpath("//td[@class='action-cell']//a[contains(., '...')]")->click();
            $this->waitForAjax();
            return $this->assertElementNotPresent("//td[@class='action-cell']//a[@title= '{$contextName}']");
        }

        return $this;
    }
}
