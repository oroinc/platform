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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigCacheTest extends TestCase
{
    use TempDirExtension;

    private string $configKey;
    private ConfigCacheFactory&MockObject $configCacheFactory;

    #[\Override]
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

    public function testGetConfig(): void
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

    public function testGetConfigWhenCacheIsFresh(): void
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

    public function testGetConfigWhenCacheDataIsInvalid(): void
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

    public function testGetConfigWhenCacheDoesNotHaveConfigForGivenConfigFile(): void
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

    public function testGetAliases(): void
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

    public function testGetAliasesWhenCacheIsFresh(): void
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

    public function testGetAliasesWhenCacheDataIsInvalid(): void
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

    public function testGetExcludedEntities(): void
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

    public function testGetExcludedEntitiesWhenCacheIsFresh(): void
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

    public function testGetExcludedEntitiesWhenCacheDataIsInvalid(): void
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

    public function testGetSubstitutions(): void
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

    public function testGetSubstitutionsWhenCacheIsFresh(): void
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

    public function testGetSubstitutionsWhenCacheDataIsInvalid(): void
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

    public function testGetExclusions(): void
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

    public function testGetExclusionsWhenCacheIsFresh(): void
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

    public function testGetExclusionsWhenCacheDataIsInvalid(): void
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

    public function testGetInclusions(): void
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

    public function testGetInclusionsWhenCacheIsFresh(): void
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

    public function testGetInclusionsWhenCacheDataIsInvalid(): void
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

    public function testIsCacheFreshForNullTimestamp(): void
    {
        $this->configCacheFactory->expects(self::never())
            ->method('getCache');

        $configCache = $this->getConfigCache();

        self::assertTrue($configCache->isCacheFresh(null));
    }

    public function testIsCacheFreshWhenNoCachedData(): void
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

    public function testIsCacheFreshWhenCachedDataExist(): void
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

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsFresh(): void
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

    public function testIsCacheFreshWhenCachedDataExistForDevelopmentModeWhenCacheIsDirty(): void
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

    public function testGetCacheTimestampWhenNoCachedData(): void
    {
        $cacheFile = $this->getTempFile('ApiConfigCache');

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn(new ConfigCacheFile($cacheFile, false, 'key', $this->createMock(ConfigCacheWarmer::class)));

        $configCache = $this->getConfigCache();

        self::assertNull($configCache->getCacheTimestamp());
    }

    public function testGetCacheTimestampWhenCachedDataExist(): void
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
