<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASSES_KEY = '_entities';
    private const FIELD_NAMES_KEY    = '_fields_';

    private const SCOPE        = 'testScope';
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME   = 'testField';
    private const FIELD_TYPE   = 'integer';

    private const ANOTHER_SCOPE        = 'anotherScope';
    private const ANOTHER_ENTITY_CLASS = 'Test\AnotherEntity';
    private const ANOTHER_FIELD_NAME   = 'anotherField';
    private const ANOTHER_FIELD_TYPE   = 'boolean';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $modelCache;

    /** @var ConfigCache */
    private $configCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->modelCache = $this->createMock(CacheProvider::class);

        $this->configCache = new ConfigCache(
            $this->cache,
            $this->modelCache,
            [self::SCOPE => self::SCOPE, self::ANOTHER_SCOPE => self::ANOTHER_SCOPE]
        );
    }

    public function testSaveEntities()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::ENTITY_CLASSES_KEY, $entities);
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveEntities($entities);

        $this->assertEquals(
            $entities,
            $this->configCache->getEntities()
        );
    }

    public function testSaveFields()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS, $fields);
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveFields(self::ENTITY_CLASS, $fields);

        $this->assertEquals(
            $fields,
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testSaveEntitiesLocalOnly()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveEntities($entities, true);

        $this->assertEquals(
            $entities,
            $this->configCache->getEntities()
        );
    }

    public function testSaveFieldsLocalOnly()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveFields(self::ENTITY_CLASS, $fields, true);

        $this->assertEquals(
            $fields,
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testGetEntities()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASSES_KEY)
            ->willReturn($entities);

        $this->assertEquals(
            $entities,
            $this->configCache->getEntities()
        );
        // test local cache
        $this->assertEquals(
            $entities,
            $this->configCache->getEntities()
        );
    }

    public function testGetFields()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS)
            ->willReturn($fields);

        $this->assertEquals(
            $fields,
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
        // test local cache
        $this->assertEquals(
            $fields,
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testGetEntitiesLocalOnly()
    {
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->assertNull(
            $this->configCache->getEntities(true)
        );
    }

    public function testGetFieldsLocalOnly()
    {
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS, true)
        );
    }

    public function testGetEntitiesNotCached()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASSES_KEY)
            ->willReturn(false);

        $this->assertNull(
            $this->configCache->getEntities()
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getEntities()
        );
    }

    public function testGetFieldsNotCached()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testDeleteEntities()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::ENTITY_CLASSES_KEY);

        $this->configCache->saveEntities($entities);

        $this->configCache->deleteEntities();
        $this->assertNull(
            $this->configCache->getEntities()
        );
    }

    public function testDeleteFields()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);

        $this->configCache->saveFields(self::ENTITY_CLASS, $fields);

        $this->configCache->deleteFields(self::ENTITY_CLASS);
        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testDeleteEntitiesLocalOnly()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->never())
            ->method('delete');

        $this->configCache->saveEntities($entities);

        $this->configCache->deleteEntities(true);
        $this->assertNull(
            $this->configCache->getEntities()
        );
    }

    public function testDeleteFieldsLocalOnly()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->never())
            ->method('delete');

        $this->configCache->saveFields(self::ENTITY_CLASS, $fields);

        $this->configCache->deleteFields(self::ENTITY_CLASS, true);
        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testSaveEntityConfig()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config = new Config($configId, ['key1' => 'val1']);
        $cacheKey = self::SCOPE;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($cacheKey, [$configId->getClassName() => $config->getValues()]);

        $this->configCache->saveConfig($config);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
    }

    public function testSaveFieldConfig()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $config = new Config($configId, ['key1' => 'val1']);
        $cacheKey = self::ENTITY_CLASS . '.' . self::SCOPE;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $cacheKey,
                [
                    $configId->getFieldName() => [
                        $config->getValues(),
                        $configId->getFieldType()
                    ]
                ]
            );

        $this->configCache->saveConfig($config);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
    }

    public function testSaveEntityConfigLocalOnly()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config = new Config($configId);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SCOPE)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('save');

        $this->configCache->saveConfig($config, true);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
    }

    public function testSaveFieldConfigLocalOnly()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $config = new Config($configId);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('save');

        $this->configCache->saveConfig($config, true);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
    }

    public function testSaveEntityConfigWhenAnotherEntityConfigIsAlreadyCached()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config = new Config($configId, ['key1' => 'val1']);
        $anotherConfigId = new EntityConfigId(self::SCOPE, self::ANOTHER_ENTITY_CLASS);
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);
        $cacheKey = self::SCOPE;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn([$anotherConfigId->getClassName() => $anotherConfig->getValues()]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $cacheKey,
                [
                    $configId->getClassName()        => $config->getValues(),
                    $anotherConfigId->getClassName() => $anotherConfig->getValues()
                ]
            );

        $this->configCache->saveConfig($config);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
        $this->assertEquals(
            $anotherConfig,
            $this->configCache->getEntityConfig(
                $anotherConfigId->getScope(),
                $anotherConfigId->getClassName()
            )
        );
    }

    public function testSaveFieldConfigWhenAnotherFieldConfigIsAlreadyCached()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $config = new Config($configId, ['key1' => 'val1']);
        $anotherConfigId = new FieldConfigId(
            self::SCOPE,
            self::ENTITY_CLASS,
            self::ANOTHER_FIELD_NAME,
            self::ANOTHER_FIELD_TYPE
        );
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);
        $cacheKey = self::ENTITY_CLASS . '.' . self::SCOPE;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn([
                $anotherConfigId->getFieldName() => [
                    $anotherConfig->getValues(),
                    $anotherConfigId->getFieldType()
                ]
            ]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $cacheKey,
                [
                    $configId->getFieldName()        => [
                        $config->getValues(),
                        $configId->getFieldType()
                    ],
                    $anotherConfigId->getFieldName() => [
                        $anotherConfig->getValues(),
                        $anotherConfigId->getFieldType()
                    ]
                ]
            );

        $this->configCache->saveConfig($config);
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
        $this->assertEquals(
            $anotherConfig,
            $this->configCache->getFieldConfig(
                $anotherConfigId->getScope(),
                $anotherConfigId->getClassName(),
                $anotherConfigId->getFieldName()
            )
        );
    }

    public function testSaveEntityConfigValues()
    {
        $config1 = new Config(
            new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
            ['key1' => 'val1']
        );
        $config2 = new Config(
            new EntityConfigId(self::SCOPE, 'Test\AnotherEntity'),
            ['key2' => 'val2']
        );
        $values = [
            $config1->getId()->getClassName() => $config1->getValues(),
            $config2->getId()->getClassName() => $config2->getValues()
        ];

        $this->cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($key, $data) use ($values) {
                $this->assertEquals(self::SCOPE, $key);
                $this->assertEquals($values, $data);

                return true;
            });
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveEntityConfigValues($values, self::SCOPE);

        // test that configs saved right
        $this->assertEquals(
            $config1,
            $this->configCache->getEntityConfig(
                self::SCOPE,
                $config1->getId()->getClassName()
            )
        );
        $this->assertEquals(
            $config2,
            $this->configCache->getEntityConfig(
                self::SCOPE,
                $config2->getId()->getClassName()
            )
        );
    }

    public function testSaveFieldConfigValues()
    {
        $config1Id = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $config1 = new Config($config1Id, ['key1' => 'val1']);
        $config2Id = new FieldConfigId(
            self::SCOPE,
            self::ENTITY_CLASS,
            self::ANOTHER_FIELD_NAME,
            self::ANOTHER_FIELD_TYPE
        );
        $config2 = new Config($config2Id, ['key2' => 'val2']);
        $values = [
            $config1Id->getFieldName() => [
                $config1->getValues(),
                $config1Id->getFieldType()
            ],
            $config2Id->getFieldName() => [
                $config2->getValues(),
                $config2Id->getFieldType()
            ]
        ];

        $this->cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($key, $data) use ($values) {
                $this->assertEquals(self::ENTITY_CLASS . '.' . self::SCOPE, $key);
                $this->assertEquals($values, $data);

                return true;
            });
        $this->cache->expects($this->never())
            ->method('fetch');

        $this->configCache->saveFieldConfigValues($values, self::SCOPE, self::ENTITY_CLASS);

        // test that configs saved right
        $this->assertEquals(
            $config1,
            $this->configCache->getFieldConfig(
                self::SCOPE,
                $config1Id->getClassName(),
                $config1Id->getFieldName()
            )
        );
        $this->assertEquals(
            $config2,
            $this->configCache->getFieldConfig(
                self::SCOPE,
                $config2Id->getClassName(),
                $config2Id->getFieldName()
            )
        );
    }

    public function testGetEntityConfig()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config = new Config($configId, ['key1' => 'val1']);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SCOPE)
            ->willReturn([$configId->getClassName() => $config->getValues()]);

        $this->assertEquals(
            $config,
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
    }

    public function testGetFieldConfig()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME);
        $config = new Config(
            new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE),
            ['key1' => 'val1']
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE)
            ->willReturn([
                $configId->getFieldName() => [
                    $config->getValues(),
                    self::FIELD_TYPE
                ]
            ]);

        $this->assertEquals(
            $config,
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
        // test local cache
        $this->assertEquals(
            $config,
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
    }

    public function testGetEntityConfigNotCachedScope()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SCOPE)
            ->willReturn(false);

        $this->assertNull(
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
    }

    public function testGetFieldConfigNotCached()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE)
            ->willReturn(false);

        $this->assertNull(
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
    }

    public function testGetEntityConfigWhenRequestedEntityDoesNotExistInCache()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $anotherConfigId = new EntityConfigId(self::SCOPE, self::ANOTHER_ENTITY_CLASS);
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SCOPE)
            ->willReturn([$anotherConfigId->getClassName() => $anotherConfig->getValues()]);

        $this->assertNull(
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getEntityConfig(
                $configId->getScope(),
                $configId->getClassName()
            )
        );
        $this->assertEquals(
            $anotherConfig,
            $this->configCache->getEntityConfig(
                $anotherConfigId->getScope(),
                $anotherConfigId->getClassName()
            )
        );
    }

    public function testGetFieldConfigWhenRequestedFieldDoesNotExistInCache()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $anotherConfigId = new FieldConfigId(
            self::SCOPE,
            self::ENTITY_CLASS,
            self::ANOTHER_FIELD_NAME,
            self::ANOTHER_FIELD_TYPE
        );
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE)
            ->willReturn([
                $anotherConfigId->getFieldName() => [
                    $anotherConfig->getValues(),
                    $anotherConfigId->getFieldType()
                ]
            ]);

        $this->assertNull(
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
        // test local cache
        $this->assertNull(
            $this->configCache->getFieldConfig(
                $configId->getScope(),
                $configId->getClassName(),
                $configId->getFieldName()
            )
        );
        $this->assertEquals(
            $anotherConfig,
            $this->configCache->getFieldConfig(
                $anotherConfigId->getScope(),
                $anotherConfigId->getClassName(),
                $anotherConfigId->getFieldName()
            )
        );
    }

    public function testDeleteEntityConfigWhenLocalCacheEmpty()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);

        $this->cache->expects($this->exactly(4))
            ->method('delete');
        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::ENTITY_CLASSES_KEY);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with(self::SCOPE);
        $this->cache->expects($this->at(3))
            ->method('delete')
            ->with(self::ANOTHER_SCOPE);

        $this->configCache->deleteEntityConfig($configId->getClassName());
    }

    public function testDeleteEntityConfigWhenConfigForLastEntityDeleted()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);

        $this->configCache->saveConfig(new Config($configId), true);

        $this->cache->expects($this->exactly(4))
            ->method('delete');
        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::ENTITY_CLASSES_KEY);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with(self::SCOPE);
        $this->cache->expects($this->at(3))
            ->method('delete')
            ->with(self::ANOTHER_SCOPE);

        $this->configCache->deleteEntityConfig($configId->getClassName());
    }

    public function testDeleteEntityConfigWhenConfigsForAnotherEntitiesExist()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $anotherConfigId = new EntityConfigId(self::SCOPE, self::ANOTHER_ENTITY_CLASS);
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);

        $this->configCache->saveConfig(new Config($configId), true);
        $this->configCache->saveConfig($anotherConfig, true);

        $this->cache->expects($this->exactly(3))
            ->method('delete');
        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::ENTITY_CLASSES_KEY);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(2))
            ->method('save')
            ->with(self::SCOPE, [$anotherConfigId->getClassName() => $anotherConfig->getValues()]);
        $this->cache->expects($this->at(3))
            ->method('delete')
            ->with(self::ANOTHER_SCOPE);

        $this->configCache->deleteEntityConfig($configId->getClassName());
    }

    public function testDeleteFieldConfigWhenLocalCacheEmpty()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE);
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with(self::ENTITY_CLASS . '.' . self::ANOTHER_SCOPE);

        $this->configCache->deleteFieldConfig($configId->getClassName(), $configId->getFieldName());
    }

    public function testDeleteFieldConfigWhenConfigForLastFieldDeleted()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);

        $this->configCache->saveConfig(new Config($configId), true);

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(self::ENTITY_CLASS . '.' . self::SCOPE);
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with(self::ENTITY_CLASS . '.' . self::ANOTHER_SCOPE);

        $this->configCache->deleteFieldConfig($configId->getClassName(), $configId->getFieldName());
    }

    public function testDeleteFieldConfigWhenConfigsForAnotherFieldsExist()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $anotherConfigId = new FieldConfigId(
            self::SCOPE,
            self::ENTITY_CLASS,
            self::ANOTHER_FIELD_NAME,
            self::ANOTHER_FIELD_TYPE
        );
        $anotherConfig = new Config($anotherConfigId, ['key2' => 'val2']);

        $this->configCache->saveConfig(new Config($configId), true);
        $this->configCache->saveConfig($anotherConfig, true);

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(self::FIELD_NAMES_KEY . self::ENTITY_CLASS);
        $this->cache->expects($this->at(1))
            ->method('save')
            ->with(
                self::ENTITY_CLASS . '.' . self::SCOPE,
                [
                    $anotherConfigId->getFieldName() => [
                        $anotherConfig->getValues(),
                        $anotherConfigId->getFieldType()
                    ]
                ]
            );
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with(self::ENTITY_CLASS . '.' . self::ANOTHER_SCOPE);

        $this->configCache->deleteFieldConfig($configId->getClassName(), $configId->getFieldName());
    }

    public function testDeleteAllConfigs()
    {
        $config = new Config(
            new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
            ['key1' => 'val1']
        );

        $this->configCache->saveConfig($config, true);

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->configCache->deleteAllConfigs();
        // check that a local cache is cleaned up as well
        $this->assertNull(
            $this->configCache->getEntityConfig(self::SCOPE, self::ENTITY_CLASS, true)
        );
    }

    public function testDeleteAllConfigsLocalCacheOnly()
    {
        $config = new Config(
            new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
            ['key1' => 'val1']
        );

        $this->configCache->saveConfig($config, true);

        $this->cache->expects($this->never())
            ->method('deleteAll');

        $this->configCache->deleteAllConfigs(true);
        // check that a local cache is cleaned up
        $this->assertNull(
            $this->configCache->getEntityConfig(self::SCOPE, self::ENTITY_CLASS, true)
        );
    }

    /**
     * @dataProvider saveConfigurableProvider
     */
    public function testSaveConfigurable($fetchVal, $flag, $fieldName, $saveValue)
    {
        $this->modelCache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn($fetchVal);
        $this->modelCache->expects($this->exactly(2))
            ->method('save')
            ->with(self::ENTITY_CLASS, $this->identicalTo($saveValue));

        $this->configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName);
        // test local cache
        $this->configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName);
    }

    public function saveConfigurableProvider()
    {
        return [
            [false, true, null, [true]],
            [false, false, null, [false]],
            [[], true, null, [true]],
            [[], false, null, [false]],
            [[], null, null, []],
            [[true], true, null, [true]],
            [[false], true, null, [true]],
            [[false], false, null, [false]],
            [[true], false, null, [false]],
            [[null, ['field' => true]], true, null, [true, ['field' => true]]],
            [[null, ['field' => true]], false, null, [false, ['field' => true]]],
            [false, true, 'field', [null, ['field' => true]]],
            [false, false, 'field', [null, ['field' => false]]],
            [[], true, 'field', [null, ['field' => true]]],
            [[], false, 'field', [null, ['field' => false]]],
            [[null, ['field' => true]], true, 'field', [null, ['field' => true]]],
            [[null, ['field' => false]], true, 'field', [null, ['field' => true]]],
            [[null, ['field' => false]], false, 'field', [null, ['field' => false]]],
            [[null, ['field' => true]], false, 'field', [null, ['field' => false]]],
        ];
    }

    /**
     * @dataProvider getConfigurableProvider
     */
    public function testGetConfigurable($fetchVal, $fieldName, $expectedFlag)
    {
        $this->modelCache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn($fetchVal);

        $this->assertSame($expectedFlag, $this->configCache->getConfigurable(self::ENTITY_CLASS, $fieldName));
        // test local cache
        $this->assertSame($expectedFlag, $this->configCache->getConfigurable(self::ENTITY_CLASS, $fieldName));
    }

    public function getConfigurableProvider()
    {
        return [
            [false, null, null],
            [[], null, null],
            [[true], null, true],
            [[false], null, false],
            [[null], null, null],
            [false, 'field', null],
            [[], 'field', null],
            [[true], 'field', null],
            [[false], 'field', null],
            [[null], 'field', null],
            [[null, ['field1' => true]], 'field2', null],
            [[null, ['field' => true]], 'field', true],
            [[null, ['field' => false]], 'field', false],
        ];
    }

    public function testDeleteAllConfigurable()
    {
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, null, true);

        $this->modelCache->expects($this->once())
            ->method('deleteAll');

        $this->configCache->deleteAllConfigurable();
        // test that a local cache is cleaned up as well
        $this->assertNull(
            $this->configCache->getConfigurable(self::ENTITY_CLASS)
        );
    }

    public function testDeleteAllConfigurableLocalCacheOnly()
    {
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, null, true);

        $this->modelCache->expects($this->never())
            ->method('deleteAll');

        $this->configCache->deleteAllConfigurable(true);
        // test that a local cache is cleaned up
        $this->assertNull(
            $this->configCache->getConfigurable(self::ENTITY_CLASS)
        );
    }

    public function testDeleteAll()
    {
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, null, true);
        $this->configCache->saveConfig(
            new Config(
                new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
                ['key1' => 'val1']
            ),
            true
        );

        $this->modelCache->expects($this->once())
            ->method('deleteAll');
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->configCache->deleteAll();
        // test that a local cache is cleaned up as well
        $this->assertNull(
            $this->configCache->getConfigurable(self::ENTITY_CLASS)
        );
        $this->assertNull(
            $this->configCache->getEntityConfig(self::SCOPE, self::ENTITY_CLASS, true)
        );
    }

    public function testDeleteAllLocalCacheOnly()
    {
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, null, true);
        $this->configCache->saveConfig(
            new Config(
                new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
                ['key1' => 'val1']
            ),
            true
        );

        $this->modelCache->expects($this->never())
            ->method('deleteAll');
        $this->cache->expects($this->never())
            ->method('deleteAll');

        $this->configCache->deleteAll(true);
        // test that a local cache is cleaned up as well
        $this->assertNull(
            $this->configCache->getConfigurable(self::ENTITY_CLASS)
        );
        $this->assertNull(
            $this->configCache->getEntityConfig(self::SCOPE, self::ENTITY_CLASS, true)
        );
    }

    public function testBeginBatchWhenBatchAlreadyStarted()
    {
        $this->expectException(\Oro\Bundle\EntityConfigBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('A batch already started. Nested batches are not supported.');

        $this->configCache->beginBatch();
        $this->configCache->beginBatch();
    }

    public function testSaveBatchWhenBatchIsNotStarted()
    {
        $this->expectException(\Oro\Bundle\EntityConfigBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('A batch is not started.');

        $this->configCache->saveBatch();
    }

    public function testCancelBatchShouldNotThrowExceptionWhenBatchIsNotStarted()
    {
        $this->configCache->cancelBatch();
    }

    public function testDeleteAllConfigsInBatchShouldNotBeAllowed()
    {
        $this->expectException(\Oro\Bundle\EntityConfigBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('deleteAllConfigs() is not allowed inside a batch.');

        $this->configCache->beginBatch();
        $this->configCache->deleteAllConfigs();
    }

    public function testDeleteAllConfigurableInBatchShouldNotBeAllowed()
    {
        $this->expectException(\Oro\Bundle\EntityConfigBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('deleteAllConfigurable() is not allowed inside a batch.');

        $this->configCache->beginBatch();
        $this->configCache->deleteAllConfigurable();
    }

    public function testDeleteAllInBatchShouldNotBeAllowed()
    {
        $this->expectException(\Oro\Bundle\EntityConfigBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('deleteAllConfigurable() is not allowed inside a batch.');

        $this->configCache->beginBatch();
        $this->configCache->deleteAll();
    }

    public function testSaveConfigurableInBatch()
    {
        $this->modelCache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);
        $this->modelCache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                self::ENTITY_CLASS => [
                    true,
                    [self::FIELD_NAME => true]
                ]
            ]);
        $this->modelCache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('deleteMultiple');

        $this->configCache->beginBatch();
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS);
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, self::FIELD_NAME);
        $this->configCache->saveBatch();
    }

    public function testSaveEntitiesInBatch()
    {
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                self::ENTITY_CLASSES_KEY => [
                    self::ANOTHER_ENTITY_CLASS => ['i' => 2, 'h' => true]
                ]
            ]);
        $this->cache->expects($this->never())
            ->method('deleteMultiple');
        $this->modelCache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('save');

        $this->configCache->beginBatch();
        $this->configCache->saveEntities(
            [self::ENTITY_CLASS => ['i' => 1, 'h' => false]]
        );
        $this->configCache->saveEntities(
            [self::ANOTHER_ENTITY_CLASS => ['i' => 2, 'h' => true]]
        );
        $this->configCache->saveBatch();
    }

    public function testSaveFieldsInBatch()
    {
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                self::FIELD_NAMES_KEY . self::ENTITY_CLASS => [
                    self::ANOTHER_FIELD_NAME => ['i' => 1, 'h' => false, 't' => self::ANOTHER_FIELD_TYPE]
                ]
            ]);
        $this->cache->expects($this->never())
            ->method('deleteMultiple');
        $this->modelCache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('save');

        $this->configCache->beginBatch();
        $this->configCache->saveFields(
            self::ENTITY_CLASS,
            [self::FIELD_NAME => ['i' => 1, 'h' => false, 't' => self::FIELD_TYPE]]
        );
        $this->configCache->saveFields(
            self::ENTITY_CLASS,
            [self::ANOTHER_FIELD_NAME => ['i' => 1, 'h' => false, 't' => self::ANOTHER_FIELD_TYPE]]
        );
        $this->configCache->saveBatch();
    }

    public function testSaveConfigInBatch()
    {
        $this->cache->expects($this->exactly(2))
            ->method('fetch')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                self::SCOPE                            => [
                    self::ENTITY_CLASS => ['key1' => 'val1']
                ],
                self::ENTITY_CLASS . '.' . self::SCOPE => [
                    self::FIELD_NAME => [['key10' => 'val10'], self::FIELD_TYPE]
                ]
            ]);
        $this->cache->expects($this->never())
            ->method('deleteMultiple');
        $this->modelCache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('save');

        $this->configCache->beginBatch();
        $this->configCache->saveConfig(
            new Config(
                new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
                ['key1' => 'val1']
            )
        );
        $this->configCache->saveConfig(
            new Config(
                new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE),
                ['key10' => 'val10']
            )
        );
        $this->configCache->saveBatch();
    }

    public function testDeleteConfigInBatch()
    {
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->once())
            ->method('deleteMultiple')
            ->with([
                self::ENTITY_CLASSES_KEY,
                self::FIELD_NAMES_KEY . self::ENTITY_CLASS,
                self::SCOPE,
                self::ANOTHER_SCOPE,
                self::ENTITY_CLASS . '.' . self::SCOPE,
                self::ENTITY_CLASS . '.' . self::ANOTHER_SCOPE
            ]);
        $this->cache->expects($this->never())
            ->method('saveMultiple');
        $this->modelCache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->never())
            ->method('delete');

        $this->configCache->beginBatch();
        $this->configCache->deleteEntityConfig(self::ENTITY_CLASS);
        $this->configCache->deleteFieldConfig(self::ENTITY_CLASS, self::FIELD_NAME);
        $this->configCache->saveBatch();
    }

    public function testLastSaveOrDeleteOperationShouldWinInBatch()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                self::SCOPE => [
                    'Test\Entity1' => ['key1' => 'val1'],
                    'Test\Entity3' => ['key3' => 'val3']
                ]
            ]);
        $this->cache->expects($this->once())
            ->method('deleteMultiple')
            ->with([
                self::ENTITY_CLASSES_KEY,
                self::FIELD_NAMES_KEY . 'Test\Entity2',
                self::ANOTHER_SCOPE,
                self::FIELD_NAMES_KEY . 'Test\Entity3'
            ]);
        $this->modelCache->expects($this->never())
            ->method('saveMultiple');
        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->never())
            ->method('delete');

        $this->configCache->beginBatch();
        $this->configCache->saveConfig(
            new Config(new EntityConfigId(self::SCOPE, 'Test\Entity1'), ['key1' => 'val1'])
        );
        $this->configCache->saveConfig(
            new Config(new EntityConfigId(self::SCOPE, 'Test\Entity2'), ['key2' => 'val2'])
        );
        $this->configCache->deleteEntityConfig('Test\Entity2');
        $this->configCache->deleteEntityConfig('Test\Entity3');
        $this->configCache->saveConfig(
            new Config(new EntityConfigId(self::SCOPE, 'Test\Entity3'), ['key3' => 'val3'])
        );
        $this->configCache->saveBatch();
    }
}
