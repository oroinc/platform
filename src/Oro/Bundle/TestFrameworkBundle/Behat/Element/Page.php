<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;

/**
 * Provides common functionality for Behat page objects.
 *
 * This base class implements the page object pattern for Behat tests, providing access to the element factory
 * and route information. Subclasses should extend this to create specific page objects representing
 * different application pages with their unique elements and interactions.
 */
abstract class Page
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var string
     */
    protected $route;

    public function __construct(OroElementFactory $elementFactory, $route)
    {
        $this->elementFactory = $elementFactory;
        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Open page using parameters
     */
    abstract public function open(array $parameters = []);

    /**
     * @return MainMenu
     */
    protected function getMainMenu()
    {
        return $this->elementFactory->createElement('MainMenu');
    }

    protected function waitForAjax()
    {
        $this->elementFactory->getPage()->getSession()->getDriver()->waitForAjax();
    }
}
