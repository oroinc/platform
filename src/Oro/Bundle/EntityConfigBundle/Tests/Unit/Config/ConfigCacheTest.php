<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigCacheTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';
    const SCOPE = 'testScope';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $modelCache;

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
    }

    protected function tearDown()
    {
        unset($this->cache);
        unset($this->modelCache);
    }

    public function testSaveConfig()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config      = new Config($configId);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::ENTITY_CLASS, [self::SCOPE => $config])
            ->willReturn(true);

        $this->assertTrue($configCache->saveConfig($config));
        // test local cache
        $this->assertEquals($config, $configCache->getConfig($configId));
    }

    public function testSaveConfigLocalOnly()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config      = new Config($configId);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('save');

        $this->assertTrue($configCache->saveConfig($config, true));
        // test local cache
        $this->assertEquals($config, $configCache->getConfig($configId));
    }

    public function testSaveConfigWhenAnotherScopeIsAlreadyCached()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config      = new Config($configId);
        $otherConfig = new Config(new EntityConfigId('other_scope', self::ENTITY_CLASS));
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(['other_scope' => $otherConfig]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::ENTITY_CLASS, ['other_scope' => $otherConfig, self::SCOPE => $config])
            ->willReturn(true);

        $this->assertTrue($configCache->saveConfig($config));
        // test local cache
        $this->assertEquals($config, $configCache->getConfig($configId));
    }

    public function testGetConfig()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $config      = new Config($configId);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn([self::SCOPE => $config]);

        $this->assertEquals($config, $configCache->getConfig($configId));
        // test local cache
        $this->assertEquals($config, $configCache->getConfig($configId));
    }

    public function testGetConfigNotCached()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertNull($configCache->getConfig($configId));
        // test local cache
        $this->assertNull($configCache->getConfig($configId));
    }

    public function testGetConfigNotCachedScope()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn(['other_scope' => new Config(new EntityConfigId('other_scope', self::ENTITY_CLASS))]);

        $this->assertNull($configCache->getConfig($configId));
        // test local cache
        $this->assertNull($configCache->getConfig($configId));
    }

    public function testDeleteConfig()
    {
        $configId    = new EntityConfigId(self::SCOPE, self::ENTITY_CLASS);
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->assertTrue($configCache->deleteConfig($configId));
    }

    public function testDeleteAllConfigs()
    {
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->assertTrue($configCache->deleteAllConfigs());
    }

    public function testFlushAllConfigs()
    {
        $configCache = $this->getConfigCache();

        $this->cache->expects($this->once())
            ->method('flushAll')
            ->willReturn(true);

        $this->assertTrue($configCache->flushAllConfigs());
    }

    /**
     * @dataProvider saveConfigurableProvider
     */
    public function testSaveConfigurable($fetchVal, $flag, $fieldName, $saveValue)
    {
        $configCache = $this->getConfigCache();

        $this->modelCache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn($fetchVal);
        $this->modelCache->expects($this->exactly(2))
            ->method('save')
            ->with(self::ENTITY_CLASS, $this->identicalTo($saveValue))
            ->willReturn(true);

        $this->assertTrue($configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName));
        // test local cache
        $this->assertTrue($configCache->saveConfigurable($flag, self::ENTITY_CLASS, $fieldName));
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
        $configCache = $this->getConfigCache();

        $this->modelCache->expects($this->once())
            ->method('fetch')
            ->with(self::ENTITY_CLASS)
            ->willReturn($fetchVal);

        $this->assertSame($expectedFlag, $configCache->getConfigurable(self::ENTITY_CLASS, $fieldName));
        // test local cache
        $this->assertSame($expectedFlag, $configCache->getConfigurable(self::ENTITY_CLASS, $fieldName));
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
        $configCache = $this->getConfigCache();

        $this->modelCache->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->assertTrue($configCache->deleteAllConfigurable());
    }

    public function testFlushAllConfigurable()
    {
        $configCache = $this->getConfigCache();

        $this->modelCache->expects($this->once())
            ->method('flushAll')
            ->willReturn(true);

        $this->assertTrue($configCache->flushAllConfigurable());
    }

    /**
     * @return ConfigCache
     */
    protected function getConfigCache()
    {
        return new ConfigCache($this->cache, $this->modelCache);
    }
}
