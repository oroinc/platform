<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * Provides a set of steps to test navigation related to pinbar functionality.
 */
class PinbarContext extends OroFeatureContext implements
    OroPageObjectAware,
    KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * Pin or unpin page
     * Example: When I pin page
     * Example: And unpin page
     *
     * @When /^(?:|I )(?P<action>(pin|unpin)) page$/
     */
    public function iPinPage($action)
    {
        $button = $this->getPage()->findButton('Pin/unpin the page');
        self::assertNotNull($button, 'Pin/Unpin button not found on page');

        $activeClass = 'gold-icon';

        if ('pin' === $action) {
            if ($button->hasClass($activeClass)) {
                self::fail('Can\'t pin tab that already pinned');
            }

            $button->press();
        } elseif ('unpin' === $action) {
            if (!$button->hasClass($activeClass)) {
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
     * @Given /^(?P<link>[\w\s-]+) link must not be in pin holder$/
     * @Given /^"(?P<link>[\w\s-]+)" link must not be in pin holder$/
     */
    public function usersLinkMustNotBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertFalse($linkElement->isValid(), "Link with '$link' anchor found, but it's not expected");
    }

    /**
     * Assert that link is present on pin bar
     * Example: Then Users link must be in pin holder
     * Example: Then Create User link must be in pin holder
     *
     * @Then /^(?P<link>[\w\s-]+) link must be in pin holder$/
     * @Then /^"(?P<link>[\w\s-]+)" link must be in pin holder$/
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
     * @When /^(?:|I )follow (?P<link>[\w\s-]+) link in pin holder$/
     * @When /^(?:|I )follow "(?P<link>[\w\s-]+)" link in pin holder$/
     */
    public function followUsersLinkInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");

        $linkElement->click();
    }
}
