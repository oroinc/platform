<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Bundle\ApiBundle\Tests\Unit\Stub\ResourceStub;
use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Oro\Component\Testing\TempDirExtension;

class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('ConfigCache');
        self::assertFileDoesNotExist($this->cacheFile);
    }

    public function debugModeProvider()
    {
        return [
            'dev'  => [true],
            'prod' => [false]
        ];
    }

    /**
     * @dataProvider debugModeProvider
     */
    public function testCacheIsNotValidIfNothingHasBeenCached($debug)
    {
        $cache = new ConfigCache($this->cacheFile, $debug);

        self::assertFalse($cache->isFresh());
    }

    public function testIsAlwaysFreshInProduction()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = new ConfigCache($this->cacheFile, false);
        $cache->write('', [$staleResource]);

        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    /**
     * @dataProvider debugModeProvider
     */
    public function testIsFreshWhenMetadataIsEmptyArray($debug)
    {
        $cache = new ConfigCache($this->cacheFile, $debug);
        $cache->write('', []);
        self::assertTrue($cache->isFresh());
        if ($debug) {
            self::assertFileExists($cache->getPath() . '.meta');
        } else {
            self::assertFileDoesNotExist($cache->getPath() . '.meta');
        }
    }

    public function testIsFreshWhenNoMetadataInDebug()
    {
        $cache = new ConfigCache($this->cacheFile, false);
        $cache->write('');
        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    public function testFreshResourceInDebug()
    {
        $freshResource = new ResourceStub();
        $freshResource->setFresh(true);

        $cache = new ConfigCache($this->cacheFile, true);
        $cache->write('', [$freshResource]);

        self::assertTrue($cache->isFresh());
    }

    public function testStaleResourceInDebug()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = new ConfigCache($this->cacheFile, true);
        $cache->write('', [$staleResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testFreshResourceInDebugAndHasFreshDependencies()
    {
        $freshResource = new ResourceStub();
        $freshResource->setFresh(true);

        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(true);
        $dependency2->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(true);

        $cache = new ConfigCache($this->cacheFile, true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$freshResource]);

        self::assertTrue($cache->isFresh());
    }

    public function testFreshResourceInDebugAndHasStaleDependencies()
    {
        $freshResource = new ResourceStub();
        $freshResource->setFresh(true);

        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(false);
        $dependency2->expects(self::never())
            ->method('isCacheFresh');

        $cache = new ConfigCache($this->cacheFile, true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$freshResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testStaleResourceInDebugAndHasDependencies()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::never())
            ->method('isCacheFresh');
        $dependency2->expects(self::never())
            ->method('isCacheFresh');

        $cache = new ConfigCache($this->cacheFile, true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$staleResource]);

        self::assertFalse($cache->isFresh());
    }
}
