<?php

namespace Oro\Bundle\DistributionBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then the documentation for installer will opened
     */
    public function theDocumentationWillOpened()
    {
        $windowNames = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow($windowNames[1]);
        self::assertContains(
            'https://www.orocommerce.com/documentation/current/install-upgrade',
            $this->getSession()->getCurrentUrl()
        );
    }

    /**
     * Click on element on page
     * Example: When I click on "Get help"
     *
     * @param string $element
     *
     * @When /^(?:|I )click on "(?P<element>[\w\s]+)"$/
     */
    public function iClickOn($element)
    {
        $this->createElement($element)->click();
    }
}
