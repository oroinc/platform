<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutFactoryInterface;

class LayoutManagerTest extends \PHPUnit\Framework\TestCase
{
    private LayoutBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder;

    private LayoutManager $manager;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(LayoutBuilderInterface::class);

        $factory = $this->createMock(LayoutFactoryInterface::class);
        $factory->expects(self::any())
            ->method('createLayoutBuilder')
            ->willReturn($this->builder);

        $factoryBuilder = $this->createMock(LayoutFactoryBuilderInterface::class);
        $factoryBuilder->expects(self::any())
            ->method('getLayoutFactory')
            ->willReturn($factory);

        $this->manager = new LayoutManager($factoryBuilder);
    }

    public function testGetLayout(): void
    {
        $context = new LayoutContext();
        $layout = $this->createMock(Layout::class);

        $this->builder->expects(self::once())
            ->method('getLayout')
            ->with($context, 'root_id')
            ->willReturn($layout);

        $this->builder->expects(self::once())
            ->method('add')
            ->with('root', null, 'root');

        $this->manager->getLayout($context, 'root_id');
    }

    public function testRender(): void
    {
        $layout = $this->createMock(Layout::class);
        $layout->expects(self::once())
            ->method('render')
            ->willReturn('rendered text');
        $this->builder->expects(self::once())
            ->method('getLayout')
            ->with(
                self::callback(function (LayoutContext $context) {
                    $this->assertInstanceOf(LayoutContext::class, $context);

                    return true;
                }),
                null
            )
            ->willReturn($layout);

        $this->builder->expects(self::once())
            ->method('add')
            ->with('root', null, 'root');

        self::assertEquals('rendered text', $this->manager->render(['foo' => 'bar'], ['foo']));
    }
}
