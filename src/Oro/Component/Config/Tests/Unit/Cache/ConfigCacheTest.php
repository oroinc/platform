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

    /** @var string */
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('ConfigCache');
        self::assertFileDoesNotExist($this->cacheFile);
    }

    private function getConfigCache(bool $debug): ConfigCacheStub
    {
        return new ConfigCacheStub($this->cacheFile, $debug);
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
        $cache = $this->getConfigCache($debug);

        self::assertFalse($cache->isFresh());
    }

    public function testIsAlwaysFreshInProduction()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = $this->getConfigCache(false);
        $cache->write('', [$staleResource]);

        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    /**
     * @dataProvider debugModeProvider
     */
    public function testIsFreshWhenMetadataIsEmptyArray($debug)
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

    public function testIsFreshWhenNoMetadataInDebug()
    {
        $cache = $this->getConfigCache(false);
        $cache->write('');
        self::assertTrue($cache->isFresh());
        self::assertFileDoesNotExist($cache->getPath() . '.meta');
    }

    public function testFreshResourceInDebug()
    {
        $freshResource = new ResourceStub();
        $freshResource->setFresh(true);

        $cache = $this->getConfigCache(true);
        $cache->write('', [$freshResource]);

        self::assertTrue($cache->isFresh());
    }

    public function testStaleResourceInDebug()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = $this->getConfigCache(true);
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

        $cache = $this->getConfigCache(true);
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

        $cache = $this->getConfigCache(true);
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

        $cache = $this->getConfigCache(true);
        $cache->addDependency($dependency1);
        $cache->addDependency($dependency2);
        $cache->write('', [$staleResource]);

        self::assertFalse($cache->isFresh());
    }

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheIsFresh()
    {
        $freshResource = new ResourceStub();
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

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheIsNotFresh()
    {
        $freshResource = new ResourceStub();
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

    public function testEnsureDependenciesWarmedUpInDebugAndMainCacheDoeNotExist()
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

    public function testEnsureDependenciesWarmedUpInProduction()
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
