<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;

class ActiveDashboardTest extends \PHPUnit\Framework\TestCase
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
        $expected  = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        $this->activeDashboard->setUser($expected);

        $this->assertSame($expected, $this->activeDashboard->getUser());
    }

    public function testSetAndGetDashboard()
    {
        $expected  = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $this->activeDashboard->setDashboard($expected);

        $this->assertSame($expected, $this->activeDashboard->getDashboard());
    }

    public function testOrganization()
    {
        $this->assertNull($this->activeDashboard->getOrganization());
        $value = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $this->assertEquals($this->activeDashboard, $this->activeDashboard->setOrganization($value));
        $this->assertEquals($value, $this->activeDashboard->getOrganization());
    }
}
