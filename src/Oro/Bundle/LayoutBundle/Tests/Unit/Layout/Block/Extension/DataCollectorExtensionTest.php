<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataCollectorExtensionTest extends TestCase
{
    private LayoutDataCollector&MockObject $dataCollector;
    private DataCollectorExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataCollector = $this->createMock(LayoutDataCollector::class);

        $this->extension = new DataCollectorExtension($this->dataCollector);
    }

    public function testGetExtendedType(): void
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishView(): void
    {
        $view = $this->createMock(BlockView::class);
        $block = $this->createMock(BlockInterface::class);

        $this->dataCollector->expects($this->once())
            ->method('collectBlockView')
            ->with($block, $view);

        $this->extension->finishView($view, $block);
    }
}
