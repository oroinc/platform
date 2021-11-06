<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class ActiveDashboardTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActiveDashboard */
    private $activeDashboard;

    protected function setUp(): void
    {
        $this->activeDashboard = new ActiveDashboard();
    }

    public function testConstructor()
    {
        $this->assertNull($this->activeDashboard->getUser());
    }

    public function testSetAndGetUser()
    {
        $expected  = $this->createMock(User::class);

        $this->activeDashboard->setUser($expected);

        $this->assertSame($expected, $this->activeDashboard->getUser());
    }

    public function testSetAndGetDashboard()
    {
        $expected  = $this->createMock(Dashboard::class);

        $this->activeDashboard->setDashboard($expected);

        $this->assertSame($expected, $this->activeDashboard->getDashboard());
    }

    public function testOrganization()
    {
        $this->assertNull($this->activeDashboard->getOrganization());
        $value = $this->createMock(Organization::class);
        $this->assertEquals($this->activeDashboard, $this->activeDashboard->setOrganization($value));
        $this->assertEquals($value, $this->activeDashboard->getOrganization());
    }
}
