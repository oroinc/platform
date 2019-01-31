<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\ConfigCacheFactory;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Config\ConfigCacheInterface;

class ConfigCacheFactoryTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid type for callback argument. Expected callable, but got "object".
     */
    public function testCacheWithInvalidCallback()
    {
        $cacheFactory = new ConfigCacheFactory(false);
        $cacheFactory->cache('file', new \stdClass());
    }

    public function testCacheShouldReturnNewInstanceOfConfigCache()
    {
        $cacheFactory = new ConfigCacheFactory(false);

        $cacheFile = $this->getTempFile('ConfigCacheFactory');

        $cache1 = $cacheFactory->cache($cacheFile, function (ConfigCacheInterface $cache) {
        });
        self::assertInstanceOf(ConfigCache::class, $cache1);

        $cache2 = $cacheFactory->cache($cacheFile, function (ConfigCacheInterface $cache) {
        });
        self::assertInstanceOf(ConfigCache::class, $cache1);

        self::assertNotSame($cache1, $cache2);
    }

    public function testCacheShouldCallCallbackFunctionIfCacheFileDoesNotExist()
    {
        $cacheFactory = new ConfigCacheFactory(false);

        $cacheFile = $this->getTempFile('ConfigCacheFactory');
        self::assertFileNotExists($cacheFile);

        $cache = $cacheFactory->cache($cacheFile, function (ConfigCacheInterface $cache) {
            $cache->write('test');
        });
        self::assertEquals($cacheFile, $cache->getPath());
        self::assertFileExists($cacheFile);
        self::assertEquals('test', file_get_contents($cacheFile));
    }

    public function testCacheShouldNotCallCallbackFunctionIfCacheFileExists()
    {
        $cacheFactory = new ConfigCacheFactory(false);

        $cacheFile = $this->getTempFile('ConfigCacheFactory');
        file_put_contents($cacheFile, 'test');

        $cacheFactory->cache($cacheFile, function (ConfigCacheInterface $cache) {
            $cache->write('updated');
        });
        self::assertEquals('test', file_get_contents($cacheFile));
    }
}
