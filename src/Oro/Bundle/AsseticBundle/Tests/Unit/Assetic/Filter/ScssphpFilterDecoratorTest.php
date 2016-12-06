<?php

namespace Oro\Bundle\AsseticBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\ScssphpFilter;

class ScssphpFilterDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScssphpFilterDecorator */
    protected $scssphpFilterDecorator;

    /** @var ScssphpFilter|\PHPUnit_Framework_MockObject_MockObject */
    protected $scssphpFilter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->scssphpFilter = $this->getMockBuilder(ScssphpFilter::class)->getMock();

        $this->scssphpFilterDecorator = new ScssphpFilterDecorator($this->scssphpFilter);
    }

    public function testGetChildren()
    {
        /** @var AssetFactory|\PHPUnit_Framework_MockObject_MockObject $factory */
        $factory = $this->getMockBuilder(AssetFactory::class)->disableOriginalConstructor()->getMock();
        $content = '';
        $loadPath = 'load/path';

        $this->scssphpFilter
            ->expects($this->once())
            ->method('getChildren')
            ->with($factory, $content, $loadPath)
            ->willReturn([]);

        $this->assertEquals([], $this->scssphpFilterDecorator->getChildren($factory, $content, $loadPath));
    }

    public function testFilterLoad()
    {
        /** @var AssetInterface|\PHPUnit_Framework_MockObject_MockObject $asset */
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $this->scssphpFilter
            ->expects($this->once())
            ->method('filterLoad')
            ->with($asset);

        $this->scssphpFilterDecorator->filterLoad($asset);
    }

    public function filterDump()
    {
        /** @var AssetInterface|\PHPUnit_Framework_MockObject_MockObject $asset */
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $this->scssphpFilter
            ->expects($this->once())
            ->method('filterDump')
            ->with($asset);

        $this->scssphpFilterDecorator->filterDump($asset);
    }
}
