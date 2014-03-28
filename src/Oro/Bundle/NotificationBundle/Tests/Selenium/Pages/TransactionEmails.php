<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class TransactionEmails
 *
 * @package Oro\Bundle\TranslationBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\NotificationBundle\Tests\Selenium\Pages\TransactionEmails
 *          openTransactionEmails() openTransactionEmails()
 * @method \Oro\Bundle\NotificationBundle\Tests\Selenium\Pages\TransactionEmails
 *          assertTitle() assertTitle($title, $message = '')
 */
class TransactionEmails extends AbstractPageFilteredGrid
{
    const URL = 'notification/email';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return TransactionEmail
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Notification Rule']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new TransactionEmail($this->test);
    }

    /**
     * @param array $entityData
     * @return TransactionEmail
     */
    public function open($entityData = array())
    {
        $transactionEmail = $this->getEntity($entityData);
        $transactionEmail->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new TransactionEmail($this->test);
    }

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
