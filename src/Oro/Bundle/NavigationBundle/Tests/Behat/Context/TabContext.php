<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
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
     * @When /^(?:|I )click "(?P<name>(?:[^"]|\\")*)" tab$/
     * @When /^(?:|I )click "(?P<name>(?:[^"]|\\")*)" tab in "(?P<element>(?:[^"]|\\")*)" element$/
     */
    public function iClickTabLink(string $name, ?string $element = null)
    {
        if ($element) {
            $element = $this->createElement($element);
            self::assertTrue($element->isValid());
        }

        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', $name, false, $element);
        self::assertTrue($linkElement->isValid(), "Link with '$name' text not found in tab");

        $linkElement->click();
    }

    /**
     * Assert tabs exists in tab set
     * Example: Then I should see following tabs:
     *              | First Tab  |
     *              | Second Tab |
     *
     * @Then /^(?:|I )should see following tabs:$/
     * @Then /^(?:|I )should see following tabs in "(?P<element>(?:[^"]|\\")*)" element:$/
     */
    public function iShouldSeeFollowingTabs(TableNode $table, ?string $element = null)
    {
        if ($element) {
            $element = $this->createElement($element);
            self::assertTrue($element->isValid());
        }

        $expectedValues = $table->getColumn(0);
        foreach ($expectedValues as $expectedValue) {
            $linkElement = $this->elementFactory->findElementContainsByXPath(
                'Tab Link',
                $expectedValue,
                false,
                $element
            );
            self::assertTrue($linkElement->isValid(), "Link with '$expectedValue' text not found in tab");
        }
    }

    /**
     * Assert tabs is not exists in tab set
     * Example: Then I should not see following tabs:
     *              | First Tab  |
     *              | Second Tab |
     *
     * @Then /^(?:|I )should not see following tabs:$/
     */
    public function iShouldNotSeeFollowingTabs(TableNode $table)
    {
        $expectedValues = $table->getColumn(0);
        foreach ($expectedValues as $expectedValue) {
            $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', $expectedValue, false);
            self::assertFalse($linkElement->isValid(), "Link with '$expectedValue' text is present in tab");
        }
    }
}
