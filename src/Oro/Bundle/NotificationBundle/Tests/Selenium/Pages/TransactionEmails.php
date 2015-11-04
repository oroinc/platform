<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class TransactionEmails
 *
 * @package Oro\Bundle\TranslationBundle\Tests\Selenium\Pages
 * @method TransactionEmails openTransactionEmails(string $bundlePath)
 * @method TransactionEmails assertTitle($title, $message = '')
 * @method TransactionEmail add()
 * @method TransactionEmail open(array $filter)
 */
class TransactionEmails extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Notification Rule']";
    const URL = 'notification/email';

    public function entityNew()
    {
        return new TransactionEmail($this->test);
    }

    public function entityView()
    {
        return new TransactionEmail($this->test);
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
