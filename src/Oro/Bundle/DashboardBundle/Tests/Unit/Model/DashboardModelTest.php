<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DashboardModelTest extends TestCase
{
    private Dashboard&MockObject $dashboardEntity;
    private ArrayCollection $widgets;

    private array $config = [
        'label' => 'Dashboard label'
    ];

    private DashboardModel $dashboardModel;

    #[\Override]
    protected function setUp(): void
    {
        $this->dashboardEntity = $this->createMock(Dashboard::class);

        $this->widgets = new ArrayCollection([
            $this->createMock(WidgetModel::class),
            $this->createMock(WidgetModel::class)
        ]);

        $this->dashboardModel = new DashboardModel($this->dashboardEntity, $this->widgets, $this->config);
    }

    public function testGetConfig(): void
    {
        $this->assertEquals($this->config, $this->dashboardModel->getConfig());
    }

    public function testGetEntity(): void
    {
        $this->assertEquals($this->dashboardEntity, $this->dashboardModel->getEntity());
    }

    public function testGetWidgets(): void
    {
        $this->assertEquals($this->widgets, $this->dashboardModel->getWidgets());
    }

    public function testGetId(): void
    {
        $id = 100;
        $this->dashboardEntity->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $this->assertEquals($id, $this->dashboardModel->getId());
    }

    public function testGetName(): void
    {
        $name = 'Name';
        $this->dashboardEntity->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertEquals($name, $this->dashboardModel->getName());
    }

    public function testSetName(): void
    {
        $name = 'Name';
        $this->dashboardEntity->expects($this->once())
            ->method('setName')
            ->with($name);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setName($name));
    }

    public function testGetStartDashboard(): void
    {
        $dashboard = $this->createMock(Dashboard::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getStartDashboard')
            ->willReturn($dashboard);

        $this->assertEquals($dashboard, $this->dashboardModel->getStartDashboard());
    }

    public function testSetStartDashboard(): void
    {
        $dashboard = $this->createMock(Dashboard::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setStartDashboard')
            ->with($dashboard);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setStartDashboard($dashboard));
    }

    public function testAddWidget(): void
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
    public function testAddWidgetMinColumnPosition(array $layoutPositions, $column, array $expectedLayoutPosition): void
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

    public function testGetWidgetById(): void
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
    ): void {
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

    public function testHasWidget(): void
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

    public function testIsDefault(): void
    {
        $isDefault = true;
        $this->dashboardEntity->expects($this->once())
            ->method('getIsDefault')
            ->willReturn($isDefault);

        $this->assertEquals($isDefault, $this->dashboardModel->isDefault());
    }

    public function testSetIsDefault(): void
    {
        $isDefault = true;
        $this->dashboardEntity->expects($this->once())
            ->method('setIsDefault')
            ->with($isDefault);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setIsDefault($isDefault));
    }

    public function testGetOwner(): void
    {
        $owner = $this->createMock(User::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);

        $this->assertEquals($owner, $this->dashboardModel->getOwner());
    }

    public function testSetOwner(): void
    {
        $owner = $this->createMock(User::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setOwner')
            ->with($owner);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setOwner($owner));
    }

    public function testGetOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $this->dashboardEntity->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertEquals($organization, $this->dashboardModel->getOrganization());
    }

    public function testSetOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $this->dashboardEntity->expects($this->once())
            ->method('setOrganization')
            ->with($organization);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setOrganization($organization));
    }

    public function testGetLabelFromEntity(): void
    {
        $label = 'Label';
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->dashboardModel->getLabel());
    }

    public function testSetLabel(): void
    {
        $label = 'Label';
        $this->dashboardEntity->expects($this->once())
            ->method('setLabel')
            ->with($label);

        $this->assertEquals($this->dashboardModel, $this->dashboardModel->setLabel($label));
    }

    public function testGetLabelFromConfig(): void
    {
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->willReturn(null);

        $this->assertEquals($this->config['label'], $this->dashboardModel->getLabel());
    }

    public function testGetTemplate(): void
    {
        $this->assertEquals(DashboardModel::DEFAULT_TEMPLATE, $this->dashboardModel->getTemplate());
    }
}
