<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Asset;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Oro\Bundle\UIBundle\Asset\RuntimeAssetVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class RuntimeAssetVersionStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider applyVersionProvider
     */
    public function testApplyVersion(
        string $expectedPath,
        string $path,
        string $dynamicVersion = ''
    ): void {
        $assetVersionManager = $this->createMock(DynamicAssetVersionManager::class);
        $wrappedStrategy = $this->createMock(VersionStrategyInterface::class);
        $wrappedStrategy
            ->method('getVersion')
            ->willReturn(123);
        $assetVersionStrategy = new RuntimeAssetVersionStrategy('test_package', $wrappedStrategy, $assetVersionManager);

        $assetVersionManager->expects($this->once())
            ->method('getAssetVersion')
            ->with('test_package')
            ->willReturn($dynamicVersion);

        $this->assertEquals(
            $expectedPath,
            $assetVersionStrategy->applyVersion($path)
        );
    }

    public function applyVersionProvider(): array
    {
        return [
            ['/css/test.css?v=123', '/css/test.css'],
            ['css/test.css?v=123', 'css/test.css'],
            ['/css/test.css?v=123-456', '/css/test.css', '456'],
            ['css/test.css?v=123-456', 'css/test.css', '456'],
        ];
    }
}
