<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages;

use Oro\Bundle\CronBundle\Tests\Selenium\Pages\Job;
use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Integration
 * @package OroCRM\Bundle\IntegrationBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class Integration extends AbstractPageEntity
{
    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_name']");
        $field->clear();
        $field->value($name);

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->test->byXpath("//div[starts-with(@id, 's2id_oro_integration_channel_form_type')]/a")->click();
        $this->waitForAjax();
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$type}')]")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();

        return $this;
    }

    /**
     * @return Integrations
     */
    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new Integrations($this->test, false);
    }

    /**
     * @return $this
     */
    public function activate()
    {
        $this->test->byXPath(
            "//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Activate')]"
        )->click();
        $this->waitForAjax();
        $this->assertMessage('Integration activated');
        $this->assertElementPresent(
            "//div[@class='badge badge-enabled status-enabled'][text()='Active']",
            'Integration status are not Active'
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function deactivate()
    {
        $this->test->byXPath(
            "//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Deactivate')]"
        )->click();
        $this->waitForAjax();
        $this->assertMessage('Integration deactivated');
        $this->assertElementPresent(
            "//div[@class='badge badge-disabled status-disabled'][text()='Inactive']",
            'Integration status are not Inactive'
        );

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function checkStatus($status)
    {
        $this->assertElementPresent("//div[starts-with(@class, 'badge badge-enabled')][contains(., '{$status}')]");

        return $this;
    }

    /**
     * Method click Schedule sync button
     * @return $this
     */
    public function scheduleSync()
    {
        $this->test->byXPath("//div[@class='pull-right']//a[text()='Schedule sync']")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->assertMessage('A sync job has been added to the queue. Check progress.');

        return $this;
    }

    /**
     * Method click link at flash message
     * @return Job
     */
    public function clickSyncMessageLink()
    {
        $this->test->byXPath("//div[@id = 'flash-messages']//a")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();

        return new Job($this->test);
    }
}
