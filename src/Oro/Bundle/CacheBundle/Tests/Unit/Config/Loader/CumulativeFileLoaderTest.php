<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeFileLoader;
use Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader\Fixtures\Bundle\TestBundle\TestBundle;

class CumulativeFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        /** @var CumulativeFileLoader|\PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->getMockForAbstractClass(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeFileLoader',
            [$relativeFilePath]
        );

        $data = ['test' => 123];
        $bundle = new TestBundle();
        $expectedFilePath = $bundle->getPath() . '/' . $relativeFilePath;
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

        $resource = $loader->load(get_class($bundle), $bundle->getPath());
        $this->assertEquals($expectedResource, $resource);
    }
}
