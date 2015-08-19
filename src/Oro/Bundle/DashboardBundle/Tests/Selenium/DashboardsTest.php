<?php

namespace Oro\Bundle\DashboardBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\DashboardBundle\Tests\Selenium\Pages\Dashboards;

/**
 * Class DashboardsTest
 *
 * @package Oro\Bundle\DashboardBundle\Tests\Selenium
 */
class DashboardsTest extends Selenium2TestCase
{
    public function testGrid()
    {
        $login = $this->login();
        /** @var Dashboards $login */
        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->assertTitle('All - Manage dashboards - Dashboards');
    }

    /**
     * @return string
     */
    public function testCreate()
    {
        $dashboardName = 'Dashboard_' . mt_rand();
        $login = $this->login();
        /** @var Dashboards $login */
        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->add()
            ->setLabel($dashboardName)
            ->setClone('Blank Dashboard')
            ->save()
            ->assertMessage('Dashboard saved')
            ->assertTitle("{$dashboardName} - Manage dashboards - Dashboards");

        return $dashboardName;
    }

    /**
     * @depends testCreate
     * @param $dashboardName
     * @return string
     */
    public function testUpdate($dashboardName)
    {
        $newDashboardName = 'Update_' . $dashboardName;

        $login = $this->login();
        /** @var Dashboards $login */
        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->filterBy('Label', $dashboardName)
            ->action(array($dashboardName), 'Edit')
            ->edit()
            ->assertTitle($dashboardName . ' - Edit - Manage dashboards - Dashboards')
            ->setLabel($newDashboardName)
            ->save()
            ->assertMessage('Dashboard saved')
            ->assertTitle("{$newDashboardName} - Manage dashboards - Dashboards");

        return $newDashboardName;
    }

    /**
     * @depends testUpdate
     * @param $dashboardName
     */
    public function testDelete($dashboardName)
    {
        $login = $this->login();
        /** @var Dashboards $login */
        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->filterBy('Label', $dashboardName)
            ->delete(array($dashboardName))
            ->assertMessage('Item deleted');

        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->filterBy('Label', $dashboardName)
            ->assertNoDataMessage('No entity was found to match your search')
            ->assertTitle('All - Manage dashboards - Dashboards');
    }
}
