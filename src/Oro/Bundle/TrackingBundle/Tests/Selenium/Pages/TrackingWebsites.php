<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium\Pages;

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
}
