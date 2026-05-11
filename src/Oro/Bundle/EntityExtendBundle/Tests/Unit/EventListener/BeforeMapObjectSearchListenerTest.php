<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\BeforeMapObjectSearchListener;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BeforeMapObjectSearchListenerTest extends TestCase
{
    private BeforeMapObjectSearchListener $listener;
    private ConfigManager&MockObject $configManager;

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

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new BeforeMapObjectSearchListener($this->configManager);
    }

    public function testPrepareEntityMapEvent(): void
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
        $testEntitySecondField = new FieldConfigId('search', 'Oro\TestBundle\Entity\Test', 'second', 'string');
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
            ->willReturnCallback(function ($configScope) use ($extendProvider, $searchProvider, $entityProvider) {
                if ($configScope === 'extend') {
                    return $extendProvider;
                }
                if ($configScope === 'search') {
                    return $searchProvider;
                }
                return $entityProvider;
            });
        $event = new SearchMappingCollectEvent($mappingConfig);
        $this->listener->prepareEntityMapEvent($event);
        $this->assertEquals($this->expectedConfig, $event->getMappingConfig());
    }

    /**
     * @dataProvider relationFieldDataProvider
     */
    public function testRelationFieldHasEmptyTargetFields(
        string $fieldType,
        string $fieldName,
        array $extendFieldData,
        string $targetFieldType,
        array $expectedField
    ): void {
        $className = TestActivity::class;
        $mappingConfig = [$className => ['fields' => []]];

        $entityConfigId = new EntityConfigId('extend', $className);
        $entityConfig = new Config($entityConfigId);
        $entityConfig->set('is_extend', true);
        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $entityConfig->set('owner', ExtendScope::OWNER_SYSTEM);

        $searchFieldId = new FieldConfigId('search', $className, $fieldName, $fieldType);
        $searchFieldConfig = new Config($searchFieldId);
        $searchFieldConfig->set('searchable', true);

        $extendFieldId = new FieldConfigId('extend', $className, $fieldName, $fieldType);
        $extendFieldConfig = new Config($extendFieldId);
        foreach ($extendFieldData as $key => $value) {
            $extendFieldConfig->set($key, $value);
        }

        $extendProvider = $this->createMock(ConfigProvider::class);
        $extendProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn([$entityConfig]);
        $extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($extendFieldConfig);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendProvider);
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('search', $className)
            ->willReturn([$searchFieldConfig]);
        $this->configManager->expects($this->any())
            ->method('getId')
            ->willReturnCallback(
                fn (string $scope, string $entity, string $field) => new FieldConfigId(
                    $scope,
                    $entity,
                    $field,
                    $targetFieldType
                )
            );

        $event = new SearchMappingCollectEvent($mappingConfig);
        $this->listener->prepareEntityMapEvent($event);

        $resultFields = $event->getMappingConfig()[$className]['fields'];
        $this->assertCount(1, $resultFields);
        $this->assertSame($expectedField, $resultFields[0]);
    }

    public static function relationFieldDataProvider(): array
    {
        return [
            'many-to-one' => [
                'fieldType'       => RelationType::MANY_TO_ONE,
                'fieldName'       => 'target',
                'extendFieldData' => [
                    'target_entity' => TestActivityTarget::class,
                    'target_field'  => 'string',
                ],
                'targetFieldType' => 'string',
                'expectedField'   => [
                    'name'            => 'target',
                    'relation_type'   => 'many-to-one',
                    'relation_fields' => [
                        [
                            'name'          => 'string',
                            'target_type'   => 'text',
                            'target_fields' => ['target_string'],
                        ],
                    ],
                    'target_fields'   => [],
                ],
            ],
            'one-to-many' => [
                'fieldType'       => RelationType::ONE_TO_MANY,
                'fieldName'       => 'targets',
                'extendFieldData' => [
                    'target_entity'   => TestActivityTarget::class,
                    'target_grid'     => ['string'],
                    'target_title'    => [],
                    'target_detailed' => [],
                ],
                'targetFieldType' => 'string',
                'expectedField'   => [
                    'name'            => 'targets',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => [
                        [
                            'name'          => 'string',
                            'target_type'   => 'text',
                            'target_fields' => ['targets_string'],
                        ],
                    ],
                    'target_fields'   => [],
                ],
            ],
            'many-to-many' => [
                'fieldType'       => RelationType::MANY_TO_MANY,
                'fieldName'       => 'relatedTargets',
                'extendFieldData' => [
                    'target_entity'   => TestActivityTarget::class,
                    'target_grid'     => ['string'],
                    'target_title'    => [],
                    'target_detailed' => [],
                ],
                'targetFieldType' => 'string',
                'expectedField'   => [
                    'name'            => 'relatedTargets',
                    'relation_type'   => 'many-to-many',
                    'relation_fields' => [
                        [
                            'name'          => 'string',
                            'target_type'   => 'text',
                            'target_fields' => ['relatedTargets_string'],
                        ],
                    ],
                    'target_fields'   => [],
                ],
            ],
        ];
    }
}
