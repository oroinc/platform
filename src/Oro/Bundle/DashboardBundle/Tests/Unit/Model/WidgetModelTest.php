<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class WidgetModelTest extends \PHPUnit\Framework\TestCase
{
    private static $expanded = true;

    /** @var Widget|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetEntity;

    /** @var WidgetState|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetState;

    private array $config = [
        'label' => 'Widget label'
    ];

    /** @var WidgetModel */
    private $widgetModel;

    protected function setUp(): void
    {
        $this->widgetEntity = $this->createMock(Widget::class);
        $this->widgetState = $this->createMock(WidgetState::class);

        $this->widgetState->expects($this->any())
            ->method('isExpanded')
            ->willReturn(self::$expanded);

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
            ->willReturn($id);

        $this->assertEquals($id, $this->widgetModel->getId());
    }

    public function testGetName()
    {
        $name = 'Name';
        $this->widgetEntity->expects($this->once())
            ->method('getName')
            ->willReturn($name);

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
        $layoutPosition = [1, 2];
        $this->widgetEntity->expects($this->once())
            ->method('getLayoutPosition')
            ->willReturn($layoutPosition);

        $this->assertEquals($layoutPosition, $this->widgetModel->getLayoutPosition());
    }

    public function testSetLayoutPosition()
    {
        $layoutPosition = [1, 2];
        $this->widgetEntity->expects($this->once())
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
        $this->widgetState->expects($this->once())
            ->method('setExpanded')
            ->with($expanded);

        $this->widgetModel->setExpanded($expanded);
    }
}
