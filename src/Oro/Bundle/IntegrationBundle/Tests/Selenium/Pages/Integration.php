<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Integration
 * @package OroCRM\Bundle\IntegrationBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class Integration extends AbstractPageEntity
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $field = $this->test->byId('oro_integration_channel_form_name');
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
        $field = $this->test->select($this->test->byId('oro_integration_channel_form_type'));
        $field->selectOptionByValue($type);

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
    public function scheduleSync()
    {
        $this->test->byXPath(
            "//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Schedule sync')]"
        )->click();
        $this->waitForAjax();

        return $this;
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
     * @return $this
     */
    public function checkAddQueueMessage()
    {
        $this->assertElementPresent(
            "//div[@class='message'][starts-with((.), 'A sync job has been added to the queue.')]"
        );

        return $this;
    }
}
