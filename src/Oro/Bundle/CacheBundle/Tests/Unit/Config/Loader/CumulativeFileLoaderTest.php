<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeFileLoader;

class CumulativeFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $relativeFilePath = 'Tests/Unit/Config/Loader/Fixtures/test.yml';

        /** @var CumulativeFileLoader|\PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->getMockForAbstractClass(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeFileLoader',
            [$relativeFilePath]
        );

        $data = ['test' => 123];
        $bundleName = 'TestBundle';
        $bundlePath = realpath(__DIR__ . '/../../../..');
        $expectedFilePath = $bundlePath . '/' . $relativeFilePath;
        $expectedFilePath = str_replace('/', DIRECTORY_SEPARATOR, $expectedFilePath);

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($bundleName));
        $bundle->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($bundlePath));

        $expectedResource = new CumulativeResourceInfo(
            $bundleName,
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

        $resource = $loader->load($bundle);
        $this->assertEquals($expectedResource, $resource);
    }
}
