<?php

namespace Oro\Bundle\DashboardBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Dashboard Management
 *
 * @package Oro\Bundle\DashboardBundle\Tests\Selenium\Pages
 * @method Dashboards openDashboards() openDashboards(string)
 * {@inheritdoc}
 */
class Dashboards extends AbstractPageFilteredGrid
{
    const URL = 'dashboard';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);

    }

    /**
     * @return Dashboard
     */
    public function add()
    {
        $this->test->byXpath("//a[@title = 'Create Dashboard']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $dashboard = new Dashboard($this->test);
        return $dashboard->init();
    }

    /**
     * @param array $entityData
     *
     * @return Dashboard
     */
    public function open($entityData = array())
    {
        $page = parent::open($entityData);

        return new Dashboard($page->test);
    }

    public function edit()
    {
        return new Dashboard($this->test);
    }

    public function addWidget($name)
    {
        //click addWidget
        $this->test->byXPath(
            "//a[contains(@class, 'dashboard-widgets-add') and normalize-space(.) = 'Add widget']"
        )->click();
        // select widget and click add
        $this->test->byXPath("//tr[td[contains(., '{$name}')]]/td/a[contains(@class, 'add-widget-button')]")->click();
        //wait until adding
        $this->waitForAjax();
        //close widget dialog
        $this->test->byXPath("//a[@class = 'close']")->click();

        return $this;
    }

    public function widgetExists($widgets = array())
    {
        $result = true;
        foreach ($widgets as $widget) {
            $result = $result && $this->isElementPresent(
                "//div[contains(@class, 'widget-title') and normalize-space(text()) = '{$widget}']"
            );
        }

        return $result;
    }

    public function removeWidget($name, $confirmation = true)
    {
        $this->test->byXPath(
            "//div[contains(@id, 'widget-container-dashboard-widget') and " .
            "//div[contains(@class, 'title') and contains(., '{$name}')]]//a[@title = 'Delete']"
        )->click();
        if ($confirmation) {
            $this->test->byXPath("//div[div[contains(., 'Delete Confirmation')]]//a[contains(., 'Yes')]")->click();
        }

        $this->waitForAjax();
        return $this;
    }

    public function isEmpty()
    {
        return !$this->isElementPresent("//div[@class = 'empty-text hidden-empty-text']");
    }

    public function tools($action)
    {
        //click Tools
        $this->test->byXPath(
            "//a[contains(@class, 'dropdown-toggle') and normalize-space(.) = 'Tools']"
        )->click();
        //select action
        $this->test->byXPath(
            "//ul[contains(@class, 'dropdown-menu')//a[@title = '{$action}']"
        )->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }
}
