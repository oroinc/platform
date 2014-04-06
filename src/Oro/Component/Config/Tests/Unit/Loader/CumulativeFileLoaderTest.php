<?php

namespace Oro\Component\Config\Tests\Unit\Loader;


use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        /** @var CumulativeFileLoader|\PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->getMockForAbstractClass(
            'Oro\Component\Config\Loader\CumulativeFileLoader',
            [$relativeFilePath]
        );

        $data             = ['test' => 123];
        $bundle           = new TestBundle1();
        $bundleDir        = dirname((new \ReflectionClass($bundle))->getFileName());
        $expectedFilePath = $bundleDir . '/' . $relativeFilePath;
        $expectedFilePath = str_replace('/', DIRECTORY_SEPARATOR, $expectedFilePath);

        $expectedResource = new CumulativeResourceInfo(
            get_class($bundle),
            'test',
            $expectedFilePath,
            $data
        );

        $loader->expects($this->once())
            ->method('loadFile')
            ->with($expectedFilePath)
            ->will($this->returnValue($data));

        $this->assertEquals($relativeFilePath, $loader->getResource());

        $resource = $loader->load(get_class($bundle), $bundleDir);
        $this->assertEquals($expectedResource, $resource);
    }
}
