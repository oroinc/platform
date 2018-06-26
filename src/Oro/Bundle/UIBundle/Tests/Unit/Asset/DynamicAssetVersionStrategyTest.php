<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Asset;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionStrategy;

class DynamicAssetVersionStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider applyVersionProvider
     */
    public function testApplyVersion($expectedPath, $path, $format = null, $dynamicVersion = '')
    {
        $assetVersionManager  = $this->getMockBuilder('Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $assetVersionStrategy = new DynamicAssetVersionStrategy(
            '123',
            $format
        );
        $assetVersionStrategy->setAssetVersionManager($assetVersionManager);
        $assetVersionStrategy->setAssetPackageName('test_package');

        $assetVersionManager->expects($this->once())
            ->method('getAssetVersion')
            ->with('test_package')
            ->willReturn($dynamicVersion);

        $this->assertEquals(
            $expectedPath,
            $assetVersionStrategy->applyVersion($path)
        );
    }

    public function applyVersionProvider()
    {
        return [
            ['/css/test.css?123', '/css/test.css'],
            ['css/test.css?123', 'css/test.css'],
            ['/css/test.css?123-456', '/css/test.css', null, '456'],
            ['css/test.css?123-456', 'css/test.css', null, '456'],
            ['/css/test.css?version=123', '/css/test.css', '%s?version=%s'],
            ['css/test.css?version=123', 'css/test.css', '%s?version=%s'],
            ['/css/test.css?version=123-456', '/css/test.css', '%s?version=%s', '456'],
            ['css/test.css?version=123-456', 'css/test.css', '%s?version=%s', '456'],
            ['/123/css/test.css', '/css/test.css', '%2$s/%1$s'],
            ['123/css/test.css', 'css/test.css', '%2$s/%1$s'],
            ['/123-456/css/test.css', '/css/test.css', '%2$s/%1$s', '456'],
            ['123-456/css/test.css', 'css/test.css', '%2$s/%1$s', '456'],
        ];
    }
}
