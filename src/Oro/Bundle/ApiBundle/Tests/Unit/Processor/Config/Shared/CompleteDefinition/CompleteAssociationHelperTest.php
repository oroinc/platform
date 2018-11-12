<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteAssociationHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var CompleteAssociationHelper */
    private $completeAssociationHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->completeAssociationHelper = new CompleteAssociationHelper(
            $this->configProvider
        );
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
                ]
            ]
        ]);
        $fieldName = 'source';
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

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
                                'property_path' => 'sourceEntityId'
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
                'source'            => [
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
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

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
                                'property_path' => 'sourceEntityId'
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
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

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
                                'property_path' => 'sourceEntityId'
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
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

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
                                'property_path' => 'sourceEntityId'
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
}
