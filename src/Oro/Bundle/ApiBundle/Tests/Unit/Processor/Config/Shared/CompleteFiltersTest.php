<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CompleteFiltersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var CompleteFilters */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new CompleteFilters($this->doctrineHelper, ['string', 'datetime'], ['string']);
    }

    public function testProcessForAlreadyCompletedFilters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => null,
                'field4' => null
            ]
        ];

        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $filters = [];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testIndexedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testIndexedFieldWithConfiguredDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type' => 'string'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterWithoutDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'field1')
            ->willReturn('integer');
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterWhenArrayIsNotAllowedForItsDataType()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type' => 'datetime'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $rootEntityMetadata->expects(self::never())
            ->method('hasField')
            ->with('field1');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'datetime',
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterWhenArrayIsNotAllowedForItsDataTypeButWithConfiguredArrayAllowed()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type'   => 'datetime',
                    'allow_array' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $rootEntityMetadata->expects(self::never())
            ->method('hasField')
            ->with('field1');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'datetime',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterWhenRangeIsNotAllowedForItsDataTypeButWithConfiguredRangeAllowed()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type'   => 'string',
                    'allow_range' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $rootEntityMetadata->expects(self::never())
            ->method('hasField')
            ->with('field1');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'string',
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterWithoutDataTypeButDataTypeExistsInFieldConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'integer'
                ]
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterForNotManageableField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'field1')
            ->willReturn(null);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredFilterForRenamedNotManageableField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedField1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];
        $filters = [
            'fields' => [
                'renamedField1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'field1')
            ->willReturn(null);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedField1' => [
                        'property_path' => 'field1'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredCustomFilterForToManyAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'association1' => [
                    'type' => 'customFilter'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'type'        => 'customFilter',
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testConfiguredCustomFilterWithAllAttributesForToManyAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'association1' => [
                    'data_type'   => 'string',
                    'allow_array' => false,
                    'allow_range' => false,
                    'type'        => 'customFilter',
                    'options'     => [
                        'key' => 'value'
                    ]
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'string',
                        'type'      => 'customFilter',
                        'options'   => [
                            'key' => 'value'
                        ]
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWhenArrayIsNotAllowedForItsDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'datetime']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'datetime',
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWhenRangeIsNotAllowedForItsDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'string']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWithConfiguredDataTypeWhenArrayIsNotAllowedForItsDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type' => 'datetime'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'string']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'datetime',
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWithConfiguredDataTypeWhenRangeIsNotAllowedForItsDataType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'data_type' => 'string'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWhenArrayIsNotAllowedForItsDataTypeButWithConfiguredArrayAllowed()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'allow_array' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'datetime']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'datetime',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testFilterWhenRangeIsNotAllowedForItsDataTypeButWithConfiguredRangeAllowed()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => [
                    'allow_range' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'string']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'string',
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testExcludedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'exclude' => true
                ]
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'exclude'     => true,
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testNotIndexedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testNotIndexedFieldThatHasConfiguredFilter()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedIndexedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedField1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedField1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedNotIndexedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedField1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testToOneAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association1')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedToOneAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedAssociation1' => [
                    'property_path' => 'association1'
                ]
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association1')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedAssociation1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testToManyAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association1')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type'   => 'integer',
                        'collection'  => true,
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedToManyAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedAssociation1' => [
                    'property_path' => 'association1'
                ]
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association1')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedAssociation1' => [
                        'data_type'   => 'integer',
                        'collection'  => true,
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testNestedToManyAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];
        $filters = [
            'fields' => [
                'association1' => [
                    'property_path' => 'association11.association111'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $toManyAssociationTargetEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association111')
            ->willReturn(true);
        $toManyAssociationTargetEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association111')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['association11'])
            ->willReturn($toManyAssociationTargetEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($toManyAssociationTargetEntityMetadata), 'association111')
            ->willReturn('integer');
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'property_path' => 'association11.association111',
                        'data_type'     => 'integer',
                        'collection'    => true,
                        'allow_array'   => true,
                        'allow_range'   => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testExtendedAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'association:manyToOne'
                ],
                'field2' => [
                    'data_type' => 'association:manyToOne:kind'
                ],
                'field3' => [
                    'data_type' => 'association:manyToOne'
                ],
                'field4' => [
                    'data_type' => 'association:manyToOne',
                    'exclude'   => true
                ]
            ]
        ];

        $filters = [
            'fields' => [
                'field3' => [
                    'data_type'   => 'string',
                    'type'        => 'myAssociation',
                    'allow_array' => false,
                    'options'     => [
                        'option1' => 'val1'
                    ]
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'   => 'integer',
                        'type'        => 'association',
                        'allow_array' => true,
                        'allow_range' => true,
                        'options'     => [
                            'associationOwnerClass' => self::TEST_CLASS_NAME,
                            'associationType'       => 'manyToOne',
                            'associationKind'       => null
                        ]
                    ],
                    'field2' => [
                        'data_type'   => 'integer',
                        'type'        => 'association',
                        'allow_array' => true,
                        'allow_range' => true,
                        'options'     => [
                            'associationOwnerClass' => self::TEST_CLASS_NAME,
                            'associationType'       => 'manyToOne',
                            'associationKind'       => 'kind'
                        ]
                    ],
                    'field3' => [
                        'data_type' => 'string',
                        'type'      => 'myAssociation',
                        'options'   => [
                            'option1'               => 'val1',
                            'associationOwnerClass' => self::TEST_CLASS_NAME,
                            'associationType'       => 'manyToOne',
                            'associationKind'       => null
                        ]
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn('integer');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['renamedId'],
            'fields'                 => [
                'renamedId' => [
                    'property_path' => 'id'
                ]
            ]
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn('integer');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedId' => [
                        'data_type'   => 'integer',
                        'allow_array' => true,
                        'allow_range' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testIdentifierFieldWhenFieldDataTypeIsUnknown()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testIdentifierFieldWhenFilterIsAlreadyConfigured()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $filters = [
            'fields' => [
                'id' => [
                    'data_type'   => 'string',
                    'allow_array' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::never())
            ->method('getFieldDataType');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testIdentifierFieldWhenNoFieldInConfig()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => []
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::never())
            ->method('getFieldDataType');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testEnumIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(TestEnumValue::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEnumValue::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(TestEnumValue::class)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn('string');

        $this->context->setClassName(TestEnumValue::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => [
                        'data_type'   => 'string',
                        'allow_array' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testRenamedEnumIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['renamedId'],
            'fields'                 => [
                'renamedId' => [
                    'property_path' => 'id'
                ]
            ]
        ];

        $filters = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(TestEnumValue::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEnumValue::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(TestEnumValue::class)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getFieldDataType')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn('string');

        $this->context->setClassName(TestEnumValue::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'renamedId' => [
                        'data_type'   => 'string',
                        'allow_array' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testEnumIdentifierFieldWhenFilterIsAlreadyConfigured()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $filters = [
            'fields' => [
                'id' => [
                    'data_type'   => 'string',
                    'allow_array' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(TestEnumValue::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEnumValue::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(TestEnumValue::class)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::never())
            ->method('getFieldDataType');

        $this->context->setClassName(TestEnumValue::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testEnumAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class'           => TestEnumValue::class,
                    'exclusion_policy'       => 'all',
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => null
                    ]
                ]
            ]
        ];
        $filters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['association1' => 'string']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type'   => 'string',
                        'allow_array' => true
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }
}
