<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\DashboardBundle\Model\DashboardModel;

class DashboardModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardEntity;

    /**
     * @var ArrayCollection
     */
    protected $widgets;

    /**
     * @var array
     */
    protected $config = array(
        'label' => 'Dashboard label'
    );

    /**
     * @var DashboardModel
     */
    protected $dashboardModel;

    protected function setUp()
    {
        $this->dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $this->widgets = new ArrayCollection(
            array(
                $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
                    ->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
                    ->disableOriginalConstructor()
                    ->getMock(),
            )
        );

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
            ->will($this->returnValue($id));

        $this->assertEquals($id, $this->dashboardModel->getId());
    }

    public function testGetName()
    {
        $name = 'Name';
        $this->dashboardEntity->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $this->assertEquals($name, $this->dashboardModel->getName());
    }

    public function testAddWidget()
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->dashboardEntity->expects($this->once())
            ->method('addWidget')
            ->with($widgetEntity);

        $this->dashboardModel->addWidget($widgetModel);
        $this->assertEquals($widgetModel, $this->widgets[2]);
    }

    /**
     * @dataProvider addWidgetRecalculatePositionDataProvider
     */
    public function testAddWidgetRecalculatePosition(array $layoutPositions, array $expectedLayoutPosition)
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $widgetModel->expects($this->once())
            ->method('setLayoutPosition')
            ->with($expectedLayoutPosition);

        $this->dashboardEntity->expects($this->once())
            ->method('addWidget')
            ->with($widgetEntity);

        foreach ($layoutPositions as $index => $layoutPosition) {
            $this->widgets[$index]->expects($this->once())
                ->method('getLayoutPosition')
                ->will($this->returnValue($layoutPosition));
        }

        $this->dashboardModel->addWidget($widgetModel, true);
        $this->assertEquals($widgetModel, $this->widgets[2]);

    }

    public function addWidgetRecalculatePositionDataProvider()
    {
        return array(
            array(
                'layoutPositions' => array(
                    array(0, 50),
                    array(0, 100),
                ),
                'expectedLayoutPosition' => array(0, 0)
            ),
            array(
                'layoutPositions' => array(
                    array(1, -100),
                    array(1, 100),
                ),
                'expectedLayoutPosition' => array(0, 0)
            ),
            array(
                'layoutPositions' => array(
                    array(0, -100),
                    array(0, 100),
                ),
                'expectedLayoutPosition' => array(0, -101)
            ),
        );
    }

    public function testGetWidgetById()
    {
        $firstWidgetId = 100;
        $secondWidgetId = 101;
        $this->widgets[0]->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($firstWidgetId));
        $this->widgets[1]->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($secondWidgetId));

        $this->assertEquals($this->widgets[1], $this->dashboardModel->getWidgetById($secondWidgetId));
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
            $widget = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
                ->disableOriginalConstructor()
                ->getMock();
            $widget->expects($this->any())->method('getLayoutPosition')->will($this->returnValue($layoutPosition));
            $this->widgets->add($widget);
        }

        $actualLayoutPositions = array();
        $orderedWidgets = $this->dashboardModel->getOrderedColumnWidgets($column, $appendGreater, $appendLesser);
        foreach ($orderedWidgets as $widget) {
            $actualLayoutPositions[] = $widget->getLayoutPosition();
        }

        $this->assertSame($expectedLayoutPositions, $actualLayoutPositions);
    }

    public function getOrderedColumnWidgetsDataProvider()
    {
        return array(
            array(
                'column' => 0,
                'appendGreater' => true,
                'appendLesser' => true,
                'layoutPositions' => array(array(2, 0), array(1, 0), array(0, 2), array(0, 1), array(0, 0)),
                'expectedLayoutPositions' => array(array(2, 0), array(1, 0), array(0, 0), array(0, 1), array(0, 2)),
            ),
            array(
                'column' => 1,
                'appendGreater' => true,
                'appendLesser' => false,
                'layoutPositions' => array(array(2, 0), array(1, 0), array(0, 2), array(0, 1), array(0, 0)),
                'expectedLayoutPositions' => array(array(1, 0), array(2, 0)),
            ),
            array(
                'column' => 1,
                'appendGreater' => false,
                'appendLesser' => false,
                'layoutPositions' => array(array(2, 0), array(1, 0), array(0, 2), array(0, 1), array(0, 0)),
                'expectedLayoutPositions' => array(array(1, 0)),
            ),
            array(
                'column' => 0,
                'appendGreater' => false,
                'appendLesser' => false,
                'layoutPositions' => array(array(2, 0), array(1, 0), array(0, 2), array(0, 1), array(0, 0)),
                'expectedLayoutPositions' => array(array(0, 0), array(0, 1), array(0, 2)),
            ),
        );
    }

    public function testHasWidget()
    {
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');

        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->dashboardEntity->expects($this->once())
            ->method('hasWidget')
            ->with($widgetEntity)
            ->will($this->returnValue(true));

        $this->assertTrue($this->dashboardModel->hasWidget($widgetModel));
    }

    public function testGetLabelFromEntity()
    {
        $label = 'Label';
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($label));

        $this->assertEquals($label, $this->dashboardModel->getLabel());
    }

    public function testGetLabelFromConfig()
    {
        $this->dashboardEntity->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue(null));

        $this->assertEquals($this->config['label'], $this->dashboardModel->getLabel());
    }

    public function testGetTemplate()
    {
        $this->assertEquals(DashboardModel::DEFAULT_TEMPLATE, $this->dashboardModel->getTemplate());
    }
}
