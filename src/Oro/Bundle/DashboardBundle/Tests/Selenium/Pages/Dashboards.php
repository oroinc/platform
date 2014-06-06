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
        $user = $this->getEntity($entityData);
        $user->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Dashboard($this->test);
    }

    public function edit()
    {
        return new Dashboard($this->test);
    }
}
