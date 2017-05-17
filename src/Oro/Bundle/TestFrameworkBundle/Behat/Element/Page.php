<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;

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

    /**
     * @param OroElementFactory $elementFactory
     * @param $route
     */
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
     *
     * @param array $parameters
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
