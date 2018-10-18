<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCache;

class ConfigCacheFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCacheProvider
     */
    public function testGetCache($debug)
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
