<?php

namespace Oro\Bundle\SearchBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\User;

/**
 * Class AdvancedSearchTest
 *
 * @package Oro\Bundle\SearchBundle\Tests\Selenium
 */
class AdvancedSearchTest extends Selenium2TestCase
{
    /**
     * Tests that checks advanced search
     *
     * @dataProvider columnTitle
     */
    public function testAdvancedSearch($query, $userField)
    {
        $this->markTestSkipped('Acme specific test');
        $login = $this->login();
        $login->openUsers('Oro\Bundle\UserBundle');
        $users = new User($this);
        $userData = $users->getRandomEntity();
        $login->openNavigation('Oro\Bundle\NavigationBundle', array('url' => '/search/advanced-search-page'));
        //Fill advanced search input field
        $login->byId('query')->value($query . $userData[$userField]);
        $login->byId('sendButton')->click();
        $login->waitPageToLoad();
        $login->waitForAjax();
        //Check that result is not null
        $result = strtolower($userData['USERNAME']);
        $login->assertElementPresent(
            "//div[@class='container-fluid']" .
            "//div[@class='search_stats alert alert-info']//h3[contains(., '{$result}')]",
            'Search results does not found'
        );
    }

    /**
     * Data provider for advanced search
     *
     * @return array
     */
    public function columnTitle()
    {
        return array(
            'firstName' => array('where firstName ~ ','FIRST NAME'),
        );
    }
}
