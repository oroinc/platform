<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CompleteAssociationHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var CompleteAssociationHelper */
    private $completeAssociationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->completeAssociationHelper = new CompleteAssociationHelper(
            $this->configProvider
        );
    }

    /**
     * @dataProvider completeAssociationDataProvider
     */
    public function testCompleteAssociation(
        array $config,
        array $targetConfig,
        array $extras,
        array $expectedConfig,
        array $expectedExtras
    ) {
        $config = $this->createConfigObject($config);
        $targetClass = 'Test\TargetEntity';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($targetClass, $version, $requestType, $expectedExtras)
            ->willReturn($this->createRelationConfigObject($targetConfig));

        $this->completeAssociationHelper->completeAssociation(
            $config->getField('association'),
            $targetClass,
            $version,
            $requestType,
            $extras
        );

        $this->assertConfig($expectedConfig, $config);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function completeAssociationDataProvider(): array
    {
        return [
            'without config extras'                        => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'fields' => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id'],
                            'collapse'               => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'collapse = true'                              => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'collapse' => true,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id'],
                            'collapse'               => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'collapse = false'                             => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'collapse' => false,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id']
                        ]
                    ]
                ],
                'expectedExtras' => [new EntityDefinitionConfigExtra()]
            ],
            'with ExpandRelatedEntitiesConfigExtra'        => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'collapse' => false,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [
                    new ExpandRelatedEntitiesConfigExtra(['test'])
                ],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id']
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new ExpandRelatedEntitiesConfigExtra(['test']),
                    new FilterFieldsConfigExtra(['Test\TargetEntity' => ['test']]),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'with target class'                            => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'target_class' => 'Test\AssociationTargetEntity',
                            'fields'       => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\AssociationTargetEntity',
                            'identifier_field_names' => ['id'],
                            'collapse'               => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'without fields'                               => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'order_by' => ['name' => 'ASC']
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'order_by'               => ['name' => 'ASC'],
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id'],
                            'collapse'               => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'with id field names'                          => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'identifier_field_names' => ['association_id'],
                            'collapse'               => false,
                            'fields'                 => [
                                'id'           => null,
                                'association1' => [
                                    'identifier_field_names' => ['association1_id'],
                                    'collapse'               => false,
                                    'fields'                 => [
                                        'id' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id'           => [
                            'data_type' => 'string'
                        ],
                        'association1' => [
                            'identifier_field_names' => ['id'],
                            'collapse'               => false,
                            'fields'                 => [
                                'id' => [
                                    'data_type' => 'string'
                                ]
                            ]
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'identifier_field_names' => ['association_id'],
                            'fields'                 => [
                                'id'           => [
                                    'data_type' => 'string'
                                ],
                                'association1' => [
                                    'identifier_field_names' => ['association1_id'],
                                    'fields'                 => [
                                        'id' => [
                                            'data_type' => 'string'
                                        ]
                                    ]
                                ]
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity'
                        ]
                    ]
                ],
                'expectedExtras' => [new EntityDefinitionConfigExtra()]
            ],
            'with exclusion_policy=all'                    => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => false,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'                 => [
                                'id' => null
                            ],
                            'exclusion_policy'       => 'all',
                            'target_class'           => 'Test\TargetEntity',
                            'identifier_field_names' => ['id']
                        ]
                    ]
                ],
                'expectedExtras' => [new EntityDefinitionConfigExtra()]
            ],
            'with form options'                            => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'fields' => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'fields' => [
                        'name' => [
                            'form_type'    => 'form_type2',
                            'form_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'           => [
                                'id'   => null,
                                'name' => [
                                    'form_type'    => 'form_type2',
                                    'form_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                                ]
                            ],
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\TargetEntity',
                            'collapse'         => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'with form options in source config'           => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'fields' => [
                                'id'   => null,
                                'name' => [
                                    'form_type'    => 'form_type1',
                                    'form_options' => ['option1' => 'value1', 'option2' => 'value2']
                                ]
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'fields' => [
                        'name' => [
                            'form_type'    => 'form_type2',
                            'form_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'           => [
                                'id'   => null,
                                'name' => [
                                    'form_type'    => 'form_type1',
                                    'form_options' => ['option1' => 'value1', 'option2' => 'value2']
                                ]
                            ],
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\TargetEntity',
                            'collapse'         => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'with post processor options'                  => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'fields' => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'fields' => [
                        'name' => [
                            'post_processor'         => 'post_processor2',
                            'post_processor_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'           => [
                                'id'   => null,
                                'name' => [
                                    'post_processor'         => 'post_processor2',
                                    'post_processor_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                                ]
                            ],
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\TargetEntity',
                            'collapse'         => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ],
            'with post processor options in source config' => [
                'config'         => [
                    'fields' => [
                        'association' => [
                            'fields' => [
                                'id'   => null,
                                'name' => [
                                    'post_processor'         => 'post_processor1',
                                    'post_processor_options' => ['option1' => 'value1', 'option2' => 'value2']
                                ]
                            ]
                        ]
                    ]
                ],
                'targetConfig'   => [
                    'fields' => [
                        'name' => [
                            'post_processor'         => 'post_processor2',
                            'post_processor_options' => ['option1' => 'value1_new', 'option3' => 'value3_new']
                        ]
                    ]
                ],
                'extras'         => [],
                'expectedConfig' => [
                    'fields' => [
                        'association' => [
                            'fields'           => [
                                'id'   => null,
                                'name' => [
                                    'post_processor'         => 'post_processor1',
                                    'post_processor_options' => ['option1' => 'value1', 'option2' => 'value2']
                                ]
                            ],
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\TargetEntity',
                            'collapse'         => true
                        ]
                    ]
                ],
                'expectedExtras' => [
                    new FilterIdentifierFieldsConfigExtra(),
                    new EntityDefinitionConfigExtra()
                ]
            ]
        ];
    }

    public function testCompleteNestedObject()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'data_class' => 'Test\Target'
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'data_class'    => 'Test\Target',
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedObjectWithInheritData()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'inherit_data' => true
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedObjectWithInheritDataAndNotMapped()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'inherit_data' => true,
                        'mapped'       => false
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true,
                            'mapped'       => false
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedObjectForRenamedField()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'data_class'    => 'Test\Target',
                        'property_path' => 'otherField'
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'data_class'    => 'Test\Target',
                            'property_path' => 'otherField'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedObjectWithoutFormOptions()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type' => 'nestedObject',
                    'fields'    => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedObjectWhenDependsOnIsPartiallySet()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'  => 'nestedObject',
                    'depends_on' => ['field3'],
                    'fields'     => [
                        'field11' => [
                            'property_path' => 'field2'
                        ],
                        'field12' => [
                            'property_path' => 'field3'
                        ]
                    ]
                ]
            ]
        ]);
        $fieldName = 'field1';

        $this->completeAssociationHelper->completeNestedObject(
            $fieldName,
            $config->getField($fieldName)
        );

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ],
                            'field12' => [
                                'property_path' => 'field3'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field3', 'field2']
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'source' => [
                    'data_type' => 'nestedAssociation',
                    'fields'    => [
                        '__class__' => [
                            'property_path' => 'sourceEntityClass'
                        ],
                        'id'        => [
                            'property_path' => 'sourceEntityId'
                        ]
                    ]
                ]
            ]
        ]);
        $fieldName = 'source';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ]));

        $this->completeAssociationHelper->completeNestedAssociation(
            $config,
            $config->getField($fieldName),
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'source'            => [
                        'data_type'              => 'nestedAssociation',
                        'property_path'          => '_',
                        'depends_on'             => ['sourceEntityClass', 'sourceEntityId'],
                        'fields'                 => [
                            '__class__' => [
                                'property_path' => 'sourceEntityClass'
                            ],
                            'id'        => [
                                'property_path' => 'sourceEntityId',
                                'data_type'     => 'string'
                            ]
                        ],
                        'exclusion_policy'       => 'all',
                        'target_class'           => EntityIdentifier::class,
                        'identifier_field_names' => ['id'],
                        'collapse'               => true
                    ],
                    'sourceEntityClass' => [
                        'exclude' => true
                    ],
                    'sourceEntityId'    => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedAssociationWhenDependsOnIsPartiallySet()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'source' => [
                    'data_type'  => 'nestedAssociation',
                    'depends_on' => ['otherField'],
                    'fields'     => [
                        '__class__' => [
                            'property_path' => 'sourceEntityClass'
                        ],
                        'id'        => [
                            'property_path' => 'sourceEntityId'
                        ]
                    ]
                ]
            ]
        ]);
        $fieldName = 'source';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ]));

        $this->completeAssociationHelper->completeNestedAssociation(
            $config,
            $config->getField($fieldName),
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'source'            => [
                        'data_type'              => 'nestedAssociation',
                        'property_path'          => '_',
                        'depends_on'             => ['otherField', 'sourceEntityClass', 'sourceEntityId'],
                        'fields'                 => [
                            '__class__' => [
                                'property_path' => 'sourceEntityClass'
                            ],
                            'id'        => [
                                'property_path' => 'sourceEntityId',
                                'data_type'     => 'string'
                            ]
                        ],
                        'exclusion_policy'       => 'all',
                        'target_class'           => EntityIdentifier::class,
                        'identifier_field_names' => ['id'],
                        'collapse'               => true
                    ],
                    'sourceEntityClass' => [
                        'exclude' => true
                    ],
                    'sourceEntityId'    => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedAssociationShouldExcludeSourceFieldsEvenIfTheyAreMarkedAsNotExcluded()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'source'            => [
                    'data_type' => 'nestedAssociation',
                    'fields'    => [
                        '__class__' => [
                            'property_path' => 'sourceEntityClass'
                        ],
                        'id'        => [
                            'property_path' => 'sourceEntityId'
                        ]
                    ]
                ],
                'sourceEntityClass' => [
                    'exclude' => false
                ],
                'sourceEntityId'    => [
                    'exclude' => false
                ]
            ]
        ]);
        $fieldName = 'source';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ]));

        $this->completeAssociationHelper->completeNestedAssociation(
            $config,
            $config->getField($fieldName),
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'source'            => [
                        'data_type'              => 'nestedAssociation',
                        'property_path'          => '_',
                        'depends_on'             => ['sourceEntityClass', 'sourceEntityId'],
                        'fields'                 => [
                            '__class__' => [
                                'property_path' => 'sourceEntityClass'
                            ],
                            'id'        => [
                                'property_path' => 'sourceEntityId',
                                'data_type'     => 'string'
                            ]
                        ],
                        'exclusion_policy'       => 'all',
                        'target_class'           => EntityIdentifier::class,
                        'identifier_field_names' => ['id'],
                        'collapse'               => true
                    ],
                    'sourceEntityClass' => [
                        'exclude' => true
                    ],
                    'sourceEntityId'    => [
                        'exclude' => true
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteNestedAssociationShouldExcludeSourceFieldsEvenIfTheyAreRenamedAndMarkedAsNotExcluded()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'source'                   => [
                    'data_type' => 'nestedAssociation',
                    'fields'    => [
                        '__class__' => [
                            'property_path' => 'sourceEntityClass'
                        ],
                        'id'        => [
                            'property_path' => 'sourceEntityId'
                        ]
                    ]
                ],
                'renamedSourceEntityClass' => [
                    'exclude'       => false,
                    'property_path' => 'sourceEntityClass'
                ],
                'renamedSourceEntityId'    => [
                    'exclude'       => false,
                    'property_path' => 'sourceEntityId'
                ]
            ]
        ]);
        $fieldName = 'source';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ]));

        $this->completeAssociationHelper->completeNestedAssociation(
            $config,
            $config->getField($fieldName),
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'source'                   => [
                        'data_type'              => 'nestedAssociation',
                        'property_path'          => '_',
                        'depends_on'             => ['sourceEntityClass', 'sourceEntityId'],
                        'fields'                 => [
                            '__class__' => [
                                'property_path' => 'sourceEntityClass'
                            ],
                            'id'        => [
                                'property_path' => 'sourceEntityId',
                                'data_type'     => 'string'
                            ]
                        ],
                        'exclusion_policy'       => 'all',
                        'target_class'           => EntityIdentifier::class,
                        'identifier_field_names' => ['id'],
                        'collapse'               => true
                    ],
                    'renamedSourceEntityClass' => [
                        'exclude'       => true,
                        'property_path' => 'sourceEntityClass'
                    ],
                    'renamedSourceEntityId'    => [
                        'exclude'       => true,
                        'property_path' => 'sourceEntityId'
                    ]
                ]
            ],
            $config
        );
    }

    public function testGetAssociationTargetType()
    {
        self::assertEquals('to-one', $this->completeAssociationHelper->getAssociationTargetType(false));
        self::assertEquals('to-many', $this->completeAssociationHelper->getAssociationTargetType(true));
    }

    public function testLoadDefinition()
    {
        $entityClass = 'Test\Entity';
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $extras = [new TestConfigExtra('test')];
        $definition = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($definition);
        $expectedExtras = [$extras[0], new EntityDefinitionConfigExtra()];

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, $requestType, $expectedExtras)
            ->willReturn($config);

        self::assertSame(
            $definition,
            $this->completeAssociationHelper->loadDefinition(
                $entityClass,
                $version,
                $requestType,
                $extras
            )
        );
    }

    public function testLoadDefinitionWhenItDoesNotExist()
    {
        $entityClass = 'Test\Entity';
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $extras = [new TestConfigExtra('test')];
        $expectedExtras = [$extras[0], new EntityDefinitionConfigExtra()];

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, $requestType, $expectedExtras)
            ->willReturn(new Config());

        self::assertNull(
            $this->completeAssociationHelper->loadDefinition(
                $entityClass,
                $version,
                $requestType,
                $extras
            )
        );
    }
}
