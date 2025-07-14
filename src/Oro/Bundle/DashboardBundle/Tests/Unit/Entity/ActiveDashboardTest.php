<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class ActiveDashboardTest extends TestCase
{
    private ActiveDashboard $activeDashboard;

    #[\Override]
    protected function setUp(): void
    {
        $this->activeDashboard = new ActiveDashboard();
    }

    public function testConstructor(): void
    {
        $this->assertNull($this->activeDashboard->getUser());
    }

    public function testSetAndGetUser(): void
    {
        $expected = $this->createMock(User::class);

        $this->activeDashboard->setUser($expected);

        $this->assertSame($expected, $this->activeDashboard->getUser());
    }

    public function testSetAndGetDashboard(): void
    {
        $expected = $this->createMock(Dashboard::class);

        $this->activeDashboard->setDashboard($expected);

        $this->assertSame($expected, $this->activeDashboard->getDashboard());
    }

    public function testOrganization(): void
    {
        $this->assertNull($this->activeDashboard->getOrganization());
        $value = $this->createMock(Organization::class);
        $this->assertEquals($this->activeDashboard, $this->activeDashboard->setOrganization($value));
        $this->assertEquals($value, $this->activeDashboard->getOrganization());
    }
}
