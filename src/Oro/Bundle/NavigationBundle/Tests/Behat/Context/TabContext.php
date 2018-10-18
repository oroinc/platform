<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * Provides a set of steps to test navigation with tabs.
 */
class TabContext extends OroFeatureContext implements
    OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Click link in tab set
     * Example: When I click "General" tab
     *
     * @When /^(?:|I )click "(?P<name>[\w\s-]+)" tab$/
     */
    public function iClickTabLink($name)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', $name, false);
        self::assertTrue($linkElement->isValid(), "Link with '$name' text not found in tab");

        $linkElement->click();
    }
}
