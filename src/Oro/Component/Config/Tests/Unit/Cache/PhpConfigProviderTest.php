<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Config\Tests\Unit\Fixtures\PhpArrayConfigProviderStub;
use Oro\Component\Config\Tests\Unit\Fixtures\ResourceStub;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PhpConfigProviderTest extends TestCase
{
    use TempDirExtension;

    private string $cacheFile;

    #[\Override]
    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('PhpConfigProvider');
        self::assertFileDoesNotExist($this->cacheFile);
    }

    /**
     * @param mixed $config
     * @param bool  $debug
     *
     * @return PhpArrayConfigProviderStub
     */
    private function getProvider($config, $debug = false): PhpArrayConfigProviderStub
    {
        return new PhpArrayConfigProviderStub(
            $this->cacheFile,
            $debug,
            function (ResourcesContainerInterface $resourcesContainer) use ($config) {
                return $config;
            }
        );
    }

    public function testIsCacheFreshForNullTimestamp(): void
    {
        $provider = $this->getProvider(['test']);

        self::assertTrue($provider->isCacheFresh(null));
    }

    public function testIsCacheFreshWhenNoCachedData(): void
    {
        $provider = $this->getProvider(['test']);

        $timestamp = time() - 1;
        self::assertFalse($provider->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshWhenCachedDataExist(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));

        $provider = $this->getProvider(['initial']);

        $cacheTimestamp = filemtime($this->cacheFile);
        self::assertTrue($provider->isCacheFresh($cacheTimestamp));
        self::assertTrue($provider->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($provider->isCacheFresh($cacheTimestamp - 1));
    }

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsFresh(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));
        $resource = new ResourceStub(__FUNCTION__);
        file_put_contents($this->cacheFile . '.meta', serialize([$resource]));

        $provider = $this->getProvider(['initial'], true);

        $cacheTimestamp = filemtime($this->cacheFile);
        self::assertTrue($provider->isCacheFresh($cacheTimestamp));
        self::assertTrue($provider->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($provider->isCacheFresh($cacheTimestamp - 1));
    }

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsDirty(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));
        $resource = new ResourceStub(__FUNCTION__);
        $resource->setFresh(false);
        file_put_contents($this->cacheFile . '.meta', serialize([$resource]));

        $provider = $this->getProvider(['initial'], true);

        $cacheTimestamp = filemtime($this->cacheFile);
        self::assertFalse($provider->isCacheFresh($cacheTimestamp));
        self::assertFalse($provider->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($provider->isCacheFresh($cacheTimestamp - 1));
    }

    public function testGetCacheTimestampWhenNoCachedData(): void
    {
        $provider = $this->getProvider(['initial']);

        self::assertNull($provider->getCacheTimestamp());
    }

    public function testGetCacheTimestampWhenCachedDataExist(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));

        $provider = $this->getProvider(['initial']);

        self::assertEquals(filemtime($this->cacheFile), $provider->getCacheTimestamp());
    }

    public function testGetConfigWhenNoCachedData(): void
    {
        $config = ['test'];

        $provider = $this->getProvider($config);

        self::assertEquals($config, $provider->getConfig());
    }

    public function testGetConfigWhenCachedDataExist(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        self::assertEquals($cachedConfig, $provider->getConfig());
    }

    public function testClearCache(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->clearCache();
        self::assertFileDoesNotExist($this->cacheFile);
        self::assertNull($provider->getCacheTimestamp());

        // test that the cache is built after it was cleared
        self::assertEquals($initialConfig, $provider->getConfig());
        self::assertIsInt($provider->getCacheTimestamp());
        self::assertSame(filemtime($this->cacheFile), $provider->getCacheTimestamp());
    }

    public function testWarmUpCache(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->warmUpCache();
        self::assertEquals($initialConfig, $provider->getConfig());
    }

    public function testEnsureCacheWarmedUpWhenCachedDataExist(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->ensureCacheWarmedUp();
        self::assertEquals($cachedConfig, $provider->getConfig());
    }

    public function testEnsureCacheWarmedUpWhenCachedDataExistForDevelopmentModeWhenCacheIsFresh(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));
        $resource = new ResourceStub(__FUNCTION__);
        file_put_contents($this->cacheFile . '.meta', serialize([$resource]));

        $provider = $this->getProvider($initialConfig, true);

        $provider->ensureCacheWarmedUp();
        self::assertEquals($cachedConfig, $provider->getConfig());
    }

    public function testEnsureCacheWarmedUpWhenCachedDataExistForDevelopmentModeWhenCacheIsDirty(): void
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));
        $resource = new ResourceStub(__FUNCTION__);
        $resource->setFresh(false);
        file_put_contents($this->cacheFile . '.meta', serialize([$resource]));

        $provider = $this->getProvider($initialConfig, true);

        $provider->ensureCacheWarmedUp();
        self::assertEquals($initialConfig, $provider->getConfig());
    }

    public function testInvalidInitialConfig(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Expected an array.',
            $this->cacheFile
        ));

        $provider = $this->getProvider('invalid');
        $provider->getConfig();
    }

    public function testGetCacheResource(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));

        $provider = $this->getProvider([]);
        /** @var FileResource $cacheResource */
        $cacheResource = $provider->getCacheResource();
        self::assertInstanceOf(FileResource::class, $cacheResource);
        self::assertEquals($this->cacheFile, $cacheResource->getResource());
    }
}
