<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Oro\Bundle\NavigationBundle\Tests\Behat\Element\PinPageButton;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * Provides a set of steps to test navigation related to pinbar functionality.
 */
class PinbarContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Pin or unpin page
     * Example: When I pin page
     * Example: And unpin page
     *
     * @When /^(?:|I )(?P<action>(pin|unpin)) page$/
     */
    public function iPinPage($action)
    {
        /** @var PinPageButton $button */
        $button = $this->getPage()->getElement('PinPageButton');
        self::assertNotNull($button, 'Pin/Unpin button not found on page');

        if ('pin' === $action) {
            if ($button->isHighlited()) {
                self::fail('Can\'t pin tab that already pinned');
            }

            $button->press();
        } elseif ('unpin' === $action) {
            if (!$button->isHighlited()) {
                self::fail('Can\'t unpin tab that not pinned before');
            }

            $button->press();
        }
    }

    /**
     * Assert that link present is NOT on pin bar
     * Example: And Users link must not be in pin holder
     * Example: And Create User link must not be in pin holder
     *
     * @Given /^(?P<link>[\w\s\-\(\)]+) link must not be in pin holder$/
     * @Given /^"(?P<link>[\w\s\-\(\)]+)" link must not be in pin holder$/
     */
    public function linkMustNotBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertFalse($linkElement->isValid(), "Link with '$link' anchor found, but it's not expected");
    }

    /**
     * Assert that link is present on pin bar
     * Example: Then Users link must be in pin holder
     * Example: Then Create User link must be in pin holder
     *
     * @Then /^(?P<link>[\w\s\-\(\)]+) link must be in pin holder$/
     * @Then /^"(?P<link>[\w\s\-\(\)]+)" link must be in pin holder$/
     */
    public function linkMustBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");
    }

    /**
     * Click link in pin bar
     * Example: When follow Users link in pin holder
     * Example: When I follow Create User link in pin holder
     *
     * @When /^(?:|I )follow (?P<link>[\w\s\-\(\)]+) link in pin holder$/
     * @When /^(?:|I )follow "(?P<link>[\w\s\-\(\)]+)" link in pin holder$/
     */
    public function followUsersLinkInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");

        $linkElement->click();
    }

    /**
     * Check is pin with given name active
     *
     * @When /^(?:|I )should see that "(?P<pinName>[\w\s\-\(\)]+)" pin is active$/
     *
     * @param string $pinName
     * @return bool
     */
    public function isPinActive($pinName)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $pinName);
        return $linkElement && $linkElement->getParent()->hasClass('active');
    }

    /**
     * Check is pin with given name is not active
     *
     * @When /^(?:|I )should see that "(?P<pinName>[\w\s\-\(\)]+)" pin is inactive$/
     *
     * @param string $pinName
     * @return bool
     */
    public function isPinNotActive($pinName)
    {
        return !$this->isPinActive($pinName);
    }

    /**
     * Check chat 'Pin/unpin the page' button is highlighted
     *
     * @When /^(?:|I )should see that "Pin\/unpin the page" Button is highlighted$/
     *
     * @return bool
     */
    public function isPinButtonHighlighted()
    {
        /** @var PinPageButton $button */
        $button = $this->getPage()->getElement('PinPageButton');
        return $button->isHighlited();
    }

    /**
     * Check chat 'Pin/unpin the page' button is not highlighted
     *
     * @When /^(?:|I )should see that "Pin\/unpin the page" Button is not highlighted$/
     *
     * @return bool
     */
    public function isPinButtonNotHighlighted()
    {
        return !$this->isPinButtonHighlighted();
    }
}
