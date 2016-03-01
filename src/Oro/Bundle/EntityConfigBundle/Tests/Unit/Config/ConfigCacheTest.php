<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConfigCacheTest extends \PHPUnit_Framework_TestCase
{
    const SCOPE = 'testScope';
    const ENTITY_CLASS = 'Test\Entity';
    const FIELD_NAME = 'testField';
    const FIELD_TYPE = 'integer';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $modelCache;

    /** @var ConfigCache */
    private $configCache;

    protected function setUp()
    {
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['fetch', 'save', 'delete', 'deleteAll', 'flushAll'])
            ->getMockForAbstractClass();

        $this->modelCache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['fetch', 'save', 'delete', 'deleteAll', 'flushAll'])
            ->getMockForAbstractClass();

        $this->configCache = new ConfigCache($this->cache, $this->modelCache);
    }

    protected function tearDown()
    {
        unset($this->cache, $this->modelCache, $this->configCache);
    }

    public function testSaveEntities()
    {
        $entities = ['Test\Entity1' => true, 'Test\Entity2' => true];

        $this->cache->expects($this->once())
            ->method('save')
            ->with(ConfigCache::ENTITY_CLASSES_KEY, $entities)
            ->willReturn(true);
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
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS, $fields)
            ->willReturn(true);
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
            ->with(ConfigCache::ENTITY_CLASSES_KEY)
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
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS)
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
            ->with(ConfigCache::ENTITY_CLASSES_KEY)
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
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS)
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
            ->with(ConfigCache::ENTITY_CLASSES_KEY)
            ->willReturn(true);

        $this->configCache->saveEntities($entities);

        $this->assertTrue(
            $this->configCache->deleteEntities()
        );
        $this->assertNull(
            $this->configCache->getEntities()
        );
    }

    public function testDeleteFields()
    {
        $fields = ['field1' => ['t' => 'integer', 'h' => true], 'field2' => ['t' => 'string', 'h' => false]];

        $this->cache->expects($this->once())
            ->method('delete')
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS)
            ->willReturn(true);

        $this->configCache->saveFields(self::ENTITY_CLASS, $fields);

        $this->assertTrue(
            $this->configCache->deleteFields(self::ENTITY_CLASS)
        );
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

        $this->assertTrue(
            $this->configCache->deleteEntities(true)
        );
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

        $this->assertTrue(
            $this->configCache->deleteFields(self::ENTITY_CLASS, true)
        );
        $this->assertNull(
            $this->configCache->getFields(self::ENTITY_CLASS)
        );
    }

    public function testSaveEntityConfig()
    {
        $configId     = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configValues = ['key1' => 'val1'];
        $config       = new Config($configId, $configValues);
        $cacheKey     = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($cacheKey, [self::SCOPE => $configValues])
            ->willReturn(true);

        $this->assertTrue($this->configCache->saveConfig($config));
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
        $configId     = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $configValues = ['key1' => 'val1'];
        $config       = new Config($configId, $configValues);
        $cacheKey     = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $cacheKey,
                [
                    ConfigCache::VALUES_KEY     => [self::SCOPE => $configValues],
                    ConfigCache::FIELD_TYPE_KEY => self::FIELD_TYPE
                ]
            )
            ->willReturn(true);

        $this->assertTrue($this->configCache->saveConfig($config));
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
        $config   = new Config($configId);
        $cacheKey = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('save');

        $this->assertTrue($this->configCache->saveConfig($config, true));
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
        $config   = new Config($configId);
        $cacheKey = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('save');

        $this->assertTrue($this->configCache->saveConfig($config, true));
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

    public function testSaveEntityConfigWhenAnotherScopeIsAlreadyCached()
    {
        $configId            = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configValues        = ['key1' => 'val1'];
        $config              = new Config($configId, $configValues);
        $anotherConfigId     = new EntityConfigId('another', self::ENTITY_CLASS);
        $anotherConfigValues = ['key2' => 'val2'];
        $anotherConfig       = new Config($anotherConfigId, $anotherConfigValues);
        $cacheKey            = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(['another' => $anotherConfigValues]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($cacheKey, ['another' => $anotherConfigValues, self::SCOPE => $configValues])
            ->willReturn(true);

        $this->assertTrue($this->configCache->saveConfig($config));
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

    public function testSaveFieldConfigWhenAnotherScopeIsAlreadyCached()
    {
        $configId            = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $configValues        = ['key1' => 'val1'];
        $config              = new Config($configId, $configValues);
        $anotherConfigId     = new FieldConfigId('another', self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $anotherConfigValues = ['key2' => 'val2'];
        $anotherConfig       = new Config($anotherConfigId, $anotherConfigValues);
        $cacheKey            = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(
                [
                    ConfigCache::VALUES_KEY     => ['another' => $anotherConfigValues],
                    ConfigCache::FIELD_TYPE_KEY => self::FIELD_TYPE
                ]
            );
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $cacheKey,
                [
                    ConfigCache::VALUES_KEY     => ['another' => $anotherConfigValues, self::SCOPE => $configValues],
                    ConfigCache::FIELD_TYPE_KEY => self::FIELD_TYPE
                ]
            )
            ->willReturn(true);

        $this->assertTrue($this->configCache->saveConfig($config));
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
            new EntityConfigId('scope1', self::ENTITY_CLASS),
            ['key1' => 'val1']
        );
        $config2 = new Config(
            new EntityConfigId('scope2', self::ENTITY_CLASS),
            ['key2' => 'val2']
        );

        $this->cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function ($key, $data) {
                    $this->cache->expects($this->once())
                        ->method('fetch')
                        ->with($key)
                        ->willReturn($data);

                    return true;
                }
            );

        $this->assertTrue(
            $this->configCache->saveEntityConfigValues(
                [
                    $config1->getId()->getScope() => $config1->getValues(),
                    $config2->getId()->getScope() => $config2->getValues()
                ],
                self::ENTITY_CLASS
            )
        );

        // test that configs saved right
        $this->assertEquals(
            $config1,
            $this->configCache->getEntityConfig(
                $config1->getId()->getScope(),
                self::ENTITY_CLASS
            )
        );
        $this->assertEquals(
            $config2,
            $this->configCache->getEntityConfig(
                $config2->getId()->getScope(),
                self::ENTITY_CLASS
            )
        );
    }

    public function testSaveFieldConfigValues()
    {
        $config1 = new Config(
            new FieldConfigId('scope1', self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE),
            ['key1' => 'val1']
        );
        $config2 = new Config(
            new FieldConfigId('scope2', self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE),
            ['key2' => 'val2']
        );

        $this->cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function ($key, $data) {
                    $this->cache->expects($this->once())
                        ->method('fetch')
                        ->with($key)
                        ->willReturn($data);

                    return true;
                }
            );

        $this->assertTrue(
            $this->configCache->saveFieldConfigValues(
                [
                    $config1->getId()->getScope() => $config1->getValues(),
                    $config2->getId()->getScope() => $config2->getValues()
                ],
                self::ENTITY_CLASS,
                self::FIELD_NAME,
                self::FIELD_TYPE
            )
        );

        // test that configs saved right
        $this->assertEquals(
            $config1,
            $this->configCache->getFieldConfig(
                $config1->getId()->getScope(),
                self::ENTITY_CLASS,
                self::FIELD_NAME
            )
        );
        $this->assertEquals(
            $config2,
            $this->configCache->getFieldConfig(
                $config2->getId()->getScope(),
                self::ENTITY_CLASS,
                self::FIELD_NAME
            )
        );
    }

    public function testGetEntityConfig()
    {
        $configId     = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configValues = ['key1' => 'val1'];
        $config       = new Config($configId, $configValues);
        $cacheKey     = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn([self::SCOPE => $configValues]);

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
        $configId     = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME);
        $configValues = ['key1' => 'val1'];
        $config       = new Config(
            new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE),
            $configValues
        );
        $cacheKey     = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(
                [
                    ConfigCache::VALUES_KEY     => [self::SCOPE => $configValues],
                    ConfigCache::FIELD_TYPE_KEY => self::FIELD_TYPE
                ]
            );

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

    public function testGetEntityConfigNotCached()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $cacheKey = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
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
        $cacheKey = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
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

    public function testGetEntityConfigNotCachedScope()
    {
        $configId            = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $anotherConfigId     = new EntityConfigId('another', self::ENTITY_CLASS);
        $anotherConfigValues = ['key2' => 'val2'];
        $anotherConfig       = new Config($anotherConfigId, $anotherConfigValues);
        $cacheKey            = self::ENTITY_CLASS;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(['another' => $anotherConfigValues]);

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

    public function testGetFieldConfigNotCachedScope()
    {
        $configId            = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $anotherConfigId     = new FieldConfigId('another', self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $anotherConfigValues = ['key2' => 'val2'];
        $anotherConfig       = new Config($anotherConfigId, $anotherConfigValues);
        $cacheKey            = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(
                [
                    ConfigCache::VALUES_KEY     => ['another' => $anotherConfigValues],
                    ConfigCache::FIELD_TYPE_KEY => self::FIELD_TYPE
                ]
            );

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

    public function testDeleteEntityConfig()
    {
        $configId = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $cacheKey = self::ENTITY_CLASS;

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(ConfigCache::ENTITY_CLASSES_KEY)
            ->willReturn(true);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS)
            ->willReturn(true);
        $this->cache->expects($this->at(2))
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->assertTrue(
            $this->configCache->deleteEntityConfig($configId->getClassName())
        );
    }

    public function testDeleteFieldConfig()
    {
        $configId = new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, self::FIELD_NAME, self::FIELD_TYPE);
        $cacheKey = self::ENTITY_CLASS . '.' . self::FIELD_NAME;

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with(ConfigCache::FIELD_NAMES_KEY . self::ENTITY_CLASS)
            ->willReturn(true);
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->assertTrue(
            $this->configCache->deleteFieldConfig($configId->getClassName(), $configId->getFieldName())
        );
    }

    public function testDeleteAllConfigs()
    {
        $config = new Config(
            new EntityConfigId(self::SCOPE, self::ENTITY_CLASS),
            ['key1' => 'val1']
        );

        $this->configCache->saveConfig($config, true);

        $this->cache->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->assertTrue($this->configCache->deleteAllConfigs());
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

        $this->assertTrue($this->configCache->deleteAllConfigs(true));
        // check that a local cache is cleaned up
        $this->assertNull(
            $this->configCache->getEntityConfig(self::SCOPE, self::ENTITY_CLASS, true)
        );
    }

    public function testFlushAllConfigs()
    {
        $this->cache->expects($this->once())
            ->method('flushAll')
            ->willReturn(true);

        $this->assertTrue($this->configCache->flushAllConfigs());
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
            ->with(self::ENTITY_CLASS, $this->identicalTo($saveValue))
            ->willReturn(true);

        $this->assertTrue($this->configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName));
        // test local cache
        $this->assertTrue($this->configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName));
    }

    public function saveConfigurableProvider()
    {
        return [
            [false, true, null, [ConfigCache::FLAG_KEY => true]],
            [false, false, null, [ConfigCache::FLAG_KEY => false]],
            [[], true, null, [ConfigCache::FLAG_KEY => true]],
            [[], false, null, [ConfigCache::FLAG_KEY => false]],
            [[ConfigCache::FLAG_KEY => true], true, null, [ConfigCache::FLAG_KEY => true]],
            [[ConfigCache::FLAG_KEY => false], true, null, [ConfigCache::FLAG_KEY => true]],
            [[ConfigCache::FLAG_KEY => false], false, null, [ConfigCache::FLAG_KEY => false]],
            [[ConfigCache::FLAG_KEY => true], false, null, [ConfigCache::FLAG_KEY => false]],
            [
                [ConfigCache::FIELDS_KEY => ['field' => true]],
                true,
                null,
                [ConfigCache::FIELDS_KEY => ['field' => true], ConfigCache::FLAG_KEY => true]
            ],
            [
                [ConfigCache::FIELDS_KEY => ['field' => true]],
                false,
                null,
                [ConfigCache::FIELDS_KEY => ['field' => true], ConfigCache::FLAG_KEY => false]
            ],
            [false, true, 'field', [ConfigCache::FIELDS_KEY => ['field' => true]]],
            [false, false, 'field', [ConfigCache::FIELDS_KEY => ['field' => false]]],
            [[], true, 'field', [ConfigCache::FIELDS_KEY => ['field' => true]]],
            [[], false, 'field', [ConfigCache::FIELDS_KEY => ['field' => false]]],
            [
                [ConfigCache::FIELDS_KEY => ['field' => true]],
                true,
                'field',
                [ConfigCache::FIELDS_KEY => ['field' => true]]
            ],
            [
                [ConfigCache::FIELDS_KEY => ['field' => false]],
                true,
                'field',
                [ConfigCache::FIELDS_KEY => ['field' => true]]
            ],
            [
                [ConfigCache::FIELDS_KEY => ['field' => false]],
                false,
                'field',
                [ConfigCache::FIELDS_KEY => ['field' => false]]
            ],
            [
                [ConfigCache::FIELDS_KEY => ['field' => true]],
                false,
                'field',
                [ConfigCache::FIELDS_KEY => ['field' => false]]
            ],
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
            [[ConfigCache::FLAG_KEY => true], null, true],
            [[ConfigCache::FLAG_KEY => false], null, false],
            [[ConfigCache::FIELDS_KEY => []], null, null],
            [false, 'field', null],
            [[], 'field', null],
            [[ConfigCache::FLAG_KEY => true], 'field', null],
            [[ConfigCache::FLAG_KEY => false], 'field', null],
            [[ConfigCache::FIELDS_KEY => []], 'field', null],
            [[ConfigCache::FIELDS_KEY => ['field1' => true]], 'field2', null],
            [[ConfigCache::FIELDS_KEY => ['field' => true]], 'field', true],
            [[ConfigCache::FIELDS_KEY => ['field' => false]], 'field', false],
        ];
    }

    public function testDeleteAllConfigurable()
    {
        $this->configCache->saveConfigurable(true, self::ENTITY_CLASS, null, true);

        $this->modelCache->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->assertTrue($this->configCache->deleteAllConfigurable());
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

        $this->assertTrue($this->configCache->deleteAllConfigurable(true));
        // test that a local cache is cleaned up
        $this->assertNull(
            $this->configCache->getConfigurable(self::ENTITY_CLASS)
        );
    }

    public function testFlushAllConfigurable()
    {
        $this->modelCache->expects($this->once())
            ->method('flushAll')
            ->willReturn(true);

        $this->assertTrue($this->configCache->flushAllConfigurable());
    }
}
