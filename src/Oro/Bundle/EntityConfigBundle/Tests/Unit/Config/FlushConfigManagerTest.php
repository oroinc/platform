<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderBagMock;

class FlushConfigManagerTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity';

    /** @var ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $testConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $modelManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $auditManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configCache;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider->expects($this->atLeast(1))
            ->method('getScope')
            ->will($this->returnValue('entity'));

        $this->testConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->testConfigProvider->expects($this->atLeast(1))
            ->method('getScope')
            ->will($this->returnValue('test'));

        $this->eventDispatcher    = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataFactory    = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->auditManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Audit\AuditManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configCache        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = new ConfigManager(
            $this->eventDispatcher,
            $this->metadataFactory,
            $this->modelManager,
            $this->auditManager,
            $this->configCache
        );

        $configProviderBag = new ConfigProviderBagMock();
        $configProviderBag->addProvider($this->entityConfigProvider);
        $configProviderBag->addProvider($this->testConfigProvider);
        $this->configManager->setProviderBag($configProviderBag);
    }

    public function testFlush()
    {
        $model = new EntityConfigModel(self::ENTITY_CLASS);

        $entityConfigId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');
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
        $this->entityConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($entityPropertyConfig));

        $testConfigId = new EntityConfigId('test', self::ENTITY_CLASS);
        $testConfig   = new Config($testConfigId);
        $testConfig->set('attr1', 'test_attr1');
        $testPropertyConfig = new PropertyConfigContainer(
            [
                'entity' => [
                    'items' => [
                        'attr1' => []
                    ]
                ]
            ]
        );
        $this->testConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($testPropertyConfig));

        $this->modelManager->expects($this->once())
            ->method('getEntityModel')
            ->with($entityConfigId->getClassName())
            ->will($this->returnValue($model));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelManager->expects($this->atLeast(1))
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->setFlushExpectations($em, [$model]);

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                Events::PRE_FLUSH,
                new PreFlushConfigEvent(['entity' => $entityConfig, 'test' => $testConfig], $this->configManager)
            );

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($testConfig);
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

        $this->assertCount(3, $model->getIndexedValues());
        $this->assertEquals('entity_config', $model->getIndexedValues()[0]->getScope());
        $this->assertEquals('module_name', $model->getIndexedValues()[0]->getCode());
        $this->assertEquals('entity_config', $model->getIndexedValues()[1]->getScope());
        $this->assertEquals('entity_name', $model->getIndexedValues()[1]->getCode());
        $this->assertEquals('entity', $model->getIndexedValues()[2]->getScope());
        $this->assertEquals('label', $model->getIndexedValues()[2]->getCode());
    }

    public function testFlushWithPersistConfigsChanged()
    {
        $model = new EntityConfigModel(self::ENTITY_CLASS);

        $entityConfigId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $entityConfig   = new Config($entityConfigId);

        $testEntityConfigId = new EntityConfigId('test', self::ENTITY_CLASS);
        $testEntityConfig   = new Config($testEntityConfigId);


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

        $configs = [
            'entity' => $entityConfig,
        ];

        $this->eventDispatcher->expects(self::at(0))
            ->method('dispatch')
            ->with(Events::PRE_FLUSH, new PreFlushConfigEvent($configs, $this->configManager))
            ->willReturnCallback(function (string $eventName, PreFlushConfigEvent $event) use ($testEntityConfig) {
                $configManager = $event->getConfigManager();
                $configManager->persist($testEntityConfig);
                $configManager->calculateConfigChangeSet($testEntityConfig);
            });

        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $em
     * @param ConfigModel[]                            $models
     */
    protected function setFlushExpectations($em, $models)
    {
        $this->configCache->expects($this->once())
            ->method('deleteAllConfigurable');
        $this->auditManager->expects($this->once())
            ->method('buildEntity')
            ->with($this->identicalTo($this->configManager))
            ->willReturn(null);

        $em->expects($this->exactly(count($models)))
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($obj) use (&$models) {
                        foreach ($models as $model) {
                            if ($model == $obj) {
                                return;
                            }
                        }
                        $this->fail(
                            sprintf(
                                'Expected that $em->persist(%s[%s]) is called.',
                                get_class($obj),
                                $obj instanceof FieldConfigModel ? $obj->getFieldName() : $obj->getClassName()
                            )
                        );
                    }
                )
            );
        $em->expects($this->once())
            ->method('flush');
    }
}
