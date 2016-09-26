<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class DataCollectorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutDataCollector|\PHPUnit_Framework_MockObject_MockObject */
    protected $dataCollector;

    /** @var DataCollectorExtension */
    protected $extension;

    protected function setUp()
    {
        $this->dataCollector = $this->getMock(LayoutDataCollector::class, [], [], '', false);

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

        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(BlockBuilderInterface::class);
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
        /** @var BlockView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(BlockView::class);
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock(BlockInterface::class);
        $options = new Options(['optionKey' => 'optionValue']);

        $this->dataCollector
            ->expects($this->once())
            ->method('collectBuildViewOptions')
            ->with($block, get_class($block), $options->toArray());

        $this->extension->buildView($view, $block, $options);
    }
}
