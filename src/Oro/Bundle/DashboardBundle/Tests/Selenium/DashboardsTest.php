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
    public function testGroupsGrid()
    {
        $login = $this->login();
        /** @var Dashboards $login */
        $login->openDashboards('Oro\Bundle\DashboardBundle')
            ->assertTitle('Manage dashboards - Dashboards');
    }
}
