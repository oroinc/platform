<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
use Symfony\Component\Config\ConfigCacheInterface;

class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $configKey;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheFactory */
    private $configCacheFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheWarmer */
    private $configCacheWarmer;

    /** @var ConfigCache */
    private $configCache;

    protected function setUp()
    {
        $this->configKey = 'test';
        $this->configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $this->configCacheWarmer = $this->createMock(ConfigCacheWarmer::class);

        $this->configCache = new ConfigCache(
            $this->configKey,
            $this->configCacheFactory,
            $this->configCacheWarmer
        );
    }

    public function testGetConfig()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedConfig = [
            'entities'  => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' => [
                    'fields' => [
                        'groups' => [
                            'exclude' => true
                        ]
                    ]
                ]
            ],
            'relations' => []
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedConfig, $this->configCache->getConfig($configFile));
        // test that data is cached in memory
        self::assertEquals($expectedConfig, $this->configCache->getConfig($configFile));
    }

    public function testGetConfigWhenCacheIsFresh()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedConfig = [
            'entities'  => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' => [
                    'fields' => [
                        'groups' => [
                            'exclude' => true
                        ]
                    ]
                ]
            ],
            'relations' => []
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedConfig, $this->configCache->getConfig($configFile));
    }

    public function testGetConfigWhenCacheDataIsInvalid()
    {
        $configFile = 'api_test.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getConfig($configFile);
    }

    public function testGetConfigWhenCacheDoesNotHaveConfigForGivenConfigFile()
    {
        $configFile = 'api_test1.yml';
        $cachePath = __DIR__ . '/Fixtures/api_test.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown config "%s".', $configFile));

        $this->configCache->getConfig($configFile);
    }

    public function testGetAliases()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedAliases = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' => [
                'alias'        => 'user',
                'plural_alias' => 'users'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedAliases, $this->configCache->getAliases());
        // test that data is cached in memory
        self::assertEquals($expectedAliases, $this->configCache->getAliases());
    }

    public function testGetAliasesWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedAliases = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' => [
                'alias'        => 'user',
                'plural_alias' => 'users'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedAliases, $this->configCache->getAliases());
    }

    public function testGetAliasesWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getAliases();
    }

    public function testGetExcludedEntities()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExcludedEntities = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User'
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedExcludedEntities, $this->configCache->getExcludedEntities());
        // test that data is cached in memory
        self::assertEquals($expectedExcludedEntities, $this->configCache->getExcludedEntities());
    }

    public function testGetExcludedEntitiesWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExcludedEntities = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User'
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedExcludedEntities, $this->configCache->getExcludedEntities());
    }

    public function testGetExcludedEntitiesWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getExcludedEntities();
    }

    public function testGetSubstitutions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedSubstitutions = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' =>
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile'
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedSubstitutions, $this->configCache->getSubstitutions());
        // test that data is cached in memory
        self::assertEquals($expectedSubstitutions, $this->configCache->getSubstitutions());
    }

    public function testGetSubstitutionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedSubstitutions = [
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User' =>
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile'
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedSubstitutions, $this->configCache->getSubstitutions());
    }

    public function testGetSubstitutionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getSubstitutions();
    }

    public function testGetExclusions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExclusions = [
            [
                'entity' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'field'  => 'name'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedExclusions, $this->configCache->getExclusions());
        // test that data is cached in memory
        self::assertEquals($expectedExclusions, $this->configCache->getExclusions());
    }

    public function testGetExclusionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedExclusions = [
            [
                'entity' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'field'  => 'name'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedExclusions, $this->configCache->getExclusions());
    }

    public function testGetExclusionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getExclusions();
    }

    public function testGetInclusions()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedInclusions = [
            [
                'entity' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'field'  => 'category'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($this->configKey);

        self::assertEquals($expectedInclusions, $this->configCache->getInclusions());
        // test that data is cached in memory
        self::assertEquals($expectedInclusions, $this->configCache->getInclusions());
    }

    public function testGetInclusionsWhenCacheIsFresh()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test.php';
        $expectedInclusions = [
            [
                'entity' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'field'  => 'category'
            ]
        ];

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        self::assertEquals($expectedInclusions, $this->configCache->getInclusions());
    }

    public function testGetInclusionsWhenCacheDataIsInvalid()
    {
        $cachePath = __DIR__ . '/Fixtures/api_test_invalid.php';

        $cache = $this->createMock(ConfigCacheInterface::class);
        $cache->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn($cachePath);

        $this->configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with($this->configKey)
            ->willReturn($cache);
        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s" must return an array.', $cachePath));

        $this->configCache->getInclusions();
    }
}
