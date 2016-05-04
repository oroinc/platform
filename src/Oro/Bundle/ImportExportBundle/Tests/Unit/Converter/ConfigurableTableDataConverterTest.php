<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

class ConfigurableTableDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $fields = array(
        'ScalarEntity' => array(
            array(
                'name' => 'created',
                'label' => 'Created',
            ),
            array(
                'name' => 'name',
                'label' => 'Name',
            ),
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'description',
                'label' => 'Description',
            ),
        ),
        'SingleRelationEntity' => array(
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'name',
                'label' => 'Name',
            ),
            array(
                'name' => 'fullScalar',
                'label' => 'Full Scalar',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ),
            array(
                'name' => 'shortScalar',
                'label' => 'Short Scalar',
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'ScalarEntity',
            ),
            array(
                'name' => 'innerRelation',
                'label' => 'Inner Relation',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'IdentitySingleRelationEntity',
            ),
        ),
        'IdentitySingleRelationEntity' => array(
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'name',
                'label' => 'Name',
            ),
            array(
                'name' => 'identityRelation',
                'label' => 'Identity Relation',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ),
        ),
        'DictionaryEntity' => array(
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'scalarEntity',
                'label' => 'Scalar Entity',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'ScalarEntity',
            ),
            array(
                'name' => 'dictionaryScalarEntities',
                'label' => 'Dictionary Scalar Entities',
                'relation_type' => 'ref-many',
                'related_entity_name' => 'ScalarEntity',
            ),
        ),
        'MultipleRelationEntity' => array(
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'scalarEntities',
                'label' => 'Scalar Entities',
                'relation_type' => 'ref-many',
                'related_entity_name' => 'ScalarEntity',
            ),
            array(
                'name' => 'singleRelationEntities',
                'label' => 'Single Relation Entities',
                'relation_type' => 'oneToMany',
                'related_entity_name' => 'SingleRelationEntity',
            ),
            array(
                'name' => 'dictionaryEntities',
                'label' => 'Dictionary Entities',
                'relation_type' => 'manyToMany',
                'related_entity_name' => 'DictionaryEntity',
            )
        ),
    );

    /**
     * @var array
     */
    protected $config = array(
        'ScalarEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'name' => array(
                'header' => 'Entity Name',
                'identity' => true,
                'order' => 20,
            ),
            'description' => array(
                'excluded' => true,
            ),
        ),
        'SingleRelationEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'name' => array(
                'order' => 20,
            ),
            'fullScalar' => array(
                'order' => 30,
                'full' => true,
            ),
            'shortScalar' => array(
                'order' => 40,
            ),
            'innerRelation' => array(
                'order' => 50,
            ),
        ),
        'IdentitySingleRelationEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'name' => array(
                'order' => 20,
            ),
            'identityRelation' => array(
                'order' => 30,
                'identity' => true,
            ),
        ),
        'DictionaryEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'scalarEntity' => array(
                'order' => 20,
            ),
            'dictionaryScalarEntities' => array(
                'order' => 30,
            ),
        ),
        'MultipleRelationEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'scalarEntities' => array(
                'order' => 20
            ),
            'singleRelationEntities' => array(
                'order' => 30,
                'full' => true,
            ),
            'dictionaryEntities' => array(
                'order' => 40,
                'full' => true,
            ),
        ),
    );

    /**
     * @var array
     */
    protected $relations = array(
        'DictionaryEntity' => array(
            'dictionaryScalarEntities' => 2,
        ),
        'MultipleRelationEntity' => array(
            'scalarEntities' => 3,
            'singleRelationEntities' => 1,
            'dictionaryEntities' => 2,
        )
    );

    /**
     * @var ConfigurableTableDataConverter
     */
    protected $converter;

    protected function setUp()
    {
        $fieldHelper = $this->prepareFieldHelper();
        $relationCalculator = $this->prepareRelationCalculator();
        $this->converter = new ConfigurableTableDataConverter($fieldHelper, $relationCalculator);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Entity class for data converter is not specified
     */
    public function testAssertEntityName()
    {
        $this->converter->convertToExportFormat(array());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function exportDataProvider()
    {
        return array(
            'empty scalar' => array(
                'entityName' => 'ScalarEntity',
                'input' => array(),
                'expected' => array(
                    'ID' => '',
                    'Entity Name' => '',
                    'Created' => '',
                ),
            ),
            'full scalar' => array(
                'entityName' => 'ScalarEntity',
                'input' => array(
                    'id' => 42,
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ),
                'expected' => array(
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ),
            ),
            'empty single relation' => array(
                'entityName' => 'SingleRelationEntity',
                'input' => array(),
                'expected' => array(
                    'ID' => '',
                    'Name' => '',
                    'Full Scalar ID' => '',
                    'Full Scalar Entity Name' => '',
                    'Full Scalar Created' => '',
                    'Short Scalar Entity Name' => '',
                    'Inner Relation Identity Relation Entity Name' => '',
                ),
            ),
            'full single relation' => array(
                'entityName' => 'SingleRelationEntity',
                'input' => array(
                    'id' => 1,
                    'name' => 'Relation Name',
                    'fullScalar' => array(
                        'id' => 42,
                        'name' => 'qwerty',
                        'created' => '2012-12-12 12:12:12',
                    ),
                    'shortScalar' => array('name' => 'asdfgh'),
                    'innerRelation' => array('identityRelation' => array ('name' => 'test123')),
                ),
                'expected' => array(
                    'ID' => '1',
                    'Name' => 'Relation Name',
                    'Full Scalar ID' => '42',
                    'Full Scalar Entity Name' => 'qwerty',
                    'Full Scalar Created' => '2012-12-12 12:12:12',
                    'Short Scalar Entity Name' => 'asdfgh',
                    'Inner Relation Identity Relation Entity Name' => 'test123'
                ),
            ),
            'empty multiple relation' => array(
                'entityName' => 'MultipleRelationEntity',
                'input' => array(),
                'expected' => array(
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
                ),
            ),
            'full multiple relation' => array(
                'entityName' => 'MultipleRelationEntity',
                'input' => array(
                    'id' => 12,
                    'scalarEntities' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                        array('name' => 'third')
                    ),
                    'singleRelationEntities' => array(
                        array(
                            'id' => 23,
                            'name' => 'relation',
                            'fullScalar' => array(
                                'id' => 43,
                                'name' => 'qwerty',
                                'created' => '2012-12-12 12:12:12',
                            ),
                            'shortScalar' => array('name' => 'asdfgh'),
                            'innerRelation' => array('identityRelation' => array ('name' => 'test123')),
                        )
                    ),
                    'dictionaryEntities' => array(
                        array(
                            'id' => 55,
                            'scalarEntity' => array(
                                'name' => 'dictionary_scalar_1',
                            ),
                            'dictionaryScalarEntities' => array(
                                array('name' => 'dict_1'),
                                array('name' => 'dict_2'),
                            ),
                        ),
                        array(
                            'id' => 56,
                            'scalarEntity' => array(
                                'name' => 'dictionary_scalar_2',
                            ),
                            'dictionaryScalarEntities' => array(
                                array('name' => 'dict_3'),
                                array('name' => 'dict_4'),
                            ),
                        ),
                    )
                ),
                'expected' => array(
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
                ),
            ),
        );
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider exportDataProvider
     */
    public function testExport($entityName, array $input, array $expected)
    {
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToExportFormat($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function importDataProvider()
    {
        return array(
            'empty scalar' => array(
                'entityName' => 'ScalarEntity',
                'input' => array(),
                'expected' => array(),
            ),
            'full scalar' => array(
                'entityName' => 'ScalarEntity',
                'input' => array(
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ),
                'expected' => array(
                    'id' => '42',
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ),
            ),
            'empty single relation' => array(
                'entityName' => 'SingleRelationEntity',
                'input' => array(),
                'expected' => array(),
            ),
            'full single relation' => array(
                'entityName' => 'SingleRelationEntity',
                'input' => array(
                    'ID' => '1',
                    'Name' => 'Relation Name',
                    'Full Scalar ID' => '42',
                    'Full Scalar Entity Name' => 'qwerty',
                    'Full Scalar Created' => '2012-12-12 12:12:12',
                    'Short Scalar Entity Name' => 'asdfgh',
                    'Inner Relation Identity Relation Entity Name' => 'test123',
                ),
                'expected' => array(
                    'id' => '1',
                    'name' => 'Relation Name',
                    'fullScalar' => array(
                        'id' => '42',
                        'name' => 'qwerty',
                        'created' => '2012-12-12 12:12:12',
                    ),
                    'shortScalar' => array(
                        'name' => 'asdfgh',
                    ),
                    'innerRelation' => array(
                        'identityRelation' => array (
                            'name' => 'test123'
                        )
                    ),
                ),
            ),
            'empty multiple relation' => array(
                'entityName' => 'MultipleRelationEntity',
                'input' => array(),
                'expected' => array(),
            ),
            'full multiple relation' => array(
                'entityName' => 'MultipleRelationEntity',
                'input' => array(
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
                ),
                'expected' => array(
                    'id' => '12',
                    'scalarEntities' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                        array('name' => 'third')
                    ),
                    'singleRelationEntities' => array(
                        array(
                            'id' => '23',
                            'name' => 'relation',
                            'fullScalar' => array(
                                'id' => '43',
                                'name' => 'qwerty',
                                'created' => '2012-12-12 12:12:12',
                            ),
                            'shortScalar' => array('name' => 'asdfgh'),
                        )
                    ),
                    'dictionaryEntities' => array(
                        array(
                            'id' => '55',
                            'scalarEntity' => array(
                                'name' => 'dictionary_scalar_1',
                            ),
                            'dictionaryScalarEntities' => array(
                                array('name' => 'dict_1'),
                                array('name' => 'dict_2'),
                            ),
                        ),
                        array(
                            'id' => '56',
                            'scalarEntity' => array(
                                'name' => 'dictionary_scalar_2',
                            ),
                            'dictionaryScalarEntities' => array(
                                array('name' => 'dict_3'),
                                array('name' => 'dict_4'),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider importDataProvider
     */
    public function testImport($entityName, array $input, array $expected)
    {
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToImportFormat($input));
    }

    public function testGetFieldHeaderWithRelation()
    {
        $fieldName = 'name';
        $simpleFieldValue = $this->converter->getFieldHeaderWithRelation('SingleRelationEntity', $fieldName);
        $this->assertEquals($simpleFieldValue, ucfirst($fieldName));

        $relationFieldValue = $this->converter->getFieldHeaderWithRelation('SingleRelationEntity', 'fullScalar');
        $this->assertEquals($relationFieldValue, 'Full Scalar Entity Name');

    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareFieldHelper()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldTypeHelper = new FieldTypeHelper([]);

        $fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->setConstructorArgs([$fieldProvider, $configProvider, $fieldTypeHelper])
            ->setMethods(array('getConfigValue', 'getFields', 'processRelationAsScalar'))
            ->getMock();
        $fieldHelper->expects($this->any())->method('getConfigValue')
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName, $parameter, $default = null) {
                        return isset($this->config[$entityName][$fieldName][$parameter])
                            ? $this->config[$entityName][$fieldName][$parameter]
                            : $default;
                    }
                )
            );
        $fieldHelper->expects($this->any())->method('getFields')->with($this->isType('string'), true)
            ->will(
                $this->returnCallback(
                    function ($entityName) {
                        return isset($this->fields[$entityName]) ? $this->fields[$entityName] : array();
                    }
                )
            );
        return $fieldHelper;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareRelationCalculator()
    {
        $relationCalculator = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\RelationCalculator')
            ->disableOriginalConstructor()
            ->getMock();
        $relationCalculator->expects($this->any())->method('getMaxRelatedEntities')
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        return isset($this->relations[$entityName][$fieldName])
                            ? $this->relations[$entityName][$fieldName]
                            : 0;
                    }
                )
            );

        return $relationCalculator;
    }
}
