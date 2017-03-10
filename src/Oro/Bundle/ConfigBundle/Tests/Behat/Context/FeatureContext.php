<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Click link on sidebar in configuration menu
     *
     * Example: Given I click "Inventory" on configuration sidebar
     *
     * @When /^(?:|I )click "(?P<link>(?:[^"]|\\")*)" on configuration sidebar$/
     */
    public function clickLinkOnConfigurationSidebar($link)
    {
        $sidebarConfigMenu = $this->getPage()->find('css', 'div.system-configuration-container div.left-panel');
        $sidebarConfigMenu->clickLink($link);
    }
}
