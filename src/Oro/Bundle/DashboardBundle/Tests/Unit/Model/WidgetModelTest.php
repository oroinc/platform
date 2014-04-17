<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class WidgetModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetEntity;

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
        $this->widgetModel = new WidgetModel($this->widgetEntity, $this->config);
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->config, $this->widgetModel->getConfig());
    }

    public function testGetEntity()
    {
        $this->assertEquals($this->widgetEntity, $this->widgetModel->getEntity());
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
        $this->widgetEntity->expects($this->once())
            ->method('setName')
            ->with($name);

        $this->widgetModel->setName($name);
    }

    public function testGetLayoutPosition()
    {
        $layoutPosition = array(1, 2);
        $this->widgetEntity->expects($this->once())
            ->method('getLayoutPosition')
            ->will($this->returnValue($layoutPosition));

        $this->assertEquals($layoutPosition, $this->widgetModel->getLayoutPosition());
    }

    public function testSetLayoutPosition()
    {
        $layoutPosition = array(1, 2);
        $this->widgetEntity->expects($this->once())
            ->method('setLayoutPosition')
            ->with($layoutPosition);

        $this->widgetModel->setLayoutPosition($layoutPosition);
    }

    public function testIsExpanded()
    {
        $expanded = true;
        $this->widgetEntity->expects($this->once())
            ->method('isExpanded')
            ->will($this->returnValue($expanded));

        $this->assertEquals($expanded, $this->widgetModel->isExpanded());
    }

    public function testSetExpanded()
    {
        $expanded = true;
        $this->widgetEntity->expects($this->once())
            ->method('setExpanded')
            ->with($expanded);

        $this->widgetModel->setExpanded($expanded);
    }
}
