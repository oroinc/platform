<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TemplateRendererConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_CACHE_KEY = 'test_config_cache_key';

    /** @var VariablesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $variablesProvider;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var TemplateRendererConfigProvider */
    private $configProvider;

    protected function setUp(): void
    {
        $this->variablesProvider = $this->createMock(VariablesProvider::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->configProvider = new TemplateRendererConfigProvider(
            $this->variablesProvider,
            $this->cache,
            self::CONFIG_CACHE_KEY
        );
    }

    public function testGetConfigurationWhenCachedConfigExists()
    {
        $cachedConfig = [
            'properties'         => ['Test\Entity' => ['field2']],
            'methods'            => ['Test\Entity' => ['getField1']],
            'accessors'          => ['Test\Entity' => ['field1' => 'getField1', 'field2' => null]],
            'default_formatters' => ['Test\Entity' => ['field1' => 'formatter1']]
        ];

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::CONFIG_CACHE_KEY)
            ->willReturn($cachedConfig);

        self::assertSame($cachedConfig, $this->configProvider->getConfiguration());
        // test local cache
        self::assertSame($cachedConfig, $this->configProvider->getConfiguration());
    }

    public function testGetConfigurationWhenCachedConfigDoesNotExist()
    {
        $entityVariableGetters = [
            'Test\Entity' => [
                'field1' => 'getField1',
                'field2' => null,
                'field3' => [
                    'property_path'     => 'getField3',
                    'default_formatter' => 'formatter3'
                ],
                'field4' => ['property_path' => null],
                'field5' => [
                    'property_path'     => 'getField5',
                    'related_entity_name' => 'Test\Entity2'
                ],
            ]
        ];
        $config = [
            'default_formatters' => ['Test\Entity' => ['field3' => 'formatter3']],
            'methods'            => ['Test\Entity2' => [], 'Test\Entity' => ['getField1', 'getField3', 'getField5']],
            'properties'         => ['Test\Entity' => ['field2', 'field4']],
            'accessors'          => [
                'Test\Entity' => [
                    'field1' => 'getField1',
                    'field2' => null,
                    'field3' => 'getField3',
                    'field4' => null,
                    'field5' => 'getField5'
                ]
            ]
        ];

        $this->variablesProvider->expects(self::once())
            ->method('getEntityVariableGetters')
            ->willReturn($entityVariableGetters);

        $this->cache->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_CACHE_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        self::assertSame($config, $this->configProvider->getConfiguration());
        // test local cache
        self::assertSame($config, $this->configProvider->getConfiguration());
    }

    public function testGetSystemVariableValues()
    {
        $systemVariableValues = ['variable1' => 'value1'];

        $this->variablesProvider->expects(self::once())
            ->method('getSystemVariableValues')
            ->willReturn($systemVariableValues);

        self::assertSame($systemVariableValues, $this->configProvider->getSystemVariableValues());
        // test local cache
        self::assertSame($systemVariableValues, $this->configProvider->getSystemVariableValues());
    }

    public function testGetEntityVariableProcessors()
    {
        $entityClass1 = 'Test\Entity1';
        $entityClass2 = 'Test\Entity2';
        $entityClass3 = 'Test\Entity3';
        $definitions = [
            $entityClass1 => ['prop1' => ['processor' => 'processor1']],
            $entityClass2 => ['prop2' => ['processor' => 'processor2', 'param2' => 'val2']],
            $entityClass3 => []
        ];

        $this->variablesProvider->expects(self::exactly(3))
            ->method('getEntityVariableProcessors')
            ->willReturnMap([
                [$entityClass1, $definitions[$entityClass1]],
                [$entityClass2, $definitions[$entityClass2]],
                [$entityClass3, $definitions[$entityClass3]]
            ]);

        foreach ($definitions as $entityClass => $definition) {
            self::assertSame(
                $definition,
                $this->configProvider->getEntityVariableProcessors($entityClass),
                $entityClass
            );
        }
        // test local cache
        foreach ($definitions as $entityClass => $definition) {
            self::assertSame(
                $definition,
                $this->configProvider->getEntityVariableProcessors($entityClass),
                $entityClass
            );
        }
    }

    public function testClearCache()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::CONFIG_CACHE_KEY);
        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with(self::CONFIG_CACHE_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->variablesProvider->expects(self::exactly(2))
            ->method('getSystemVariableValues')
            ->willReturn([]);
        $this->variablesProvider->expects(self::exactly(2))
            ->method('getEntityVariableProcessors')
            ->with('Test\Entity')
            ->willReturn([]);

        // warmup local cache
        $this->configProvider->getConfiguration();
        $this->configProvider->getSystemVariableValues();
        $this->configProvider->getEntityVariableProcessors('Test\Entity');

        $this->configProvider->clearCache();

        // test that local cache was cleared
        $this->configProvider->getConfiguration();
        $this->configProvider->getSystemVariableValues();
        $this->configProvider->getEntityVariableProcessors('Test\Entity');
    }
}
