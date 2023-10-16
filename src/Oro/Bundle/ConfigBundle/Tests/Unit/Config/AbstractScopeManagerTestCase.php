<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Abstract test case for scope manager unit tests.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractScopeManagerTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var ConfigRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repo;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var ConfigBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $configBag;

    /** @var AbstractScopeManager */
    protected $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->configBag = $this->createMock(ConfigBag::class);

        $this->repo = $this->createMock(ConfigRepository::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects(self::any())
            ->method('getRepository')
            ->with(Config::class)
            ->willReturn($this->repo);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Config::class)
            ->willReturn($this->em);

        $this->manager = $this->createManager();
    }

    abstract protected function createManager(): AbstractScopeManager;

    abstract protected function getScopedEntityName(): string;

    abstract protected function getScopedEntity(): object;

    protected function getEntityId(?object $entity): mixed
    {
        return null !== $entity && method_exists($entity, 'getId') ? $entity->getId() : null;
    }

    protected function getConfigValue(string $section, string $name, string $type, mixed $value): ConfigValue
    {
        $configValue = new ConfigValue();
        $configValue->setSection($section);
        $configValue->setName($name);
        $configValue->setType($type);
        $configValue->setValue($value);

        return $configValue;
    }

    protected function getCachedConfig(Config $config): array
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

    protected function prepareSave(Config $config, ?int $scopeId): void
    {
        $config->getValues()->add($this->getConfigValue('oro_user', 'update', 'scalar', 'old value'));
        $config->getValues()->add($this->getConfigValue('oro_user', 'remove', 'scalar', 'test'));

        $this->repo->expects(self::any())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), $scopeId)
            ->willReturn($config);

        $this->em->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($config));
        $this->em->expects(self::once())
            ->method('flush');
    }

    /**
     * Test get info from loaded settings
     */
    public function testGetInfoLoaded(): void
    {
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));

        $configValue1 = $this->getConfigValue('oro_user', 'level', 'scalar', 2000);
        $configValue1->setUpdatedAt($datetime);
        ReflectionUtil::setPropertyValue($configValue1, 'createdAt', $datetime);

        $config = new Config();
        $config->getValues()->add($configValue1);

        $this->repo->expects(self::exactly(4))
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), 0)
            ->willReturn($config);

        $key = $this->getScopedEntityName() . '_0';
        $this->cache->expects(self::exactly(4))
            ->method('get')
            ->with($key)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->configBag->expects(self::any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        [$created, $updated, $isNullValue] = $this->manager->getInfo('oro_user.level');

        self::assertEquals($configValue1->getCreatedAt(), $created);
        self::assertEquals($configValue1->getUpdatedAt(), $updated);
        self::assertFalse($isNullValue);
        self::assertNull($this->manager->getSettingValue('oro_user.greeting'));
        self::assertNull($this->manager->getSettingValue('oro_test.nosetting'));
        self::assertNull($this->manager->getSettingValue('noservice.nosetting'));
    }

    /**
     * @dataProvider getInfoLoadedWithNormalizationProvider
     */
    public function testGetInfoLoadedWithNormalization(string $rawValue, string $dataType): void
    {
        $config = new Config();
        $config->getValues()->add($this->getConfigValue('oro_user', 'level', 'scalar', $rawValue));

        $key = $this->getScopedEntityName() . '_0';
        $this->cache->expects(self::once())
            ->method('get')
            ->with($key)
            ->willReturn($this->getCachedConfig($config));

        $settingPath = 'oro_user.level';
        $this->configBag->expects(self::any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [
                    $settingPath => [
                        'data_type' => $dataType,
                    ],
                ],
            ]);

        $this->manager->getInfo($settingPath);
    }

    public function getInfoLoadedWithNormalizationProvider(): array
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
    public function testFlush(): void
    {
        $scopeId = 1;

        $config = new Config();
        $this->prepareSave($config, $scopeId);

        $this->manager->set('oro_user.update', 'updated value', $scopeId);
        $key = sprintf('%s_%s', $this->getScopedEntityName(), $scopeId);

        $this->cache->expects(self::once())
            ->method('delete')
            ->with($key);
        $this->cache->expects(self::exactly(3))
            ->method('get')
            ->with($key)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });
        $this->assertNotEmpty($this->manager->getChanges($scopeId));

        $this->configBag->expects(self::any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        $this->manager->flush($scopeId);

        self::assertEquals(
            'updated value',
            $this->manager->getSettingValue('oro_user.update', false, $scopeId)
        );
        self::assertEmpty($this->manager->getChanges($scopeId));
    }

    /**
     * Test saving settings
     */
    public function testSave(): void
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

        $this->cache->expects(self::once())
            ->method('delete')
            ->with($key);
        $this->cache->expects(self::exactly(7))
            ->method('get')
            ->with($key)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->configBag->expects(self::any())
            ->method('getConfig')
            ->willReturn([
                'fields' => [],
            ]);

        $result = $this->manager->save($settings);

        self::assertEquals(
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

        self::assertEquals('updated value', $this->manager->getSettingValue('oro_user.update'));
        self::assertNull($this->manager->getSettingValue('oro_user.remove'));
        self::assertEquals('new value', $this->manager->getSettingValue('oro_user.add'));
    }

    public function testCachedDataTypes(): void
    {
        $scopeId = null;
        $settings = [
            'oro_user.integer' => ['value' => '1', 'use_parent_scope_value' => false],
            'oro_user.decimal' => ['value' => '1', 'use_parent_scope_value' => false],
            'oro_user.boolean' => ['value' => '1', 'use_parent_scope_value' => false],
        ];

        $this->configBag->expects(self::once())
            ->method('getConfig')
            ->willReturn([
                'fields' => [
                    'oro_user.integer' => ['data_type' => 'integer'],
                    'oro_user.decimal' => ['data_type' => 'decimal'],
                    'oro_user.boolean' => ['data_type' => 'boolean']
                ]
            ]);

        $key = sprintf('%s_%s', $this->getScopedEntityName(), (int) $scopeId);
        $this->cache->expects(self::once())
            ->method('delete')
            ->with($key);
        $cachedResult = [
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
        ];
        $this->cache->expects(self::exactly(4))
            ->method('get')
            ->with($key)
            ->willReturn($cachedResult);

        $this->manager->save($settings);
    }

    public function testSaveWithRemoveScopeEntity(): void
    {
        $scopeId = 0;
        $settings = [
            'oro_user.update' => [
                'use_parent_scope_value' => true
            ]
        ];

        $config = new Config();
        $config->getValues()->add($this->getConfigValue('oro_user', 'update', 'scalar', 'old value'));

        $this->repo->expects(self::any())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), $scopeId)
            ->willReturn($config);

        $this->em->expects(self::once())
            ->method('remove')
            ->with($this->identicalTo($config));
        $this->em->expects(self::once())
            ->method('flush');

        $this->configBag->expects(self::any())
            ->method('getConfig')
            ->willReturn(['fields' => []]);

        $result = $this->manager->save($settings);

        self::assertEquals([[], ['oro_user.update']], $result);
    }

    public function testSetScopeId(): void
    {
        $entityId = 123;

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ConfigManagerScopeIdUpdateEvent::class),
                ConfigManagerScopeIdUpdateEvent::EVENT_NAME
            );

        $this->manager->setScopeId($entityId);

        self::assertSame($entityId, $this->manager->getScopeId());
    }

    public function testGetScopedEntityName(): void
    {
        self::assertEquals($this->getScopedEntityName(), $this->manager->getScopedEntityName());
    }

    public function testGetScopeIdFromEntity(): void
    {
        $entity = $this->getScopedEntity();
        $entityId = $this->getEntityId($entity);

        self::assertSame($entityId, $this->manager->getScopeIdFromEntity($entity));
    }

    public function testGetScopeIdFromUnsupportedEntity(): void
    {
        self::assertNull($this->manager->getScopeIdFromEntity(new \stdClass()));
    }

    public function testSetScopeIdFromEntity(): void
    {
        $entity = $this->getScopedEntity();
        $entityId = $this->getEntityId($entity);

        $newScopeId = $entityId ?: $this->manager->getScopeId();
        $this->dispatcher->expects(self::exactly($newScopeId ? 1 : 0))
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ConfigManagerScopeIdUpdateEvent::class),
                ConfigManagerScopeIdUpdateEvent::EVENT_NAME
            );

        $this->manager->setScopeIdFromEntity($entity);

        self::assertSame($newScopeId, $this->manager->getScopeId());
    }

    public function testSetScopeIdFromUnsupportedEntity(): void
    {
        $this->manager->setScopeIdFromEntity($this->getScopedEntity());
        $oldScopeId = $this->manager->getScopeId();
        $this->dispatcher->expects(self::exactly(0))
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ConfigManagerScopeIdUpdateEvent::class),
                ConfigManagerScopeIdUpdateEvent::EVENT_NAME
            );

        $this->manager->setScopeIdFromEntity(new \stdClass());

        self::assertSame($oldScopeId, $this->manager->getScopeId());
    }
}
