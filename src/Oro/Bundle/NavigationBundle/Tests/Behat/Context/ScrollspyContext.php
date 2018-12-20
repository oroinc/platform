<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * Provides a set of steps to test navigation with scrollspy.
 */
class ScrollspyContext extends OroFeatureContext implements
    OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Click link in scrollspy
     * Example: When I click "Additional information" in scrollspy
     *
     * @When /^(?:|I )click "(?P<name>[\w\s-]+)" in scrollspy$/
     */
    public function iClickLinkInScrollspy($name)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Scrollspy Link', $name, false);
        self::assertTrue($linkElement->isValid(), "Link with '$name' text not found in scrollspy");

        $linkElement->click();
    }
}
