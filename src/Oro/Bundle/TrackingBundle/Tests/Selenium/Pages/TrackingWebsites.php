<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium\Pages;

use PHPUnit_Framework_Assert;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class TrackingWebsites
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium\Pages
 * @method TrackingWebsites openTrackingWebsites openTrackingWebsites(string)
 * {@inheritdoc}
 */
class TrackingWebsites extends AbstractPageFilteredGrid
{
    const URL = 'tracking/website';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return TrackingWebsite
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Tracking Website']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new TrackingWebsite($this->test);
    }

    /**
     * @param array $entityData
     * @return TrackingWebsite
     */
    public function open($entityData = array())
    {
        $cart = $this->getEntity($entityData, 2);
        $cart->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new TrackingWebsite($this->test);
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
            PHPUnit_Framework_Assert::assertEquals(
                $optional,
                $this->test->title(),
                $message
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            PHPUnit_Framework_Assert::assertEquals(
                $required,
                $this->test->title(),
                $message
            );
        }

        return $this;
    }
}
