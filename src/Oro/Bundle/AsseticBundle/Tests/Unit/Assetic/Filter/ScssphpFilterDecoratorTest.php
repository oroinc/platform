<?php

namespace Oro\Bundle\AsseticBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\ScssphpFilter;

class ScssphpFilterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScssphpFilterDecorator */
    protected $scssphpFilterDecorator;

    /** @var ScssphpFilter|\PHPUnit\Framework\MockObject\MockObject */
    protected $scssphpFilter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->scssphpFilter = $this->getMockBuilder(ScssphpFilter::class)->getMock();

        $this->scssphpFilterDecorator = new ScssphpFilterDecorator($this->scssphpFilter);
    }

    public function testEnableCompass()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('enableCompass')
            ->with(true);

        $this->scssphpFilterDecorator->enableCompass(true);
    }

    public function testIsCompassEnabled()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('isCompassEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->scssphpFilterDecorator->isCompassEnabled());
    }

    public function testSetFormatter()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('setFormatter')
            ->with('formatter');

        $this->scssphpFilterDecorator->setFormatter('formatter');
    }

    public function testSetVariables()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('setVariables')
            ->with([]);

        $this->scssphpFilterDecorator->setVariables([]);
    }

    public function testAddVariable()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('addVariable')
            ->with('variable');

        $this->scssphpFilterDecorator->addVariable('variable');
    }

    public function testSetImportPaths()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('setImportPaths')
            ->with([]);

        $this->scssphpFilterDecorator->setImportPaths([]);
    }

    public function testAddImportPath()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('addImportPath')
            ->with('path');

        $this->scssphpFilterDecorator->addImportPath('path');
    }

    public function testRegisterFunction()
    {
        $this->scssphpFilter
            ->expects($this->once())
            ->method('registerFunction')
            ->with('name', []);

        $this->scssphpFilterDecorator->registerFunction('name', []);
    }

    public function testGetChildren()
    {
        /** @var AssetFactory|\PHPUnit\Framework\MockObject\MockObject $factory */
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
        /** @var AssetInterface|\PHPUnit\Framework\MockObject\MockObject $asset */
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $this->scssphpFilter
            ->expects($this->once())
            ->method('filterLoad')
            ->with($asset);

        $this->scssphpFilterDecorator->filterLoad($asset);
    }

    public function filterDump()
    {
        /** @var AssetInterface|\PHPUnit\Framework\MockObject\MockObject $asset */
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $this->scssphpFilter
            ->expects($this->once())
            ->method('filterDump')
            ->with($asset);

        $this->scssphpFilterDecorator->filterDump($asset);
    }
}
