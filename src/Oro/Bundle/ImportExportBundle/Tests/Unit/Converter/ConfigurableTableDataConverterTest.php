<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class ConfigurableTableDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $fields = [
        'ScalarEntity' => [
            [
                'name' => 'created',
                'label' => 'Created',
            ],
            [
                'name' => 'name',
                'label' => 'Name',
            ],
            [
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'name' => 'description',
                'label' => 'Description',
            ],
        ],
        'SingleRelationEntity' => [
            [
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'name' => 'name',
                'label' => 'Name',
            ],
            [
                'name' => 'fullScalar',
                'label' => 'Full Scalar',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ],
            [
                'name' => 'shortScalar',
                'label' => 'Short Scalar',
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'ScalarEntity',
            ],
            [
                'name' => 'innerRelation',
                'label' => 'Inner Relation',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'IdentitySingleRelationEntity',
            ],
        ],
        'IdentitySingleRelationEntity' => [
            [
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'name' => 'name',
                'label' => 'Name',
            ],
            [
                'name' => 'identityRelation',
                'label' => 'Identity Relation',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ],
        ],
        'DictionaryEntity' => [
            [
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'name' => 'scalarEntity',
                'label' => 'Scalar Entity',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ],
            [
                'name' => 'dictionaryScalarEntities',
                'label' => 'Dictionary Scalar Entities',
                'relation_type' => 'ref-many',
                'related_entity_name' => 'ScalarEntity',
            ],
        ],
        'MultipleRelationEntity' => [
            [
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'name' => 'scalarEntities',
                'label' => 'Scalar Entities',
                'relation_type' => 'ref-many',
                'related_entity_name' => 'ScalarEntity',
            ],
            [
                'name' => 'singleRelationEntities',
                'label' => 'Single Relation Entities',
                'relation_type' => 'oneToMany',
                'related_entity_name' => 'SingleRelationEntity',
            ],
            [
                'name' => 'dictionaryEntities',
                'label' => 'Dictionary Entities',
                'relation_type' => 'manyToMany',
                'related_entity_name' => 'DictionaryEntity',
            ]
        ],
    ];

    /**
     * @var array
     */
    protected $config = [
        'ScalarEntity' => [
            'id' => [
                'order' => 10
            ],
            'created' => [
                'header' => '',
            ],
            'name' => [
                'header' => 'Entity Name',
                'identity' => true,
                'order' => 20,
            ],
            'description' => [
                'excluded' => true,
            ],
        ],
        'SingleRelationEntity' => [
            'id' => [
                'order' => 10
            ],
            'name' => [
                'order' => 20,
            ],
            'fullScalar' => [
                'order' => 30,
                'full' => true,
            ],
            'shortScalar' => [
                'order' => 40,
            ],
            'innerRelation' => [
                'order' => 50,
            ],
        ],
        'IdentitySingleRelationEntity' => [
            'id' => [
                'order' => 10
            ],
            'name' => [
                'order' => 20,
            ],
            'identityRelation' => [
                'order' => 30,
                'identity' => true,
            ],
        ],
        'DictionaryEntity' => [
            'id' => [
                'order' => 10
            ],
            'scalarEntity' => [
                'order' => 20,
            ],
            'dictionaryScalarEntities' => [
                'order' => 30,
            ],
        ],
        'MultipleRelationEntity' => [
            'id' => [
                'order' => 10
            ],
            'scalarEntities' => [
                'order' => 20
            ],
            'singleRelationEntities' => [
                'order' => 30,
                'full' => true,
            ],
            'dictionaryEntities' => [
                'order' => 40,
                'full' => true,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $relations = [
        'DictionaryEntity' => [
            'dictionaryScalarEntities' => 2,
        ],
        'MultipleRelationEntity' => [
            'scalarEntities' => 3,
            'singleRelationEntities' => 1,
            'dictionaryEntities' => 2,
        ]
    ];

    /**
     * @var ConfigurableTableDataConverter
     */
    protected $converter;

    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldHelper;

    /**
     * @var RelationCalculator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $relationCalculator;

    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localeSettings;

    protected function setUp()
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $fieldProvider = $this->createMock(EntityFieldProvider::class);
        $fieldTypeHelper = new FieldTypeHelper([]);

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->setConstructorArgs([$fieldProvider, $configProvider, $fieldTypeHelper])
            ->setMethods(['getConfigValue', 'getFields', 'processRelationAsScalar', 'setLocale'])
            ->getMock();

        $this->relationCalculator = $this->createMock(RelationCalculator::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->converter = new ConfigurableTableDataConverter(
            $this->fieldHelper,
            $this->relationCalculator,
            $this->localeSettings
        );
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Entity class for data converter is not specified
     */
    public function testAssertEntityName()
    {
        $this->converter->convertToExportFormat([]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function exportDataProvider()
    {
        return [
            'empty scalar' => [
                'entityName' => 'ScalarEntity',
                'input' => [],
                'expected' => [
                    'ID' => '',
                    'Entity Name' => '',
                    'Created' => '',
                ],
            ],
            'full scalar' => [
                'entityName' => 'ScalarEntity',
                'input' => [
                    'id' => 42,
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ],
                'expected' => [
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ],
            ],
            'empty single relation' => [
                'entityName' => 'SingleRelationEntity',
                'input' => [],
                'expected' => [
                    'ID' => '',
                    'Name' => '',
                    'Full Scalar ID' => '',
                    'Full Scalar Entity Name' => '',
                    'Full Scalar Created' => '',
                    'Short Scalar Entity Name' => '',
                    'Inner Relation Identity Relation Entity Name' => '',
                ],
            ],
            'full single relation' => [
                'entityName' => 'SingleRelationEntity',
                'input' => [
                    'id' => 1,
                    'name' => 'Relation Name',
                    'fullScalar' => [
                        'id' => 42,
                        'name' => 'qwerty',
                        'created' => '2012-12-12 12:12:12',
                    ],
                    'shortScalar' => ['name' => 'asdfgh'],
                    'innerRelation' => ['identityRelation' => ['name' => 'test123']],
                ],
                'expected' => [
                    'ID' => '1',
                    'Name' => 'Relation Name',
                    'Full Scalar ID' => '42',
                    'Full Scalar Entity Name' => 'qwerty',
                    'Full Scalar Created' => '2012-12-12 12:12:12',
                    'Short Scalar Entity Name' => 'asdfgh',
                    'Inner Relation Identity Relation Entity Name' => 'test123'
                ],
            ],
            'empty multiple relation' => [
                'entityName' => 'MultipleRelationEntity',
                'input' => [],
                'expected' => [
                    'ID' => '',
                    'Scalar Entities 1 Entity Name' => '',
                    'Scalar Entities 2 Entity Name' => '',
                    'Scalar Entities 3 Entity Name' => '',
                    'Single Relation Entities 1 ID' => '',
                    'Single Relation Entities 1 Name' => '',
                    'Single Relation Entities 1 Full Scalar ID' => '',
                    'Single Relation Entities 1 Full Scalar Entity Name' => '',
                    'Single Relation Entities 1 Full Scalar Created' => '',
                    'Single Relation Entities 1 Short Scalar Entity Name' => '',
                    'Single Relation Entities 1 Inner Relation Identity Relation Entity Name' => '',
                    'Dictionary Entities 1 ID' => '',
                    'Dictionary Entities 1 Scalar Entity Entity Name' => '',
                    'Dictionary Entities 1 Dictionary Scalar Entities 1 Entity Name' => '',
                    'Dictionary Entities 1 Dictionary Scalar Entities 2 Entity Name' => '',
                    'Dictionary Entities 2 ID' => '',
                    'Dictionary Entities 2 Scalar Entity Entity Name' => '',
                    'Dictionary Entities 2 Dictionary Scalar Entities 1 Entity Name' => '',
                    'Dictionary Entities 2 Dictionary Scalar Entities 2 Entity Name' => '',
                ],
            ],
            'full multiple relation' => [
                'entityName' => 'MultipleRelationEntity',
                'input' => [
                    'id' => 12,
                    'scalarEntities' => [
                        ['name' => 'first'],
                        ['name' => 'second'],
                        ['name' => 'third']
                    ],
                    'singleRelationEntities' => [
                        [
                            'id' => 23,
                            'name' => 'relation',
                            'fullScalar' => [
                                'id' => 43,
                                'name' => 'qwerty',
                                'created' => '2012-12-12 12:12:12',
                            ],
                            'shortScalar' => ['name' => 'asdfgh'],
                            'innerRelation' => ['identityRelation' => ['name' => 'test123']],
                        ]
                    ],
                    'dictionaryEntities' => [
                        [
                            'id' => 55,
                            'scalarEntity' => [
                                'name' => 'dictionary_scalar_1',
                            ],
                            'dictionaryScalarEntities' => [
                                ['name' => 'dict_1'],
                                ['name' => 'dict_2'],
                            ],
                        ],
                        [
                            'id' => 56,
                            'scalarEntity' => [
                                'name' => 'dictionary_scalar_2',
                            ],
                            'dictionaryScalarEntities' => [
                                ['name' => 'dict_3'],
                                ['name' => 'dict_4'],
                            ],
                        ],
                    ]
                ],
                'expected' => [
                    'ID' => '12',
                    'Scalar Entities 1 Entity Name' => 'first',
                    'Scalar Entities 2 Entity Name' => 'second',
                    'Scalar Entities 3 Entity Name' => 'third',
                    'Single Relation Entities 1 ID' => '23',
                    'Single Relation Entities 1 Name' => 'relation',
                    'Single Relation Entities 1 Full Scalar ID' => '43',
                    'Single Relation Entities 1 Full Scalar Entity Name' => 'qwerty',
                    'Single Relation Entities 1 Full Scalar Created' => '2012-12-12 12:12:12',
                    'Single Relation Entities 1 Short Scalar Entity Name' => 'asdfgh',
                    'Single Relation Entities 1 Inner Relation Identity Relation Entity Name' => 'test123',
                    'Dictionary Entities 1 ID' => '55',
                    'Dictionary Entities 1 Scalar Entity Entity Name' => 'dictionary_scalar_1',
                    'Dictionary Entities 1 Dictionary Scalar Entities 1 Entity Name' => 'dict_1',
                    'Dictionary Entities 1 Dictionary Scalar Entities 2 Entity Name' => 'dict_2',
                    'Dictionary Entities 2 ID' => '56',
                    'Dictionary Entities 2 Scalar Entity Entity Name' => 'dictionary_scalar_2',
                    'Dictionary Entities 2 Dictionary Scalar Entities 1 Entity Name' => 'dict_3',
                    'Dictionary Entities 2 Dictionary Scalar Entities 2 Entity Name' => 'dict_4',
                ],
            ],
        ];
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider exportDataProvider
     */
    public function testExport($entityName, array $input, array $expected)
    {
        $this->prepareFieldHelper();
        $this->prepareRelationCalculator();
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToExportFormat($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function importDataProvider()
    {
        return [
            'empty scalar' => [
                'entityName' => 'ScalarEntity',
                'input' => [],
                'expected' => [],
            ],
            'full scalar' => [
                'entityName' => 'ScalarEntity',
                'input' => [
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ],
                'expected' => [
                    'id' => '42',
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ],
            ],
            'empty single relation' => [
                'entityName' => 'SingleRelationEntity',
                'input' => [],
                'expected' => [],
            ],
            'full single relation' => [
                'entityName' => 'SingleRelationEntity',
                'input' => [
                    'ID' => '1',
                    'Name' => 'Relation Name',
                    'Full Scalar ID' => '42',
                    'Full Scalar Entity Name' => 'qwerty',
                    'Full Scalar Created' => '2012-12-12 12:12:12',
                    'Short Scalar Entity Name' => 'asdfgh',
                    'Inner Relation Identity Relation Entity Name' => 'test123',
                ],
                'expected' => [
                    'id' => '1',
                    'name' => 'Relation Name',
                    'fullScalar' => [
                        'id' => '42',
                        'name' => 'qwerty',
                        'created' => '2012-12-12 12:12:12',
                    ],
                    'shortScalar' => [
                        'name' => 'asdfgh',
                    ],
                    'innerRelation' => [
                        'identityRelation' => [
                            'name' => 'test123'
                        ]
                    ],
                ],
            ],
            'empty multiple relation' => [
                'entityName' => 'MultipleRelationEntity',
                'input' => [],
                'expected' => [],
            ],
            'full multiple relation' => [
                'entityName' => 'MultipleRelationEntity',
                'input' => [
                    'ID' => '12',
                    'Scalar Entities 1 Entity Name' => 'first',
                    'Scalar Entities 2 Entity Name' => 'second',
                    'Scalar Entities 3 Entity Name' => 'third',
                    'Single Relation Entities 1 ID' => '23',
                    'Single Relation Entities 1 Name' => 'relation',
                    'Single Relation Entities 1 Full Scalar ID' => '43',
                    'Single Relation Entities 1 Full Scalar Entity Name' => 'qwerty',
                    'Single Relation Entities 1 Full Scalar Created' => '2012-12-12 12:12:12',
                    'Single Relation Entities 1 Short Scalar Entity Name' => 'asdfgh',
                    'Dictionary Entities 1 ID' => '55',
                    'Dictionary Entities 1 Scalar Entity Entity Name' => 'dictionary_scalar_1',
                    'Dictionary Entities 1 Dictionary Scalar Entities 1 Entity Name' => 'dict_1',
                    'Dictionary Entities 1 Dictionary Scalar Entities 2 Entity Name' => 'dict_2',
                    'Dictionary Entities 2 ID' => '56',
                    'Dictionary Entities 2 Scalar Entity Entity Name' => 'dictionary_scalar_2',
                    'Dictionary Entities 2 Dictionary Scalar Entities 1 Entity Name' => 'dict_3',
                    'Dictionary Entities 2 Dictionary Scalar Entities 2 Entity Name' => 'dict_4',
                ],
                'expected' => [
                    'id' => '12',
                    'scalarEntities' => [
                        ['name' => 'first'],
                        ['name' => 'second'],
                        ['name' => 'third']
                    ],
                    'singleRelationEntities' => [
                        [
                            'id' => '23',
                            'name' => 'relation',
                            'fullScalar' => [
                                'id' => '43',
                                'name' => 'qwerty',
                                'created' => '2012-12-12 12:12:12',
                            ],
                            'shortScalar' => ['name' => 'asdfgh'],
                        ]
                    ],
                    'dictionaryEntities' => [
                        [
                            'id' => '55',
                            'scalarEntity' => [
                                'name' => 'dictionary_scalar_1',
                            ],
                            'dictionaryScalarEntities' => [
                                ['name' => 'dict_1'],
                                ['name' => 'dict_2'],
                            ],
                        ],
                        [
                            'id' => '56',
                            'scalarEntity' => [
                                'name' => 'dictionary_scalar_2',
                            ],
                            'dictionaryScalarEntities' => [
                                ['name' => 'dict_3'],
                                ['name' => 'dict_4'],
                            ],
                        ],
                    ]
                ],
            ],
        ];
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider importDataProvider
     */
    public function testImport($entityName, array $input, array $expected)
    {
        $this->prepareFieldHelper();
        $this->prepareRelationCalculator();
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToImportFormat($input));
    }

    public function testGetFieldHeaderWithRelation()
    {
        $fieldName = 'name';
        $this->prepareFieldHelper();
        $this->prepareRelationCalculator();
        $simpleFieldValue = $this->converter->getFieldHeaderWithRelation('SingleRelationEntity', $fieldName);
        $this->assertEquals($simpleFieldValue, ucfirst($fieldName));

        $relationFieldValue = $this->converter->getFieldHeaderWithRelation('SingleRelationEntity', 'fullScalar');
        $this->assertEquals($relationFieldValue, 'Full Scalar Entity Name');
    }

    /**
     * @param bool $translateUsingLocale
     * @param string $locale
     * @param int $expectedCallsToGetLocale
     * @param int $translateUsingLocaleCalls
     *
     * @dataProvider getImportWithTranslatedFieldsDataProvider
     */
    public function testImportWithTranslatedFields(
        $translateUsingLocale,
        $locale,
        $expectedCallsToGetLocale,
        $translateUsingLocaleCalls
    ) {
        $this->converter->setTranslateUsingLocale($translateUsingLocale);

        $this->localeSettings->expects($this->exactly($expectedCallsToGetLocale))
            ->method('getLanguage')
            ->willReturn($locale);

        $this->fieldHelper->expects($this->exactly($translateUsingLocaleCalls))
            ->method('setLocale')
            ->with($locale);

        $this->prepareFieldHelper();
        $this->prepareRelationCalculator();

        $this->converter->setEntityName('EntityName');
        $this->converter->convertToImportFormat(['field1' => 'Field1 name']);
    }

    /**
     * @return array
     */
    public function getImportWithTranslatedFieldsDataProvider()
    {
        return [
            'fields should use locale' => [
                'translateUsingLocale' => true,
                'locale' => 'it_IT',
                'expectedCallsToGetLocale' => 1,
                'translateUsingLocaleCalls' => 1
            ],
            'fields should use default locale' => [
                'translateUsingLocale' => false,
                'locale' => null,
                'expectedCallsToGetLocale' => 0,
                'translateUsingLocaleCalls' => 0
            ]
        ];
    }

    protected function prepareFieldHelper()
    {
        $this->fieldHelper->expects($this->any())->method('getConfigValue')
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName, $parameter, $default = null) {
                        return $this->config[$entityName][$fieldName][$parameter] ?? $default;
                    }
                )
            );
        $this->fieldHelper->expects($this->any())->method('getFields')->with($this->isType('string'), true)
            ->will(
                $this->returnCallback(
                    function ($entityName) {
                        return $this->fields[$entityName] ?? [];
                    }
                )
            );
    }

    protected function prepareRelationCalculator()
    {
        $this->relationCalculator->expects($this->any())->method('getMaxRelatedEntities')
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        return $this->relations[$entityName][$fieldName] ?? 0;
                    }
                )
            );
    }
}
