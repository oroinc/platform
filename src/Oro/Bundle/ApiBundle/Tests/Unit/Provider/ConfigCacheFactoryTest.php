<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFile;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
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
        $configCacheWarmer = $this->createMock(ConfigCacheWarmer::class);
        $expectedConfig = new ConfigCacheFile(
            sprintf('%s/%s.php', $cacheDir, $configKey),
            $debug,
            $configKey,
            $configCacheWarmer
        );

        $factory = new ConfigCacheFactory($cacheDir, $debug);
        $factory->setConfigCacheWarmer($configCacheWarmer);

        self::assertEquals(
            $expectedConfig,
            $factory->getCache($configKey)
        );
    }

    public function testGetCacheWithDependencies()
    {
        $configKey = 'test';
        $cacheDir = __DIR__ . '/Fixtures';
        $configCacheWarmer = $this->createMock(ConfigCacheWarmer::class);
        $dependency1 = $this->createMock(ConfigCacheStateInterface::class);
        $dependency2 = $this->createMock(ConfigCacheStateInterface::class);
        $expectedConfig = new ConfigCacheFile(
            sprintf('%s/%s.php', $cacheDir, $configKey),
            true,
            $configKey,
            $configCacheWarmer
        );
        $expectedConfig->addDependency($dependency1);
        $expectedConfig->addDependency($dependency2);

        $factory = new ConfigCacheFactory($cacheDir, true);
        $factory->setConfigCacheWarmer($configCacheWarmer);
        $factory->addDependency($dependency1);
        $factory->addDependency($dependency2);

        self::assertEquals(
            $expectedConfig,
            $factory->getCache($configKey)
        );
    }

    public function getCacheProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
