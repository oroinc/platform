<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Bundle\ApiBundle\Tests\Unit\Stub\ResourceStub;
use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Testing\TempDirExtension;

class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    protected function setUp()
    {
        $this->cacheFile = $this->getTempFile('ConfigCache');
        self::assertFileNotExists($this->cacheFile);
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
        self::assertFileNotExists($cache->getPath() . '.meta');
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
            self::assertFileNotExists($cache->getPath() . '.meta');
        }
    }

    public function testIsFreshWhenNoMetadataInDebug()
    {
        $cache = new ConfigCache($this->cacheFile, false);
        $cache->write('');
        self::assertTrue($cache->isFresh());
        self::assertFileNotExists($cache->getPath() . '.meta');
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
}
