<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlushConfigManagerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = DemoEntity::class;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject */
    private $modelManager;

    /** @var AuditManager|\PHPUnit\Framework\MockObject\MockObject */
    private $auditManager;

    /** @var ConfigCache|\PHPUnit\Framework\MockObject\MockObject */
    private $configCache;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $testConfigProvider;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->testConfigProvider = $this->createMock(ConfigProvider::class);

        $configProviderBag = $this->createMock(ConfigProviderBag::class);
        $configProviderBag->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(function ($scope) {
                switch ($scope) {
                    case 'entity':
                        return $this->entityConfigProvider;
                    case 'test':
                        return $this->testConfigProvider;
                    default:
                        return null;
                }
            });

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->modelManager = $this->createMock(ConfigModelManager::class);
        $this->auditManager = $this->createMock(AuditManager::class);
        $this->configCache = $this->createMock(ConfigCache::class);
        $serviceProvider = new ServiceLocator([
            'annotation_metadata_factory' => function () {
                return $this->createMock(MetadataFactory::class);
            },
            'configuration_handler' => function () {
                return ConfigurationHandlerMock::getInstance();
            },
            'event_dispatcher' => function () {
                return $this->eventDispatcher;
            },
            'audit_manager' => function () {
                return $this->auditManager;
            },
            'config_model_manager' => function () {
                return $this->modelManager;
            }
        ]);

        $this->configManager = new ConfigManager(
            $this->configCache,
            $serviceProvider
        );
        $this->configManager->setProviderBag($configProviderBag);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFlush(): void
    {
        $model = new EntityConfigModel(self::ENTITY_CLASS);
        $fieldModel = new FieldConfigModel('field1', 'string');

        $entityConfigId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $entityConfig = new Config($entityConfigId);
        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');

        $testConfigId = new EntityConfigId('test', self::ENTITY_CLASS);
        $testConfig = new Config($testConfigId);
        $testConfig->set('attr1', 'test_attr1');

        $entityFieldConfigId = new FieldConfigId('entity', 'Test\AnotherEntity', 'field1', 'string');
        $entityFieldConfig = new Config($entityFieldConfigId);
        $entityFieldConfig->set('label', 'test_field_label');

        $testFieldConfigId = new FieldConfigId('test', 'Test\AnotherEntity', 'field1', 'string');
        $testFieldConfig = new Config($testFieldConfigId);
        $testFieldConfig->set('attr1', 'test_field_attr1');

        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('getPropertyConfig')
            ->willReturn(new PropertyConfigContainer([
                'entity' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['indexed' => true]]
                    ]
                ],
                'field'  => [
                    'items' => [
                        'label' => ['options' => ['indexed' => true]]
                    ]
                ]
            ]));
        $this->testConfigProvider->expects($this->exactly(2))
            ->method('getPropertyConfig')
            ->willReturn(new PropertyConfigContainer([
                'entity' => [
                    'items' => [
                        'attr1' => []
                    ]
                ],
                'field'  => [
                    'items' => [
                        'attr1' => []
                    ]
                ]
            ]));

        $this->modelManager->expects($this->once())
            ->method('getEntityModel')
            ->with($entityConfigId->getClassName())
            ->willReturn($model);
        $this->modelManager->expects($this->once())
            ->method('getFieldModel')
            ->with($entityFieldConfigId->getClassName(), $entityFieldConfigId->getFieldName())
            ->willReturn($fieldModel);

        $em = $this->createMock(EntityManager::class);
        $this->modelManager->expects($this->atLeast(1))
            ->method('getEntityManager')
            ->willReturn($em);

        $this->setFlushExpectations($em, [$model, $fieldModel]);

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    new PreFlushConfigEvent(
                        ['entity' => $entityConfig, 'test' => $testConfig],
                        $this->configManager
                    ),
                    Events::PRE_FLUSH
                ],
                [
                    new PreFlushConfigEvent(
                        ['entity' => $entityFieldConfig, 'test' => $testFieldConfig],
                        $this->configManager
                    ),
                    Events::PRE_FLUSH
                ],
                [
                    new PostFlushConfigEvent(
                        [self::ENTITY_CLASS => $model, 'Test\AnotherEntity.field1' => $fieldModel],
                        $this->configManager
                    ),
                    Events::POST_FLUSH
                ]
            );

        $this->configCache->expects($this->once())
            ->method('deleteEntityConfig')
            ->with($entityConfigId->getClassName());
        $this->configCache->expects($this->once())
            ->method('deleteFieldConfig')
            ->with($entityFieldConfigId->getClassName(), $entityFieldConfigId->getFieldName());

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($testConfig);
        $this->configManager->persist($entityFieldConfig);
        $this->configManager->persist($testFieldConfig);
        $this->configManager->flush();

        $this->assertEquals(
            [
                'icon' => 'test_icon',
                'label' => 'test_label',
            ],
            $model->toArray('entity')
        );
        $this->assertEquals(
            [
                'attr1' => 'test_attr1'
            ],
            $model->toArray('test')
        );
        $this->assertEquals(
            [
                'label' => 'test_field_label'
            ],
            $fieldModel->toArray('entity')
        );
        $this->assertEquals(
            [
                'attr1' => 'test_field_attr1'
            ],
            $fieldModel->toArray('test')
        );

        $this->assertCount(3, $model->getIndexedValues());
        $this->assertEquals('entity_config', $model->getIndexedValues()[0]->getScope());
        $this->assertEquals('module_name', $model->getIndexedValues()[0]->getCode());
        $this->assertEquals('entity_config', $model->getIndexedValues()[1]->getScope());
        $this->assertEquals('entity_name', $model->getIndexedValues()[1]->getCode());
        $this->assertEquals('entity', $model->getIndexedValues()[2]->getScope());
        $this->assertEquals('label', $model->getIndexedValues()[2]->getCode());
    }

    public function testFlushWithPersistConfigsChanged(): void
    {
        $model = new EntityConfigModel(self::ENTITY_CLASS);

        $entityConfigId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $entityConfig = new Config($entityConfigId);

        $testConfigId = new EntityConfigId('test', self::ENTITY_CLASS);
        $testConfig = new Config($testConfigId);

        $entityPropertyConfig = new PropertyConfigContainer(
            [
                'entity' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['indexed' => true]]
                    ]
                ]
            ]
        );
        $testPropertyConfig = new PropertyConfigContainer(
            [
                'test' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['indexed' => true]]
                    ]
                ]
            ]
        );

        // First occurrence is on the first pass of prepareFlush(),
        // second occurrence - on the second pass of prepareFlush()
        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('getPropertyConfig')
            ->willReturnOnConsecutiveCalls($entityPropertyConfig, $entityPropertyConfig);

        // Called on the second pass of prepareFlush(), after persistConfigs were updated during the first pass
        $this->testConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturnOnConsecutiveCalls($testPropertyConfig);

        $this->modelManager->expects($this->once())
            ->method('getEntityModel')
            ->with($entityConfigId->getClassName())
            ->willReturn($model);

        $em = $this->createMock(EntityManager::class);
        $this->modelManager->expects($this->atLeast(1))
            ->method('getEntityManager')
            ->willReturn($em);

        $this->eventDispatcher->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    new PreFlushConfigEvent(
                        ['entity' => $entityConfig],
                        $this->configManager
                    ),
                    Events::PRE_FLUSH
                ],
                [
                    new PreFlushConfigEvent(
                        ['entity' => $entityConfig, 'test' => $testConfig],
                        $this->configManager
                    ),
                    Events::PRE_FLUSH
                ],
                [
                    new PostFlushConfigEvent(
                        [self::ENTITY_CLASS => $model],
                        $this->configManager
                    ),
                    Events::POST_FLUSH
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (PreFlushConfigEvent $event) use ($testConfig) {
                    $configManager = $event->getConfigManager();
                    $configManager->persist($testConfig);
                    $configManager->calculateConfigChangeSet($testConfig);

                    return $event;
                }),
                new ReturnCallback(function (PreFlushConfigEvent $event) {
                    return $event;
                }),
                new ReturnCallback(function (PostFlushConfigEvent $event) {
                    return $event;
                })
            );

        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    /**
     * @param EntityManager|\PHPUnit\Framework\MockObject\MockObject $em
     * @param ConfigModel[]                                          $models
     */
    private function setFlushExpectations(
        EntityManager|\PHPUnit\Framework\MockObject\MockObject $em,
        array $models
    ): void {
        $this->configCache->expects($this->once())
            ->method('deleteAllConfigurable');
        $this->auditManager->expects($this->once())
            ->method('buildEntity')
            ->with($this->identicalTo($this->configManager))
            ->willReturn(null);

        $em->expects($this->exactly(count($models)))
            ->method('persist')
            ->willReturnCallback(function ($obj) use ($models) {
                foreach ($models as $model) {
                    if ($model === $obj) {
                        return;
                    }
                }
                $this->fail(sprintf(
                    'Expected that $em->persist(%s[%s]) is called.',
                    get_class($obj),
                    $obj instanceof FieldConfigModel ? $obj->getFieldName() : $obj->getClassName()
                ));
            });
        $em->expects($this->once())
            ->method('flush');
    }
}
