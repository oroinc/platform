<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class WidgetModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    public static $expanded = true;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetEntity;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetState;

    /**
     * @var array
     */
    protected $config = array(
        'label' => 'Widget label'
    );

    /**
     * @var WidgetModel
     */
    protected $widgetModel;

    protected function setUp()
    {
        $this->widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $this->widgetState  = $this->getMock('Oro\Bundle\DashboardBundle\Entity\WidgetState');

        $this->widgetState
            ->expects($this->any())
            ->method('isExpanded')
            ->will($this->returnValue(self::$expanded));

        $this->widgetModel = new WidgetModel(
            $this->widgetEntity,
            $this->config,
            $this->widgetState
        );
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->config, $this->widgetModel->getConfig());
    }

    public function testGetEntity()
    {
        $this->assertEquals($this->widgetEntity, $this->widgetModel->getEntity());
    }

    public function testGetState()
    {
        $this->assertEquals($this->widgetState, $this->widgetModel->getState());
    }

    public function testGetId()
    {
        $id = 100;
        $this->widgetEntity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $this->assertEquals($id, $this->widgetModel->getId());
    }

    public function testGetName()
    {
        $name = 'Name';
        $this->widgetEntity->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $this->assertEquals($name, $this->widgetModel->getName());
    }

    public function testSetName()
    {
        $name = 'Name';
        $this->widgetEntity
            ->expects($this->once())
            ->method('setName')
            ->with($name);

        $this->widgetModel->setName($name);
    }

    public function testGetLayoutPosition()
    {
        $layoutPosition = array(1, 2);
        $this
            ->widgetEntity
            ->expects($this->once())
            ->method('getLayoutPosition')
            ->will($this->returnValue($layoutPosition));

        $this->assertEquals($layoutPosition, $this->widgetModel->getLayoutPosition());
    }

    public function testSetLayoutPosition()
    {
        $layoutPosition = array(1, 2);
        $this->widgetEntity
            ->expects($this->once())
            ->method('setLayoutPosition')
            ->with($layoutPosition);

        $this->widgetModel->setLayoutPosition($layoutPosition);
    }

    public function testIsExpanded()
    {
        $this->assertEquals(self::$expanded, $this->widgetModel->isExpanded());
    }

    public function testSetExpanded()
    {
        $expanded = true;
        $this->widgetState
            ->expects($this->once())
            ->method('setExpanded')
            ->with($expanded);

        $this->widgetModel->setExpanded($expanded);
    }
}
