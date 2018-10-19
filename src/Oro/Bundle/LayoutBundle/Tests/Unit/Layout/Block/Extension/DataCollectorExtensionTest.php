<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class DataCollectorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutDataCollector|\PHPUnit\Framework\MockObject\MockObject */
    protected $dataCollector;

    /** @var DataCollectorExtension */
    protected $extension;

    protected function setUp()
    {
        $this->dataCollector = $this->createMock(LayoutDataCollector::class);

        $this->extension = new DataCollectorExtension($this->dataCollector);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildBlock()
    {
        $blockId = 'root';
        $blockTypeName = 'root';
        $options = new Options(['optionKey' => 'optionValue']);

        /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(BlockBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($blockId));
        $builder->expects($this->once())
            ->method('getTypeName')
            ->will($this->returnValue($blockTypeName));

        $this->dataCollector
            ->expects($this->once())
            ->method('collectBuildBlockOptions')
            ->with($blockId, $blockTypeName, $options->toArray());

        $this->extension->buildBlock($builder, $options);
    }

    public function testBuildView()
    {
        /** @var BlockView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock(BlockView::class);
        /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
        $block = $this->createMock(BlockInterface::class);
        $options = new Options(['optionKey' => 'optionValue']);

        $this->dataCollector
            ->expects($this->once())
            ->method('collectBuildViewOptions')
            ->with($block, get_class($block), $options->toArray());

        $this->extension->buildView($view, $block, $options);
    }
}
