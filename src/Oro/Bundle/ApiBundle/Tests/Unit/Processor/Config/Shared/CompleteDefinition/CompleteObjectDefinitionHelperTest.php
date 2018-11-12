<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteObjectDefinitionHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;

class CompleteObjectDefinitionHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var CompleteObjectDefinitionHelper */
    private $completeObjectDefinitionHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->completeObjectDefinitionHelper = new CompleteObjectDefinitionHelper(
            new CompleteAssociationHelper($this->configProvider)
        );
    }

    public function testCompleteDefinitionForField()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForCompletedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithoutConfig()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn($this->createRelationConfigObject());

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class' => 'Test\Association1Target'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-many'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-many',
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithDataType()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithCompositeId()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id1', 'id2'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnly()
    {
        $config = $this->createConfigObject([
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'        => null,
                '__class__' => [
                    'meta_property' => true,
                    'data_type'     => 'string'
                ],
                'field1'    => null,
                'field2'    => [
                    'exclude' => true
                ],
                'field3'    => [
                    'property_path' => 'realField3'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => [
                        'meta_property' => true,
                        'data_type'     => 'string'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWithRenamedIdFieldInConfig()
    {
        $config = $this->createConfigObject([
            'identifier_field_names' => ['renamedId'],
            'fields'                 => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $this->completeObjectDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['renamedId'],
                'fields'                 => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $config
        );
    }
}
