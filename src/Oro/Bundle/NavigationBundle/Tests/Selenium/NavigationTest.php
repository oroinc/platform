<?php

namespace Oro\Bundle\NavigationBundle\Tests\Selenium;

use Oro\Bundle\NavigationBundle\Tests\Selenium\Pages\Navigation;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Groups;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class NavigationTest
 *
 * @package Oro\Bundle\NavigationBundle\Tests\Selenium
 */
class NavigationTest extends Selenium2TestCase
{
    /**
     * Test for User tab navigation
     */
    public function testUserTab()
    {
        $login = $this->login();
        /** @var $login Navigation */
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('User Management')
            ->menu('Users')
            ->assertElementPresent("//table[@class='grid table-hover table table-bordered table-condensed']/tbody");

        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('User Management')
            ->menu('Roles')
            ->assertElementPresent("//table[@class='grid table-hover table table-bordered table-condensed']/tbody");

        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('User Management')
            ->menu('Groups')
            ->assertElementPresent("//table[@class='grid table-hover table table-bordered table-condensed']/tbody");
    }

    /**
     * Test Pinbar History
     *
     * @depends testUserTab
     */
    public function testPinbarHistory()
    {
        $login = $this->login();
        //Open History pinbar dropdown
        $login->getTest()->byXPath("//li[contains(@class, 'dot-menu')]/a")->click();
        $login->waitForAjax();
        $login->assertElementPresent("//div[@class='tabbable tabs-left']");
        $login->getTest()->byXPath("//div[@class='tabbable tabs-left']//a[contains(., 'History')]")->click();
        $login->waitForAjax();
        //Check that user, group and roles pages added
        $login->assertElementPresent(
            "//div[@id='history-content'][//a[contains(., 'Users')]]" .
            "[//a[contains(., 'Roles')]][//a[contains(., 'Groups')]]",
            'Not found in History tab'
        );
    }

    /**
     * Test Pinbar Most Viewed
     *
     * @depends testUserTab
     */
    public function testPinbarMostViewed()
    {
        $login = $this->login();
        //Open Most viewed pinbar dropdown
        $login->getTest()->byXPath("//li[contains(@class, 'dot-menu')]/a")->click();
        $login->waitForAjax();
        $login->assertElementPresent("//div[@class='tabbable tabs-left']");
        $login->getTest()->byXPath("//div[@class='tabbable tabs-left']//a[contains(., 'Most Viewed')]")->click();
        $login->waitForAjax();
        //Check that user, group and roles pages added
        $login->assertElementPresent(
            "//div[@id='mostviewed-content'][//a[contains(., 'Users')]]" .
            "[//a[contains(., 'Roles')]][//a[contains(., 'Groups')]]",
            'Not found in Most Viewed section'
        );
    }

    /**
     * Test Pinbar Most Viewed
     *
     */
    public function testPinbarFavorites()
    {
        $login = $this->login();
        /** @var $login Groups */
        $login->openGroups('Oro\Bundle\UserBundle');
        //Add Groups page to favorites
        $login->getTest()->byXPath("//button[@class='btn favorite-button']")->click();
        //Open pinbar dropdown Favorites
        $login->getTest()->byXPath("//li[contains(@class, 'dot-menu')]/a")->click();
        $login->waitForAjax();
        $login->assertElementPresent("//div[@class='tabbable tabs-left']");
        $login->getTest()->byXPath("//div[@class='tabbable tabs-left']//a[contains(., 'Favorites')]")->click();
        $login->waitForAjax();
        //Check that page is added to favorites
        $login->assertElementPresent("//div[@id='favorite-content' and @class='tab-pane active']");
        $login->waitForAjax();
        $login->assertElementPresent(
            "//li[@id='favorite-tab'][//span[contains(., 'Groups')]]",
            'Not found in favorites section'
        );
        //Remove Groups page from favorites
        $login->getTest()
            ->byXPath("//div[@id='favorite-content'][//span[contains(., 'Groups')]]//button[@class='close']")
            ->click();
        $login->waitForAjax();
        //Check that page is deleted from favorites
        $login->assertElementNotPresent(
            "//div[@id='favorites-content'][//span[contains(., 'Groups')]]",
            'Not found in favorites section'
        );
    }

    public function testTabs()
    {
        $login = $this->login();
        /** @var $login Users */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->pin()
            ->assertElementPresent(
                "//div[@class='list-bar']//a[contains(@title, 'Users - User Management - System') and text()='Users']",
                'Element does not minimised to pinbar tab'
            );
    }

    public function testSimpleSearch()
    {
        $login = $this->login();
        $login->assertElementPresent(
            "//div[@id='search-div']//input[@id='search-bar-search']",
            "Simple search does not available"
        );
    }
}
