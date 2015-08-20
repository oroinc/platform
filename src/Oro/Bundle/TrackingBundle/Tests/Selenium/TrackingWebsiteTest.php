<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\TrackingBundle\Tests\Selenium\Pages\TrackingWebsites;

/**
 * Class TrackingWebsiteTest
 *
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium
 * {@inheritdoc}
 */
class TrackingWebsiteTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreate()
    {
        $identifier = 'Website' . mt_rand();

        $login = $this->login();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->assertTitles('Tracking Websites', 'Tracking Websites - Marketing')
            ->add()
            ->assertTitles('Create Tracking Website', 'Create Tracking Website - Tracking Websites - Marketing')
            ->setName($identifier)
            ->setIdentifier($identifier)
            ->setUrl("http://{$identifier}.com")
            ->save()
            ->assertMessage('Tracking Website saved')
            ->assertTitles("{$identifier}", "{$identifier} - Tracking Websites - Marketing");

        return $identifier;
    }

    /**
     * @depends testCreate
     * @param $identifier
     * @return string
     */
    public function testUpdate($identifier)
    {
        $newName = 'Update_'.$identifier;

        $login = $this->login();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->filterBy('Identifier', $identifier)
            ->open(array($identifier))
            ->assertTitles("{$identifier}", "{$identifier} - Tracking Websites - Marketing")
            ->edit()
            ->assertTitles("{$identifier} - Edit", "{$identifier} - Edit - Tracking Websites - Marketing")
            ->setName($newName)
            ->save()
            ->assertMessage('Tracking Website saved')
            ->assertTitles("{$newName}", "{$newName} - Tracking Websites - Marketing")
            ->close();

        return $newName;
    }

    /**
     * @depends testUpdate
     * @param $name
     */
    public function testDelete($name)
    {
        $login = $this->login();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->filterBy('Name', $name)
            ->open(array($name))
            ->delete()
            ->assertMessage('Tracking Website deleted');

        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle');
        if ($login->getRowsCount() > 0) {
            $login->filterBy('Name', $name)
                ->assertNoDataMessage('No entity was found to match your search');
        }
    }
}
