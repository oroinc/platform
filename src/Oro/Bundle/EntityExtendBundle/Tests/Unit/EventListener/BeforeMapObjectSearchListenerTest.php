<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\BeforeMapObjectSearchListener;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class BeforeMapObjectSearchListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BeforeMapObjectSearchListener */
    private $listener;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    private array $expectedConfig = [
        'Oro\TestBundle\Entity\Test'   => [
            'fields'       => [
                [
                    'name'          => 'name',
                    'target_type'   => 'text',
                    'target_fields' => ['name']
                ],
                [
                    'name'          => 'first',
                    'target_type'   => 'integer',
                    'target_fields' => ['first']
                ],
                [
                    'name'          => 'second',
                    'target_type'   => 'text',
                    'target_fields' => ['second']
                ]
            ]
        ],
        'Oro\TestBundle\Entity\Custom' => [
            'alias'           => 'testTable',
            'label'           => 'custom',
            'route'           => [
                'name'       => 'oro_entity_view',
                'parameters' => [
                    'id'         => 'id',
                    'entityName' => '@Oro_TestBundle_Entity_Custom@'
                ]
            ],
            'search_template' => '@OroEntityExtend/Search/result.html.twig',
            'fields'          => [
                [
                    'name'          => 'first',
                    'target_type'   => 'decimal',
                    'target_fields' => ['first']
                ],
                [
                    'name'          => 'string',
                    'target_type'   => 'text',
                    'target_fields' => ['string']
                ]
            ],
            'mode'            => 'normal'
        ]
    ];

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new BeforeMapObjectSearchListener($this->configManager);
    }

    public function testPrepareEntityMapEvent()
    {
        $mappingConfig = [
            'Oro\TestBundle\Entity\Test' => [
                'fields' => [['name' => 'name', 'target_type' => 'text', 'target_fields' => ['name']]]
            ]
        ];
        $testEntityConfigId = new EntityConfigId('extend', 'Oro\TestBundle\Entity\Test');
        $testEntityConfig = new Config($testEntityConfigId);
        $testEntityConfig->set('is_extend', true);
        $testEntityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $testEntityConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $testEntityConfig->set('label', 'test');
        $testEntityConfig->set('schema', ['doctrine' => ['Oro\TestBundle\Entity\Test' => ['table' => 'testTable']]]);
        $testEntityFirstField = new FieldConfigId('search', 'Oro\TestBundle\Entity\Test', 'first', 'integer');
        $testEntityFirstFieldConfig = new Config($testEntityFirstField);
        $testEntityFirstFieldConfig->set('searchable', true);
        $testEntitySecondField  = new FieldConfigId('search', 'Oro\TestBundle\Entity\Test', 'second', 'string');
        $testEntitySecondConfig = new Config($testEntitySecondField);
        $testEntitySecondConfig->set('searchable', true);
        $testEntitySearchConfigs = [$testEntityFirstFieldConfig, $testEntitySecondConfig];

        $customEntityConfigId = new EntityConfigId('extend', 'Oro\TestBundle\Entity\Custom');
        $customEntityConfig = new Config($customEntityConfigId);
        $customEntityConfig->set('is_extend', true);
        $customEntityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $customEntityConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $customEntityConfig->set('label', 'custom');
        $customEntityConfig->set('schema', [
            'doctrine' => ['Oro\TestBundle\Entity\Custom' => ['table' => 'testTable']]
        ]);
        $customEntityFirstField = new FieldConfigId('search', 'Oro\TestBundle\Entity\Custom', 'first', 'percent');
        $customEntityFirstFieldConfig = new Config($customEntityFirstField);
        $customEntityFirstFieldConfig->set('searchable', true);
        $customEntitySecondField = new FieldConfigId('search', 'Oro\TestBundle\Entity\Custom', 'string', 'string');
        $customEntitySecondConfig = new Config($customEntitySecondField);
        $customEntitySecondConfig->set('searchable', true);
        $customEntitySearchConfigs = [$customEntityFirstFieldConfig, $customEntitySecondConfig];
        $extendConfigs = [$testEntityConfig, $customEntityConfig];
        $searchProvider = $this->createMock(ConfigProvider::class);
        $searchProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($customEntityFirstFieldConfig);
        $extendProvider = $this->createMock(ConfigProvider::class);
        $extendProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($extendConfigs);
        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->willReturnCallback(
                function ($configScope, $className) use ($testEntitySearchConfigs, $customEntitySearchConfigs) {
                    if ($className === 'Oro\TestBundle\Entity\Test') {
                        return $testEntitySearchConfigs;
                    }

                    return $customEntitySearchConfigs;
                }
            );
        $entityProvider = $this->createMock(ConfigProvider::class);
        $entityProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function ($className) use ($testEntityConfig, $customEntityConfig) {
                if ($className === 'Oro\TestBundle\Entity\Test') {
                    return $testEntityConfig;
                }

                return $customEntityConfig;
            });
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(
                function ($configScope) use ($extendProvider, $searchProvider, $entityProvider) {
                    if ($configScope === 'extend') {
                        return $extendProvider;
                    }
                    if ($configScope === 'search') {
                        return $searchProvider;
                    }
                    return $entityProvider;
                }
            );
        $event = new SearchMappingCollectEvent($mappingConfig);
        $this->listener->prepareEntityMapEvent($event);
        $this->assertEquals($this->expectedConfig, $event->getMappingConfig());
    }
}
