<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\BrowserTabManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\BrowserTabManagerAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * Allows to manage browser's tabs
 */
class BrowserTabContext extends OroFeatureContext implements BrowserTabManagerAwareInterface
{
    /** @var BrowserTabManager */
    private $browserTabManager;

    /**
     * {@inheritdoc}
     */
    public function setBrowserTabManager(BrowserTabManager $browserTabManager)
    {
        $this->browserTabManager = $browserTabManager;
    }

    /**
     * Opens current url in the new tab and switches to that tab
     *
     * Example: And I open a new browser tab and set "tab1" alias for it
     * @When /^(?:|I )open a new browser tab and set "(?P<alias>[^"]+)" alias for it$/
     */
    public function iOpenANewWindow(string $alias)
    {
        $this->browserTabManager->openTab($this->getMink(), $alias);
    }

    /**
     * Sets alias for the current browser tab
     *
     * Example: And I set alias "tab1" for the current browser tab
     * @When /^(?:|I )set alias "(?P<alias>[^"]+)" for (?:|the )current browser tab$/
     */
    public function iSetAliasForTheCurrentWindow(string $alias)
    {
        $this->browserTabManager->addAliasForCurrentTab($this->getMink(), $alias);
    }

    /**
     * Switches to any opened tab of the window by its index, starting from 1 to the length of windowNames array
     *
     * Example: And I switch to the browser tab "3"
     * @When /^(?:|I )switch to (?:|the )browser tab "(?P<alias>[^"]+)"$/
     */
    public function iSwitchToTheWindow(string $alias)
    {
        $this->browserTabManager->switchTabForAlias($this->getMink(), $alias);
    }

    /**
     * Closes current browser tab
     *
     * Example: And I close the current browser tab
     * @When /^(?:|I )close (?:|the )current browser tab$/
     * @When /^(?:|I )close (?:|the ) browser tab "(?P<alias>[^"]+)"$/
     */
    public function iCloseTheCurrentWindow(string $alias = null)
    {
        $this->browserTabManager->closeTab($this->getMink(), $alias);
    }
}
