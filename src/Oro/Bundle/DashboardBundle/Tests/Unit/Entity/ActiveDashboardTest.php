<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;

class ActiveDashboardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveDashboard
     */
    protected $activeDashboard;

    protected function setUp()
    {
        $this->activeDashboard = new ActiveDashboard();
    }

    public function testConstructor()
    {
        $this->assertNull($this->activeDashboard->getUser());
    }

    public function testSetAndGetUser()
    {
        $expected  = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->activeDashboard->setUser($expected);

        $this->assertSame($expected, $this->activeDashboard->getUser());
    }

    public function testSetAndGetDashboard()
    {
        $expected  = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $this->activeDashboard->setDashboard($expected);

        $this->assertSame($expected, $this->activeDashboard->getDashboard());
    }
}
