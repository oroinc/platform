<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium\Pages;

use PHPUnit_Framework_Assert;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class TrackingWebsites
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium\Pages
 * @method TrackingWebsites openTrackingWebsites openTrackingWebsites(string)
 * @method TrackingWebsite add add()
 * @method TrackingWebsite open open()
 * {@inheritdoc}
 */
class TrackingWebsites extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Tracking Website']";
    const URL = 'tracking/website';

    public function entityNew()
    {
        return new TrackingWebsite($this->test);
    }

    public function entityView()
    {
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
