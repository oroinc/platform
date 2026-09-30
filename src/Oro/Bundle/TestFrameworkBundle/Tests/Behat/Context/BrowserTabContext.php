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

    /** @var array<string, string[]> Session name => window names */
    private array $tabsAtScenarioStart = [];

    /**
     * Remembers the open tabs, so a tab of an earlier scenario does not count as a new tab.
     *
     * @BeforeScenario
     */
    public function rememberOpenTabs(): void
    {
        $this->tabsAtScenarioStart = [];

        $mink = $this->getMink();
        if (!$mink->isSessionStarted()) {
            return;
        }

        $this->tabsAtScenarioStart[$mink->getDefaultSessionName()] = $mink->getSession()->getWindowNames();
    }

    #[\Override]
    public function setBrowserTabManager(BrowserTabManager $browserTabManager)
    {
        $this->browserTabManager = $browserTabManager;
    }

    /**
     * Returns the name of the current and the last browser tab as an array.
     *
     * @return array [$currentTab, $lastTab]
     */
    private function getCurrentAndLastTabNames()
    {
        $currentTab = $this->getSession()->getWindowName();
        $lastTab = $currentTab;
        // A new tab registers in the driver after a delay. Wait for a tab that this scenario opened, because a tab
        // of an earlier scenario is not new. Without a list of earlier tabs, any tab except the current one counts.
        $knownTabs = $this->tabsAtScenarioStart[$this->getMink()->getDefaultSessionName()] ?? [];
        $knownTabs[] = $currentTab;
        $this->spin(function () use (&$lastTab, $knownTabs) {
            $windowNames = $this->getSession()->getWindowNames();
            $openedHere = array_values(array_diff($windowNames, $knownTabs));
            $lastTab = $openedHere ? end($openedHere) : end($windowNames);

            return (bool)$openedHere;
        }, 15);

        return [$currentTab, $lastTab];
    }

    /**
     * Check if the browser opened a new tab.
     *
     * It is based on the assumption that the new window is the last tab.
     *
     * Example: Then a new browser tab is opened
     * @Then /^a new browser tab is opened$/
     */
    public function newBrowserTabIsOpened()
    {
        list($currentTab, $lastTab) = $this->getCurrentAndLastTabNames();
        if ($lastTab === $currentTab) {
            self::fail('No new browser tabs detected after the current one');
        }
    }

    /**
     * Check if the browser opened a new tab, and switch to this tab if it is.
     *
     * It is based on the assumption that the new window is the last tab.
     *
     * Example: Then a new browser tab is opened and I switch to it
     * @Then /^a new browser tab is opened and I switch to it$/
     */
    public function newBrowserTabIsOpenedAndISwitchToIt()
    {
        list($currentTab, $lastTab) = $this->getCurrentAndLastTabNames();
        if ($lastTab === $currentTab) {
            self::fail('No new browser tabs detected after the current one');
        }
        $this->getSession()->switchToWindow($lastTab);
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
     * @When /^(?:|I )close (?:|the )browser tab "(?P<alias>[^"]+)"$/
     */
    public function iCloseTheCurrentWindow(?string $alias = null)
    {
        $this->browserTabManager->closeTab($this->getMink(), $alias);
    }
}
