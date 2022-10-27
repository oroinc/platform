<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DashboardTest extends \PHPUnit\Framework\TestCase
{
    /** @var Dashboard */
    private $dashboard;

    protected function setUp(): void
    {
        $this->dashboard = new Dashboard();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Collection::class, $this->dashboard->getWidgets());
    }

    public function testId()
    {
        $this->assertNull($this->dashboard->getId());
    }

    public function testLabel()
    {
        $this->assertNull($this->dashboard->getLabel());
        $value = 'test';
        $this->assertEquals($this->dashboard, $this->dashboard->setLabel($value));
        $this->assertEquals($value, $this->dashboard->getLabel());
    }

    public function testGetIsDefault()
    {
        $this->assertFalse($this->dashboard->getIsDefault());
        $this->dashboard->setIsDefault(true);
        $this->assertTrue($this->dashboard->getIsDefault());
    }

    public function testName()
    {
        $this->assertNull($this->dashboard->getName());
        $value = 'test';
        $this->assertEquals($this->dashboard, $this->dashboard->setName($value));
        $this->assertEquals($value, $this->dashboard->getName());
    }

    public function testOwner()
    {
        $this->assertNull($this->dashboard->getOwner());
        $value = $this->createMock(User::class);
        $this->assertEquals($this->dashboard, $this->dashboard->setOwner($value));
        $this->assertEquals($value, $this->dashboard->getOwner());
    }

    public function testOrganization()
    {
        $this->assertNull($this->dashboard->getOrganization());
        $value = $this->createMock(Organization::class);
        $this->assertEquals($this->dashboard, $this->dashboard->setOrganization($value));
        $this->assertEquals($value, $this->dashboard->getOrganization());
    }

    public function testStartDashboard()
    {
        $this->assertNull($this->dashboard->getOwner());
        $value = $this->createMock(Dashboard::class);
        $this->assertEquals($this->dashboard, $this->dashboard->setStartDashboard($value));
        $this->assertEquals($value, $this->dashboard->getStartDashboard());
    }

    public function testAddAndResetWidgets()
    {
        $widget = $this->createMock(Widget::class);
        $widget->expects($this->once())
            ->method('setDashboard')
            ->with($this->dashboard);
        $this->assertFalse($this->dashboard->getWidgets()->contains($widget));
        $this->assertEquals($this->dashboard, $this->dashboard->addWidget($widget));
        $this->assertEquals(1, $this->dashboard->getWidgets()->count());
        $this->assertTrue($this->dashboard->getWidgets()->contains($widget));
        $this->dashboard->resetWidgets();
        $this->assertEquals(0, $this->dashboard->getWidgets()->count());
    }

    public function testHasWidget()
    {
        $widget = $this->createMock(Widget::class);
        $this->assertFalse($this->dashboard->hasWidget($widget));
        $this->dashboard->addWidget($widget);
        $this->assertTrue($this->dashboard->hasWidget($widget));
    }

    public function testRemoveWidget()
    {
        $widget = $this->createMock(Widget::class);
        $this->assertFalse($this->dashboard->removeWidget($widget));
        $this->dashboard->addWidget($widget);
        $this->assertTrue($this->dashboard->hasWidget($widget));
        $this->assertTrue($this->dashboard->removeWidget($widget));
        $this->assertFalse($this->dashboard->hasWidget($widget));
    }

    public function testCreatedAt()
    {
        $this->assertNull($this->dashboard->getCreatedAt());
        $date = new \DateTime();
        $this->assertEquals($this->dashboard, $this->dashboard->setCreatedAt($date));
        $this->assertEquals($date, $this->dashboard->getCreatedAt());
    }

    public function testUpdatedAt()
    {
        $this->assertNull($this->dashboard->getUpdatedAt());
        $date = new \DateTime();
        $this->assertEquals($this->dashboard, $this->dashboard->setUpdatedAt($date));
        $this->assertEquals($date, $this->dashboard->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->dashboard->getCreatedAt());
        $this->dashboard->prePersist();
        $this->assertInstanceOf(\DateTime::class, $this->dashboard->getCreatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->dashboard->getUpdatedAt());
        $this->dashboard->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->dashboard->getUpdatedAt());
    }
}
