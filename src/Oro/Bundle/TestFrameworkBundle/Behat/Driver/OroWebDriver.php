<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use WebDriver\Browser;
use WebDriver\Session;
use WebDriver\WebDriver;

/**
 * Behat session web driver.
 */
class OroWebDriver extends WebDriver
{
    private WatchModeSessionHolder $sessionHolder;

    public function session($requiredCapabilities = Browser::FIREFOX, $desiredCapabilities = []): Session
    {
        if ($this->sessionHolder->isWatchFrom() && $this->sessionHolder->hasDefaultSession()) {
            return new Session($this->sessionHolder->getDefaultSession());
        }

        return parent::session($requiredCapabilities, $desiredCapabilities);
    }

    public function setSessionHolder(WatchModeSessionHolder $sessionHolder): void
    {
        $this->sessionHolder = $sessionHolder;
    }
}
