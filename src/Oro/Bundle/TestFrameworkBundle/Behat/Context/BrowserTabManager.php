<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Mink\Mink;

/**
 * Provides functionality to work with tabs.
 */
class BrowserTabManager
{
    /**
     * @var array|string[]
     */
    private $aliases = [];

    /**
     * @param Mink $mink
     * @param string $alias
     */
    public function openTab(Mink $mink, string $alias)
    {
        $session = $mink->getSession();

        $driver = $session->getDriver();
        $driver->executeScript(sprintf("window.open('%s', '_blank')", $session->getCurrentUrl()));

        $windowNames = $driver->getWindowNames();
        $this->aliases[$alias] = end($windowNames);

        $driver->switchToWindow($this->aliases[$alias]);
    }

    /**
     * @param Mink $mink
     * @param string $alias
     */
    public function switchTabForAlias(Mink $mink, string $alias)
    {
        $this->switchTab($mink, $this->aliases[$alias]);
    }

    /**
     * @param Mink $mink
     * @param string $id
     */
    public function switchTab(Mink $mink, string $id)
    {
        $driver = $mink->getSession()->getDriver();
        $driver->switchToWindow($id);
    }

    /**
     * @param Mink $mink
     * @param string $alias
     */
    public function addAliasForCurrentTab(Mink $mink, string $alias): void
    {
        $this->aliases[$alias] = $mink->getSession()->getWindowName();
    }

    /**
     * @param Mink $mink
     * @param string|null $alias
     */
    public function closeTab(Mink $mink, string $alias = null)
    {
        $session = $mink->getSession();
        $driver = $session->getDriver();

        $current = $driver->getWindowName();
        if ($alias && $current !== $this->aliases[$alias]) {
            $this->switchTabForAlias($mink, $alias);
        }

        $driver->executeScript('setTimeout(window.close)');

        if ($alias && $current !== $this->aliases[$alias]) {
            $this->switchTab($mink, $current);
        }
    }
}
