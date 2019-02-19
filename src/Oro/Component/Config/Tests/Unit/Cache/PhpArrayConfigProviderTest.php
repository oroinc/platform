<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Config\Tests\Unit\Fixtures\PhpArrayConfigProviderStub;
use Oro\Component\Testing\TempDirExtension;

class PhpArrayConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    protected function setUp()
    {
        $this->cacheFile = $this->getTempFile('PhpConfigCacheAccessor');
        self::assertFileNotExists($this->cacheFile);
    }

    /**
     * @param mixed $config
     *
     * @return PhpArrayConfigProviderStub
     */
    private function getProvider($config): PhpArrayConfigProviderStub
    {
        return new PhpArrayConfigProviderStub(
            $this->cacheFile,
            false,
            function (ResourcesContainerInterface $resourcesContainer) use ($config) {
                return $config;
            }
        );
    }

    public function testIsCacheFreshWhenNoCachedData()
    {
        $config = ['test'];

        $provider = $this->getProvider($config);

        $timestamp = time() - 1;
        self::assertFalse($provider->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshWhenCachedDataExist()
    {
        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export(['test'], true)));

        $provider = $this->getProvider(['initial']);

        $cacheTimestamp = filemtime($this->cacheFile);
        self::assertTrue($provider->isCacheFresh($cacheTimestamp));
        self::assertTrue($provider->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($provider->isCacheFresh($cacheTimestamp - 1));
    }

    public function testGetConfigWhenNoCachedData()
    {
        $config = ['test'];

        $provider = $this->getProvider($config);

        self::assertEquals($config, $provider->getConfig());
    }

    public function testGetConfigWhenCachedDataExist()
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        self::assertEquals($cachedConfig, $provider->getConfig());
    }

    public function testClearCache()
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->clearCache();
        self::assertAttributeSame(null, 'config', $provider);
        self::assertFileNotExists($this->cacheFile);

        // test that the cache is built after it was cleared
        self::assertEquals($initialConfig, $provider->getConfig());
    }

    public function testWarmUpCache()
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->warmUpCache();
        self::assertEquals($initialConfig, $provider->getConfig());
    }

    public function testEnsureCacheWarmedUpWhenCachedDataExist()
    {
        $cachedConfig = ['test'];
        $initialConfig = ['initial'];

        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedConfig, true)));

        $provider = $this->getProvider($initialConfig);

        $provider->ensureCacheWarmedUp();
        self::assertEquals($cachedConfig, $provider->getConfig());
    }

    public function testInvalidInitialConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Expected an array.',
            $this->cacheFile
        ));

        $provider = $this->getProvider('invalid');
        $provider->getConfig();
    }
}
