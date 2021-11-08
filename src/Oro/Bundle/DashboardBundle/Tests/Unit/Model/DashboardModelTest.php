<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DashboardModelTest extends \PHPUnit\Framework\TestCase
{
    /** @var Dashboard|\PHPUnit\Framework\MockObject\MockObject */
    private $dashboardEntity;

    /** @var ArrayCollection */
    private $widgets;

    private array $config = [
        'label' => 'Dashboard label'
    ];

    /** @var DashboardModel */
    private $dashboardModel;

    protected function setUp(): void
    {
        $this->dashboardEntity = $this->createMock(Dashboard::class);

        $this->widgets = new ArrayCollection([
            $this->createMock(WidgetModel::class),
            $this->createMock(WidgetModel::class)
        ]);

        $this->dashboardModel = new DashboardModel($this->dashboardEntity, $this->widgets, $this->config);
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->config, $this->dashboardModel->getConfig());
    }

    public function testGetEntity()
    {
        $this->assertEquals($this->dashboardEntity, $this->dashboardModel->getEntity());
    }

    public function testGetWidgets()
    {
        $this->assertEquals($this->widgets, $this->dashboardModel->getWidgets());
    }

    public function testGetId()
    {
        $id = 100;
        $this->dashboardEntity->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $this->assertEquals($id, $this->dashboardModel->getId());
    }

    public function testGetName()
    {
        $name = 'Name';
        $this->dashboardEntity->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertEquals($name, $this->dashboardModel->getName());
    }

    public function testSetName()
    {
        $name = 'Name';
        $this->dashboardEntity->expects($this->once())
            ->method('setName')
            ->with($name);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setName($name));
    }

    public function testGetStartDashboard()
    {
        $dashboard = $this->createMock(Dashboard::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getStartDashboard')
            ->willReturn($dashboard);

        $this->assertEquals($dashboard, $this->dashboardModel->getStartDashboard());
    }

    public function testSetStartDashboard()
    {
        $dashboard = $this->createMock(Dashboard::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setStartDashboard')
            ->with($dashboard);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setStartDashboard($dashboard));
    }

    public function testAddWidget()
    {
        $widgetEntity = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(WidgetModel::class);

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $this->dashboardEntity->expects($this->once())
            ->method('addWidget')
            ->with($widgetEntity);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->addWidget($widgetModel));
        $this->assertEquals($widgetModel, $this->widgets[2]);
    }

    /**
     * @dataProvider addWidgetRecalculatePositionDataProvider
     */
    public function testAddWidgetMinColumnPosition(array $layoutPositions, $column, array $expectedLayoutPosition)
    {
        $widgetEntity = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(WidgetModel::class);

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $widgetModel->expects($this->once())
            ->method('setLayoutPosition')
            ->with($expectedLayoutPosition);

        $this->dashboardEntity->expects($this->once())
            ->method('addWidget')
            ->with($widgetEntity);

        foreach ($layoutPositions as $index => $layoutPosition) {
            $this->widgets[$index]->expects($this->once())
                ->method('getLayoutPosition')
                ->willReturn($layoutPosition);
        }

        $this->dashboardModel->addWidget($widgetModel, $column);
        $this->assertEquals($widgetModel, $this->widgets[2]);
    }

    public function addWidgetRecalculatePositionDataProvider(): array
    {
        return [
            [
                'layoutPositions' => [
                    [0, 50],
                    [0, 100],
                ],
                'column' => 0,
                'expectedLayoutPosition' => [0, 0]
            ],
            [
                'layoutPositions' => [
                    [0, 50],
                    [1, 0],
                ],
                'column' => 0,
                'expectedLayoutPosition' => [0, 0]
            ],
            [
                'layoutPositions' => [
                    [1, -100],
                    [1, 100],
                ],
                'column' => 1,
                'expectedLayoutPosition' => [1, -101]
            ],
            [
                'layoutPositions' => [
                    [0, -100],
                    [0, 100],
                ],
                'column' => 0,
                'expectedLayoutPosition' => [0, -101]
            ],
        ];
    }

    public function testGetWidgetById()
    {
        $firstWidgetId = 100;
        $secondWidgetId = 101;
        $this->widgets[0]->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($firstWidgetId);
        $this->widgets[1]->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($secondWidgetId);

        $this->assertEquals($this->widgets[1], $this->dashboardModel->getWidgetById($secondWidgetId));
        $this->assertNull($this->dashboardModel->getWidgetById('undefined'));
    }

    /**
     * @dataProvider getOrderedColumnWidgetsDataProvider
     */
    public function testGetOrderedColumnWidgets(
        $column,
        $appendGreater,
        $appendLesser,
        $layoutPositions,
        $expectedLayoutPositions
    ) {
        $this->widgets->clear();
        foreach ($layoutPositions as $layoutPosition) {
            $widget = $this->createMock(WidgetModel::class);
            $widget->expects($this->any())
                ->method('getLayoutPosition')
                ->willReturn($layoutPosition);
            $this->widgets->add($widget);
        }

        $actualLayoutPositions = [];
        $orderedWidgets = $this->dashboardModel->getOrderedColumnWidgets($column, $appendGreater, $appendLesser);
        foreach ($orderedWidgets as $widget) {
            $actualLayoutPositions[] = $widget->getLayoutPosition();
        }

        $this->assertSame($expectedLayoutPositions, $actualLayoutPositions);
    }

    public function getOrderedColumnWidgetsDataProvider(): array
    {
        return [
            [
                'column' => 0,
                'appendGreater' => true,
                'appendLesser' => true,
                'layoutPositions' => [[2, 0], [1, 0], [0, 2], [0, 1], [0, 0]],
                'expectedLayoutPositions' => [[0, 0], [0, 1], [0, 2], [1, 0], [2, 0]],
            ],
            [
                'column' => 1,
                'appendGreater' => true,
                'appendLesser' => false,
                'layoutPositions' => [[2, 0], [1, 0], [0, 2], [0, 1], [0, 0]],
                'expectedLayoutPositions' => [[1, 0], [2, 0]],
            ],
            [
                'column' => 1,
                'appendGreater' => false,
                'appendLesser' => false,
                'layoutPositions' => [[2, 0], [1, 0], [0, 2], [0, 1], [0, 0]],
                'expectedLayoutPositions' => [[1, 0]],
            ],
            [
                'column' => 0,
                'appendGreater' => false,
                'appendLesser' => false,
                'layoutPositions' => [[2, 0], [1, 0], [0, 2], [0, 1], [0, 0]],
                'expectedLayoutPositions' => [[0, 0], [0, 1], [0, 2]],
            ],
        ];
    }

    public function testHasWidget()
    {
        $widgetModel = $this->createMock(WidgetModel::class);
        $widgetEntity = $this->createMock(Widget::class);

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $this->dashboardEntity->expects($this->once())
            ->method('hasWidget')
            ->with($widgetEntity)
            ->willReturn(true);

        $this->assertTrue($this->dashboardModel->hasWidget($widgetModel));
    }

    public function testIsDefault()
    {
        $isDefault = true;
        $this->dashboardEntity->expects($this->once())
            ->method('getIsDefault')
            ->willReturn($isDefault);

        $this->assertEquals($isDefault, $this->dashboardModel->isDefault());
    }

    public function testSetIsDefault()
    {
        $isDefault = true;
        $this->dashboardEntity->expects($this->once())
            ->method('setIsDefault')
            ->with($isDefault);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setIsDefault($isDefault));
    }

    public function testGetOwner()
    {
        $owner = $this->createMock(User::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);

        $this->assertEquals($owner, $this->dashboardModel->getOwner());
    }

    public function testSetOwner()
    {
        $owner = $this->createMock(User::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setOwner')
            ->with($owner);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setOwner($owner));
    }

    public function testGetOrganization()
    {
        $organization = $this->createMock(Organization::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertEquals($organization, $this->dashboardModel->getOrganization());
    }

    public function testSetOrganization()
    {
        $organization = $this->createMock(Organization::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setOrganization')
            ->with($organization);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setOrganization($organization));
    }

    public function testGetLabelFromEntity()
    {
        $label = 'Label';
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->dashboardModel->getLabel());
    }

    public function testSetLabel()
    {
        $label = 'Label';
        $this->dashboardEntity->expects($this->once())
            ->method('setLabel')
            ->with($label);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setLabel($label));
    }

    public function testGetLabelFromConfig()
    {
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->willReturn(null);

        $this->assertEquals($this->config['label'], $this->dashboardModel->getLabel());
    }

    public function testGetTemplate()
    {
        $this->assertEquals(DashboardModel::DEFAULT_TEMPLATE, $this->dashboardModel->getTemplate());
    }
}
