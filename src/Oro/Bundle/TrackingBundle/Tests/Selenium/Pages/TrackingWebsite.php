<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium\Pages;

use PHPUnit_Framework_Assert;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class TrackingWebsite
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class TrackingWebsite extends AbstractPageEntity
{
    protected $owner = "//div[starts-with(@id,'s2id_oro_tracking_website_owner')]/a";

     /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $object = $this->test->byXpath("//*[@data-ftid='oro_tracking_website_name']");
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
        $object = $this->test->byXpath("//*[@data-ftid='oro_tracking_website_identifier']");
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
        $object = $this->test->byXpath("//*[@data-ftid='oro_tracking_website_url']");
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

    /**
     * @param string $required
     * @param string $optional
     * @param string $message
     * @return $this
     */
    public function assertTitles($required, $optional, $message = '')
    {
        try {
            PHPUnit_Framework_Assert::assertContains(
                $optional,
                $this->test->title(),
                $message
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            PHPUnit_Framework_Assert::assertContains(
                $required,
                $this->test->title(),
                $message
            );
        }

        return $this;
    }
}
