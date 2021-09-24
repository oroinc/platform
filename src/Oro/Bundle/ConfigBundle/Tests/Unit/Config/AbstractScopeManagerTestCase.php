<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Abstract test case for scope manager unit tests.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractScopeManagerTestCase extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AbstractScopeManager */
    protected $manager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var ConfigRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repo;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var ConfigBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $configBag;

    /** @var CacheProvider */
    protected $cache;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ConfigRepository::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->em->expects($this->any())->method('getRepository')->with(Config::class)->willReturn($this->repo);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine */
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())->method('getManagerForClass')->with(Config::class)->willReturn($this->em);

        $this->cache = new ArrayCache();

        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->configBag = $this->createMock(ConfigBag::class);

        $this->manager = $this->createManager($doctrine, $this->cache, $this->dispatcher, $this->configBag);
    }

    /**
     * Test get info from loaded settings
     */
    public function testGetInfoLoaded()
    {
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));

        /** @var ConfigValue $configValue1 */
        $configValue1 = $this->getEntity(
            ConfigValue::class,
            [
                'section' => 'oro_user',
                'name' => 'level',
                'value' => 2000,
                'type' => 'scalar',
                'createdAt' => $datetime,
                'updatedAt' => $datetime
            ]
        );

        $config = new Config();
        $config->getValues()->add($configValue1);

        $this->repo->expects($this->once())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), 0)
            ->willReturn($config);

        $key = $this->getScopedEntityName() . '_0';
        $this->assertFalse($this->cache->contains($key));

        $this->configBag->expects($this->any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        [$created, $updated, $isNullValue] = $this->manager->getInfo('oro_user.level');

        $this->assertEquals($this->cache->fetch($key), $this->getCachedConfig($config));
        $this->assertEquals($configValue1->getCreatedAt(), $created);
        $this->assertEquals($configValue1->getUpdatedAt(), $updated);
        $this->assertFalse($isNullValue);
        $this->assertNull($this->manager->getSettingValue('oro_user.greeting'));
        $this->assertNull($this->manager->getSettingValue('oro_test.nosetting'));
        $this->assertNull($this->manager->getSettingValue('noservice.nosetting'));
    }

    /**
     * @dataProvider getInfoLoadedWithNormalizationProvider
     *
     * @param string $rawValue
     * @param string $dataType
     * @param mixed $expectedValue
     */
    public function testGetInfoLoadedWithNormalization(string $rawValue, string $dataType, $expectedValue)
    {
        /** @var ConfigValue $configValue1 */
        $configValue1 = $this->getEntity(
            ConfigValue::class,
            [
                'section' => 'oro_user',
                'name' => 'level',
                'value' => $rawValue,
                'type' => 'scalar',
            ]
        );

        $config = new Config();
        $config->getValues()->add($configValue1);

        $this->repo->expects($this->once())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), 0)
            ->willReturn($config);

        $key = $this->getScopedEntityName() . '_0';
        $this->assertFalse($this->cache->contains($key));

        $settingPath = 'oro_user.level';
        $this->configBag->expects($this->any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [
                    $settingPath => [
                        'data_type' => $dataType,
                    ],
                ],
            ]);
        $this->manager->getInfo($settingPath);

        $fromCache = $this->cache->fetch($key);
        $fromConfig = $this->getCachedConfig($config);
        $this->assertEquals($fromCache, $fromConfig);
        $this->assertNotSame($fromCache['oro_user']['level']['value'], $fromConfig['oro_user']['level']['value']);
        $this->assertSame($expectedValue, $fromCache['oro_user']['level']['value']);
    }

    public function getInfoLoadedWithNormalizationProvider()
    {
        return [
            'integer' => [
                'rawValue' => '1000',
                'dataType' => 'integer',
                'expectedValue' => 1000,
            ],
            'decimal' => [
                'rawValue' => '1000.42',
                'dataType' => 'decimal',
                'expectedValue' => 1000.42,
            ],
            'boolean' => [
                'rawValue' => '1',
                'dataType' => 'boolean',
                'expectedValue' => true,
            ],
            'boolean negative' => [
                'rawValue' => '',
                'dataType' => 'boolean',
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * Test flush settings
     */
    public function testFlush()
    {
        $scopeId = 1;
        $config = new Config();
        $this->prepareSave($config, $scopeId);
        $this->manager->set('oro_user.update', 'updated value', $scopeId);
        $key = sprintf('%s_%s', $this->getScopedEntityName(), $scopeId);

        $this->assertFalse($this->cache->contains($key));
        $this->assertNotEmpty($this->manager->getChanges($scopeId));

        $this->configBag->expects($this->any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        $this->manager->flush($scopeId);

        $this->assertEquals($this->cache->fetch($key), $this->getCachedConfig($config));
        $this->assertEquals(
            'updated value',
            $this->manager->getSettingValue('oro_user.update', false, $scopeId)
        );
        $this->assertEmpty($this->manager->getChanges($scopeId));
    }

    /**
     * Test saving settings
     */
    public function testSave()
    {
        $scopeId = null;
        $settings = [
            'oro_user.update' => [
                'value' => 'updated value',
                'use_parent_scope_value' => false
            ],
            'oro_user.remove' => [
                'use_parent_scope_value' => true
            ],
            'oro_user.add' => [
                'value' => 'new value',
                'use_parent_scope_value' => false
            ],
        ];
        $config = new Config();
        $this->prepareSave($config, $scopeId);

        $key = sprintf('%s_%s', $this->getScopedEntityName(), (int) $scopeId);

        $this->assertFalse($this->cache->contains($key));

        $this->configBag->expects($this->any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        $result = $this->manager->save($settings);
        $this->assertEquals(
            [
                [
                    'oro_user.update' => 'updated value',
                    'oro_user.add'    => 'new value'
                ],
                [
                    'oro_user.remove'
                ]
            ],
            $result
        );

        $this->assertEquals($this->cache->fetch($key), $this->getCachedConfig($config));
        $this->assertEquals('updated value', $this->manager->getSettingValue('oro_user.update'));
        $this->assertNull($this->manager->getSettingValue('oro_user.remove'));
        $this->assertEquals('new value', $this->manager->getSettingValue('oro_user.add'));
    }

    public function testCachedDataTypes(): void
    {
        $scopeId = null;
        $settings = [
            'oro_user.integer' => ['value' => '1', 'use_parent_scope_value' => false],
            'oro_user.decimal' => ['value' => '1', 'use_parent_scope_value' => false],
            'oro_user.boolean' => ['value' => '1', 'use_parent_scope_value' => false],
        ];

        $this->configBag
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn([
                'fields' => [
                    'oro_user.integer' => ['data_type' => 'integer'],
                    'oro_user.decimal' => ['data_type' => 'decimal'],
                    'oro_user.boolean' => ['data_type' => 'boolean']
                ]
            ]);

        $key = sprintf('%s_%s', $this->getScopedEntityName(), (int) $scopeId);
        $this->assertFalse($this->cache->contains($key));
        $this->manager->save($settings);
        $this->assertTrue($this->cache->contains($key));

        $cachedResult = $this->cache->fetch($key);
        $this->assertSame(
            [
                'oro_user' => [
                    'integer' => [
                        'value' => 1,
                        'use_parent_scope_value' => false,
                        'createdAt' => null,
                        'updatedAt' => null
                    ],
                    'decimal' => [
                        'value' => 1.0,
                        'use_parent_scope_value' => false,
                        'createdAt' => null,
                        'updatedAt' => null
                    ],
                    'boolean' => [
                        'value' => true,
                        'use_parent_scope_value' => false,
                        'createdAt' => null,
                        'updatedAt' => null
                    ],
                ]
            ],
            $cachedResult
        );
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals($this->getScopedEntityName(), $this->manager->getScopedEntityName());
    }

    public function testGetScopeIdFromEntity()
    {
        $entity = $this->getScopedEntity();
        $entityId = $this->getEntityId($entity);
        $this->assertSame($entityId, $this->manager->getScopeIdFromEntity($entity));
    }

    public function testGetScopeIdFromUnsupportedEntity()
    {
        $entity = new \stdClass();
        $this->assertNull($this->manager->getScopeIdFromEntity($entity));
    }

    public function testSetScopeIdFromEntity()
    {
        $entity = $this->getScopedEntity();
        $entityId = $this->getEntityId($entity);
        $newScopeId = $entityId ?: $this->manager->getScopeId();
        $this->dispatcher->expects($this->exactly($newScopeId ? 1 : 0))
            ->method('dispatch')
            ->with(static::anything(), ConfigManagerScopeIdUpdateEvent::EVENT_NAME);

        $this->manager->setScopeIdFromEntity($entity);
        $this->assertEquals($newScopeId, $this->manager->getScopeId());
    }

    public function testSetScopeIdFromUnsupportedEntity()
    {
        $this->manager->setScopeIdFromEntity($this->getScopedEntity());
        $oldScopeId = $this->manager->getScopeId();
        $this->dispatcher->expects($this->exactly(0))
            ->method('dispatch')
            ->with(static::anything(), ConfigManagerScopeIdUpdateEvent::EVENT_NAME);

        $entity = new \stdClass();
        $this->manager->setScopeIdFromEntity($entity);

        $this->assertSame($oldScopeId, $this->manager->getScopeId());
    }

    /**
     * @param Config $config
     * @param int $scopeId
     */
    protected function prepareSave(Config $config, $scopeId)
    {
        $configValue1 = new ConfigValue();
        $configValue1->setSection('oro_user')->setName('update')->setValue('old value')->setType('scalar');

        $configValue2 = new ConfigValue();
        $configValue2->setSection('oro_user')->setName('remove')->setValue('test')->setType('scalar');

        $config->getValues()->add($configValue1);
        $config->getValues()->add($configValue2);

        $this->repo->expects($this->any())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), $scopeId)
            ->willReturn($config);

        $this->em->expects($this->once())->method('persist')->with($this->identicalTo($config));
        $this->em->expects($this->once())->method('flush');
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    protected function getCachedConfig(Config $config)
    {
        $cachedConfig = [];

        foreach ($config->getValues() as $configValue) {
            if (isset($cachedConfig[$configValue->getSection()])) {
                $cachedConfig[$configValue->getSection()][$configValue->getName()] = [
                    'value' => $configValue->getValue(),
                    'use_parent_scope_value' => false,
                    'createdAt' => $configValue->getCreatedAt(),
                    'updatedAt' => $configValue->getUpdatedAt()
                ];
            } else {
                $cachedConfig[$configValue->getSection()] = [
                    $configValue->getName() => [
                        'value' => $configValue->getValue(),
                        'use_parent_scope_value' => false,
                        'createdAt' => $configValue->getCreatedAt(),
                        'updatedAt' => $configValue->getUpdatedAt()
                    ]
                ];
            }
        }

        return $cachedConfig;
    }

    /**
     * @return object|null
     */
    protected function getScopedEntity()
    {
        return null;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    protected function getEntityId($entity)
    {
        return $entity && method_exists($entity, 'getId') ? $entity->getId() : null;
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param CacheProvider $cache
     * @param EventDispatcher $eventDispatcher
     * @param ConfigBag $configBag
     *
     * @return AbstractScopeManager
     */
    abstract protected function createManager(
        ManagerRegistry $doctrine,
        CacheProvider $cache,
        EventDispatcher $eventDispatcher,
        ConfigBag $configBag
    );

    /**
     * @return string
     */
    abstract protected function getScopedEntityName();
}
