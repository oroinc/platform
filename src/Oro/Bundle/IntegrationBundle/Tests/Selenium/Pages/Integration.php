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
     * @param string $url
     * @return $this
     */
    public function setWsdlUrl($url)
    {
        $field = $this->test->byId('oro_integration_channel_form_transport_wsdlUrl');
        $field->clear();
        $field->value($url);

        return $this;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setApiUser($user)
    {
        $field = $this->test->byId('oro_integration_channel_form_transport_apiUser');
        $field->clear();
        $field->value($user);

        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setApiKey($key)
    {
        $field = $this->test->byId('oro_integration_channel_form_transport_apiKey');
        $field->clear();
        $field->value($key);

        return $this;
    }

    /**
     * @return $this
     */
    public function setWsiCompliance()
    {
        $this->test->byXPath("//input[@id='oro_integration_channel_form_transport_isWsiMode']")->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setSyncDate($date)
    {
        $field = $this->test->byId('date_selector_oro_integration_channel_form_transport_syncStartDate');
        $field->clear();
        $field->value($date);

        return $this;
    }

    /**
     * @return $this
     */
    public function checkConnection()
    {
        $this->test->byXPath("//button[@id='oro_integration_channel_form_transport_check']")->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $website
     * @return $this
     */
    public function selectWebsite($website)
    {
        $select = $this->test->select($this->test->byId('oro_integration_channel_form_transport_websiteId'));
        $select->selectOptionByLabel($website);

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setAdminUrl($url)
    {
        $field = $this->test->byId('oro_integration_channel_form_transport_adminUrl');
        $field->clear();
        $field->value($url);

        return $this;
    }

    /**
     * @param array $connectors
     * @return $this
     */
    public function setConnectors($connectors = array())
    {
        foreach ($connectors as $connector) {
            $this->test->byXPath(
                "//div[@id='oro_integration_channel_form_connectors']//label[contains(., '{$connector}')]"
            )->click();
            $this->waitForAjax();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setTwoWaySync()
    {
        $this->test->byXPath(
            "//input[@id='oro_integration_channel_form_synchronizationSettings_isTwoWaySyncEnabled']"
        )->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $priority
     * @return $this
     */
    public function setSyncPriority($priority)
    {
        $select = $this->test->select(
            $this->test->byId('oro_integration_channel_form_synchronizationSettings_syncPriority')
        );
        $select->selectOptionByLabel($priority);

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
