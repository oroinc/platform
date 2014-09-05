<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class TrackingWebsite
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class TrackingWebsite extends AbstractPageEntity
{
    protected $owner = "//div[starts-with(@id,'s2id_oro_tracking_website_owner')]/a";

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
        $object = $this->test->byId('oro_tracking_website_name');
        $object->clear();
        $object->value($name);

        return $this;
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $object = $this->test->byId('oro_tracking_website_identifier');
        $object->clear();
        $object->value($identifier);

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $object = $this->test->byId('oro_tracking_website_url');
        $object->clear();
        $object->value($url);

        return $this;
    }


    /**
     * @return $this
     */
    public function edit()
    {
        $this->test->byXpath(
            "//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit Tracking Website']"
        )->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new TrackingWebsites($this->test, false);
    }
}
