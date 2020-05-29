<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;

class ConfigCacheFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCacheProvider
     */
    public function testGetCache(bool $debug)
    {
        $configKey = 'test';
        $cacheDir = __DIR__ . '/Fixtures';
        $expectedConfig = new ConfigCache(
            sprintf('%s/%s.php', $cacheDir, $configKey),
            $debug
        );

        $factory = new ConfigCacheFactory($cacheDir, $debug);

        self::assertEquals(
            $expectedConfig,
            $factory->getCache($configKey)
        );
    }

    public function testGetCacheWithDependencies()
    {
        $configKey = 'test';
        $cacheDir = __DIR__ . '/Fixtures';
        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);
        $expectedConfig = new ConfigCache(
            sprintf('%s/%s.php', $cacheDir, $configKey),
            true
        );
        $expectedConfig->addDependency($dependency1);
        $expectedConfig->addDependency($dependency2);

        $factory = new ConfigCacheFactory($cacheDir, true);
        $factory->addDependency($dependency1);
        $factory->addDependency($dependency2);

        self::assertEquals(
            $expectedConfig,
            $factory->getCache($configKey)
        );
    }

    /**
     * @return array
     */
    public function getCacheProvider()
    {
        return [
            [false],
            [true]
        ];
    }
}
