<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class DataCollectorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutDataCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $dataCollector;

    /** @var DataCollectorExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->dataCollector = $this->createMock(LayoutDataCollector::class);

        $this->extension = new DataCollectorExtension($this->dataCollector);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishView()
    {
        $view = $this->createMock(BlockView::class);
        $block = $this->createMock(BlockInterface::class);

        $this->dataCollector->expects($this->once())
            ->method('collectBlockView')
            ->with($block, $view);

        $this->extension->finishView($view, $block);
    }
}
