<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * Allows to manage browser's tabs
 */
class BrowserTabContext extends OroFeatureContext
{
    /**
     * Returns the name of the current and the last browser tab as an array.
     *
     * @return array [$currentTab, $lastTab]
     */
    private function getCurrentAndLastTabNames()
    {
        $currentTab = $this->getSession()->getWindowName();
        $windowNames = $this->getSession()->getWindowNames();
        $lastTab = end($windowNames);
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
     * Example: And I open a new browser tab
     * @When /^(?:|I )open a new browser tab$/
     */
    public function iOpenANewWindow()
    {
        $driver = $this->getSession()->getDriver();
        $url = $this->getSession()->getCurrentUrl();
        $driver->executeScript("window.open('{$url}', '_blank')");
        $windowNames = $driver->getWindowNames();
        $driver->switchToWindow(end($windowNames));
    }

    /**
     * Switches to any opened tab of the window by its index, starting from 1 to the length of windowNames array
     *
     * Example: And I switch to the browser tab "3"
     * @When /^(?:|I )switch to (?:|the )browser tab "(?P<tabIndex>\d+)"$/
     * @param int $tabIndex
     * @throws \Exception
     */
    public function iSwitchToTheWindow(int $tabIndex)
    {
        $driver = $this->getSession()->getDriver();
        $windowNames = $driver->getWindowNames();

        if ($tabIndex < 1 || $tabIndex > count($windowNames)) {
            throw new \Exception(
                sprintf(
                    "Index of window out of bounds, given '%d' but number of windows from '1' tp '%d'",
                    $tabIndex,
                    count($windowNames)
                )
            );
        }

        $driver->switchToWindow($windowNames[$tabIndex - 1]);
    }

    /**
     * Switches to nearest sibling browser tab on the left or on the right
     * If the current tab is the first, switching to left will make the last tab active
     * If the current tab is the last, switching to right will make the first tab active
     *
     * Example: And I switch to the right browser tab
     * Example: And I switch to the left browser tab
     * @When /^(?:|I )switch to (?:|the )(?P<direction>right|left) browser tab$/
     * @param string $direction 'left' or 'right'
     * @throws \Exception when direction is not 'left' or 'right'
     */
    public function iSwitchToTheSiblingWindow($direction)
    {
        $driver = $this->getSession()->getDriver();

        $windowNames = $driver->getWindowNames();
        $currentTabIndex = array_search($driver->getWindowName(), $windowNames);

        switch ($direction) {
            case 'right':
                $windowName = $currentTabIndex === count($windowNames) - 1 ? 0 : $windowNames[$currentTabIndex + 1];
                break;
            case 'left':
                $windowName = $windowNames[($currentTabIndex === 0 ? count($windowNames) : $currentTabIndex) - 1];
                break;
            default:
                throw new \Exception(
                    sprintf('Direction to sibling "%s" is not supported, use "right" or "left"', $direction)
                );
        }

        $driver->switchToWindow($windowName);
    }

    /**
     * Closes current browser tab
     *
     * Example: And I close the current browser tab
     * @When /^(?:|I )close (?:|the )current browser tab$/
     */
    public function iCloseTheCurrentWindow()
    {
        $this->getSession()->getDriver()->executeScript("setTimeout(window.close)");
    }
}
