<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Oro\Component\Config\Tests\Unit\Fixtures\ConfigCacheStub;
use Oro\Component\Config\Tests\Unit\Fixtures\PhpArrayConfigProviderStub;
use Oro\Component\Config\Tests\Unit\Fixtures\ResourceStub;
use Oro\Component\Testing\TempDirExtension;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $cacheFile = '';

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('ConfigCache');
        self::assertFileDoesNotExist($this->cacheFile);
    }

    private function getConfigCache(bool $debug): ConfigCacheStub
    {
        return new ConfigCacheStub($this->cacheFile, $debug);
    }

    public function debugModeProvider(): array
    {
        return [
            'dev'  => [true],
            'prod' => [false]
        ];
    }

    /**
     * @dataProvider debugModeProvider
     */
    public function testCacheIsNotValidIfNothingHasBeenCached($debug): void
    {
        $cache = $this->getConfigCache($debug);

        self::assertFalse($cache->isFresh());
    }

    public function testIsAlwaysFreshInProduction(): void
    {
        $staleResource = new ResourceStub(__FUNCTION__);
        $staleResource->setFresh(false);

        $cache = $this->getConfigCache(false);
        $cache->write('', [$staleResource]);

        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    /**
     * @dataProvider debugModeProvider
     */
    public function testIsFreshWhenMetadataIsEmptyArray($debug): void
    {
        $cache = $this->getConfigCache($debug);
        $cache->write('', []);
        self::assertTrue($cache->isFresh());
        if ($debug) {
            self::assertFileExists($cache->getPath() . '.meta');
        } else {
            self::assertFileDoesNotExist($cache->getPath() . '.meta');
        }
    }

    public function testIsFreshWhenNoMetadataInDebug(): void
    {
        $cache = $this->getConfigCache(false);
        $cache->write('');
        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    public function testFreshResourceInDebug(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
        $freshResource->setFresh(true);

        $cache = $this->getConfigCache(true);
        $cache->write('', [$freshResource]);

        self::assertTrue($cache->isFresh());
    }

    public function testFreshnessIsNotCachedInDebug(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
        $freshResource->setFresh(false);

        $cache = $this->getConfigCache(true);
        $cache->write('', [$freshResource]);
        self::assertFalse($cache->isFresh());

        $freshResource->setFresh(true);
        $cache->write('', [$freshResource]);
        self::assertTrue($cache->isFresh());
    }

    public function testStaleResourceInDebug(): void
    {
        $staleResource = new ResourceStub(__FUNCTION__);
        $staleResource->setFresh(false);

        $cache = $this->getConfigCache(true);
        $cache->write('', [$staleResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testFreshResourceInDebugAndHasFreshDependencies(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
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

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$freshResource]);

        self::assertTrue($cache->isFresh());
    }

    public function testFreshResourceInDebugAndHasStaleDependencies(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
        $freshResource->setFresh(true);

        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(false);
        $dependency2->expects(self::never())
            ->method('isCacheFresh');

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$freshResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testStaleResourceInDebugAndHasDependencies(): void
    {
        $staleResource = new ResourceStub(__FUNCTION__);
        $staleResource->setFresh(false);

        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::never())
            ->method('isCacheFresh');
        $dependency2->expects(self::never())
            ->method('isCacheFresh');

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$staleResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheIsFresh(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
        $freshResource->setFresh(true);

        $dependency1 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency2 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency3 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(true);
        $dependency2->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(false);
        $dependency3->expects(self::never())
            ->method('isCacheFresh');

        $dependency1->expects(self::never())
            ->method('warmUpCache');
        $dependency2->expects(self::once())
            ->method('warmUpCache');

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->addDependency($dependency3);
        $cache->write('', [$freshResource]);

        $cache->doEnsureDependenciesWarmedUp();
    }

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheIsNotFresh(): void
    {
        $freshResource = new ResourceStub(__FUNCTION__);
        $freshResource->setFresh(false);

        $dependency1 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency2 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency3 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(true);
        $dependency2->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isType('int'))
            ->willReturn(false);
        $dependency3->expects(self::never())
            ->method('isCacheFresh');

        $dependency1->expects(self::never())
            ->method('warmUpCache');
        $dependency2->expects(self::once())
            ->method('warmUpCache');

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->addDependency($dependency3);
        $cache->write('', [$freshResource]);

        $cache->doEnsureDependenciesWarmedUp();
    }

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheDoeNotExist(): void
    {
        $dependency1 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency2 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency3 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::once())
            ->method('isCacheFresh')
            ->with(PHP_INT_MAX)
            ->willReturn(true);
        $dependency2->expects(self::once())
            ->method('isCacheFresh')
            ->with(PHP_INT_MAX)
            ->willReturn(false);
        $dependency3->expects(self::never())
            ->method('isCacheFresh');

        $dependency1->expects(self::never())
            ->method('warmUpCache');
        $dependency2->expects(self::once())
            ->method('warmUpCache');

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->addDependency($dependency3);

        $cache->doEnsureDependenciesWarmedUp();
    }

    public function testEnsureDependenciesWarmedUpInProduction(): void
    {
        $dependency1 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency2 = $this->createMock(PhpArrayConfigProviderStub::class);
        $dependency3 = $this->createMock(ConfigCacheStateInterface::class);

        $dependency1->expects(self::never())
            ->method('isCacheFresh');
        $dependency2->expects(self::never())
            ->method('isCacheFresh');
        $dependency3->expects(self::never())
            ->method('isCacheFresh');

        $dependency1->expects(self::never())
            ->method('warmUpCache');
        $dependency2->expects(self::never())
            ->method('warmUpCache');

        $cache = $this->getConfigCache(false);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->addDependency($dependency3);

        $cache->doEnsureDependenciesWarmedUp();
    }
}
