<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Config\Tests\Unit\Fixtures\PhpArrayConfigProviderStub;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;

class PhpArrayConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    /** @var ConfigCacheFactoryInterface */
    private $cacheFactory;

    protected function setUp()
    {
        $this->cacheFile = $this->getTempFile('PhpConfigCacheAccessor');
        $this->cacheFactory = new ConfigCacheFactory(false);
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
            $this->cacheFactory,
            function (ResourcesContainerInterface $resourcesContainer) use ($config) {
                return $config;
            }
        );
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
