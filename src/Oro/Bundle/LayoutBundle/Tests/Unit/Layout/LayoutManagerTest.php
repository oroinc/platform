<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutFactoryInterface;

class LayoutManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $builder;

    /** @var LayoutContextHolder|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextHolder;

    /** @var LayoutManager */
    private $manager;

    /** @var LayoutDataCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutDataCollector;

    protected function setUp()
    {
        $this->builder = $this->createMock(LayoutBuilderInterface::class);
        $this->contextHolder = $this->createMock(LayoutContextHolder::class);

        $factory = $this->createMock(LayoutFactoryInterface::class);
        $factory->expects($this->any())
            ->method('createLayoutBuilder')
            ->willReturn($this->builder);

        $factoryBuilder = $this->createMock(LayoutFactoryBuilderInterface::class);
        $factoryBuilder->expects($this->any())
            ->method('getLayoutFactory')
            ->willReturn($factory);

        $this->layoutDataCollector = $this->getMockBuilder(LayoutDataCollector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new LayoutManager($factoryBuilder, $this->contextHolder, $this->layoutDataCollector);
    }

    public function testGetLayout()
    {
        $context = new LayoutContext();
        $layout = $this->createMock(Layout::class);

        $this->builder->expects($this->once())
            ->method('getLayout')
            ->with($context, 'root_id')
            ->willReturn($layout);

        $this->builder->expects($this->once())
            ->method('add')
            ->with('root', null, 'root');

        $this->builder->expects($this->once())
            ->method('getNotAppliedActions')
            ->willReturn([]);

        $this->layoutDataCollector->expects($this->once())
            ->method('setNotAppliedActions');

        $this->contextHolder->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->manager->getLayout($context, 'root_id');
    }

    public function testRender()
    {
        $layout = $this->createMock(Layout::class);
        $layout->expects($this->once())
            ->method('render')
            ->willReturn('rendered text');
        $this->builder->expects($this->once())
            ->method('getLayout')
            ->with(
                $this->callback(
                    function (LayoutContext $context) {
                        $this->assertInstanceOf(LayoutContext::class, $context);

                        return true;
                    }
                ),
                null
            )
            ->willReturn($layout);

        $this->builder->expects($this->once())
            ->method('add')
            ->with('root', null, 'root');

        $this->builder->expects($this->once())
            ->method('getNotAppliedActions')
            ->willReturn([]);

        $this->layoutDataCollector->expects($this->once())
            ->method('setNotAppliedActions');

        $this->contextHolder->expects($this->once())
            ->method('setContext')
            ->with(
                $this->callback(
                    function ($context) {
                        $this->assertInstanceOf(LayoutContext::class, $context);

                        return true;
                    }
                )
            );

        $this->assertEquals('rendered text', $this->manager->render(['foo' => 'bar'], ['foo']));
    }
}
