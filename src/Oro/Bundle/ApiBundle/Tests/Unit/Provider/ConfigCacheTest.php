<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFile;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Component\Config\Tests\Unit\Fixtures\ResourceStub;
use Oro\Component\Testing\TempDirExtension;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $configKey;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheFactory */
    private $configCacheFactory;

    protected function setUp(): void
    {
        $this->configKey = 'test';
        $this->configCacheFactory = $this->createMock(ConfigCacheFactory::class);
    }

    public function getConfigCache(bool $debug = false): ConfigCache
    {
        return new ConfigCache(
            $this->configKey,
            $debug,
            $this->configCacheFactory
        );
    }

    public function testGetConfig()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedConfig = [
            'entities'  => [
                User::class => [
                    'fields' => [
                        'groups' => [
                            'exclude' => true
                        ]
                    ]
                ]
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedConfig, $configCache->getConfig($configFile));
        // test that data is cached in memory
        self::assertEquals($expectedConfig, $configCache->getConfig($configFile));
    }

    public function testGetConfigWhenCacheIsFresh()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedConfig = [
            'entities'  => [
                User::class => [
                    'fields' => [
                        'groups' => [
                            'exclude' => true
                        ]
                    ]
                ]
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedConfig, $configCache->getConfig($configFile));
    }

    public function testGetConfigWhenCacheDataIsInvalid()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getConfig($configFile);
    }

    public function testGetConfigWhenCacheDoesNotHaveConfigForGivenConfigFile()
    {
        $configFile = 'api_test1.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown config "%s".', $configFile));

        $configCache = $this->getConfigCache();

        $configCache->getConfig($configFile);
    }

    public function testGetAliases()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedAliases = [
            User::class => [
                'alias'        => 'user',
                'plural_alias' => 'users'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedAliases, $configCache->getAliases());
        // test that data is cached in memory
        self::assertEquals($expectedAliases, $configCache->getAliases());
    }

    public function testGetAliasesWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedAliases = [
            User::class => [
                'alias'        => 'user',
                'plural_alias' => 'users'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedAliases, $configCache->getAliases());
    }

    public function testGetAliasesWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getAliases();
    }

    public function testGetExcludedEntities()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExcludedEntities = [
            User::class
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedExcludedEntities, $configCache->getExcludedEntities());
        // test that data is cached in memory
        self::assertEquals($expectedExcludedEntities, $configCache->getExcludedEntities());
    }

    public function testGetExcludedEntitiesWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExcludedEntities = [
            User::class
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedExcludedEntities, $configCache->getExcludedEntities());
    }

    public function testGetExcludedEntitiesWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getExcludedEntities();
    }

    public function testGetSubstitutions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedSubstitutions = [
            User::class =>
                UserProfile::class
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedSubstitutions, $configCache->getSubstitutions());
        // test that data is cached in memory
        self::assertEquals($expectedSubstitutions, $configCache->getSubstitutions());
    }

    public function testGetSubstitutionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedSubstitutions = [
            User::class =>
                UserProfile::class
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedSubstitutions, $configCache->getSubstitutions());
    }

    public function testGetSubstitutionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getSubstitutions();
    }

    public function testGetExclusions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExclusions = [
            [
                'entity' => User::class,
                'field'  => 'name'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedExclusions, $configCache->getExclusions());
        // test that data is cached in memory
        self::assertEquals($expectedExclusions, $configCache->getExclusions());
    }

    public function testGetExclusionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExclusions = [
            [
                'entity' => User::class,
                'field'  => 'name'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedExclusions, $configCache->getExclusions());
    }

    public function testGetExclusionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getExclusions();
    }

    public function testGetInclusions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedInclusions = [
            [
                'entity' => User::class,
                'field'  => 'category'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::once())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedInclusions, $configCache->getInclusions());
        // test that data is cached in memory
        self::assertEquals($expectedInclusions, $configCache->getInclusions());
    }

    public function testGetInclusionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedInclusions = [
            [
                'entity' => User::class,
                'field'  => 'category'
            ]
        ];

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $configCache = $this->getConfigCache();

        self::assertEquals($expectedInclusions, $configCache->getInclusions());
    }

    public function testGetInclusionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);
        $cache->expects(self::never())
            ->method('warmUpCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $configCache = $this->getConfigCache();

        $configCache->getInclusions();
    }

    public function testIsCacheFreshForNullTimestamp()
    {
        $this->configCacheFactory->expects(self::never())
            ->method('getCache');

        $configCache = $this->getConfigCache();

        self::assertTrue($configCache->isCacheFresh(null));
    }

    public function testIsCacheFreshWhenNoCachedData()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, false, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        $timestamp = time() - 1;
        self::assertFalse($configCache->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshWhenCachedDataExist()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        file_put_contents($cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, false, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        $cacheTimestamp = filemtime($cacheFile);
        self::assertTrue($configCache->isCacheFresh($cacheTimestamp));
        self::assertTrue($configCache->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($configCache->isCacheFresh($cacheTimestamp - 1));
    }

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsFresh()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        file_put_contents($cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));
        $resource = new ResourceStub(__FUNCTION__);
        file_put_contents($cacheFile . '.meta', serialize([$resource]));

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, true, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        $cacheTimestamp = filemtime($cacheFile);
        self::assertTrue($configCache->isCacheFresh($cacheTimestamp));
        self::assertTrue($configCache->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($configCache->isCacheFresh($cacheTimestamp - 1));
    }

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsDirty()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        file_put_contents($cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));
        $resource = new ResourceStub(__FUNCTION__);
        $resource->setFresh(false);
        file_put_contents($cacheFile . '.meta', serialize([$resource]));

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, true, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        $cacheTimestamp = filemtime($cacheFile);
        self::assertFalse($configCache->isCacheFresh($cacheTimestamp));
        self::assertFalse($configCache->isCacheFresh($cacheTimestamp + 1));
        self::assertFalse($configCache->isCacheFresh($cacheTimestamp - 1));
    }

    public function testGetCacheTimestampWhenNoCachedData()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, false, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        self::assertNull($configCache->getCacheTimestamp());
    }

    public function testGetCacheTimestampWhenCachedDataExist()
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        file_put_contents($cacheFile, sprintf('<?php return %s;', var_export(['test'], true)));

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, false, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        self::assertIsInt($configCache->getCacheTimestamp());
        self::assertSame(filemtime($cacheFile), $configCache->getCacheTimestamp());
    }
}
