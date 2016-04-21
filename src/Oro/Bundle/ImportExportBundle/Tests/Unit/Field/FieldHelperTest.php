<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\ImportEntity;
use Oro\Bundle\WorkflowBundle\Field\FieldProvider;

class FieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $config = [
        'TestEntity' => [
            'testField' => [
                'testParameter' => 1,
            ],
        ],
        'TestEntityScalar' => [
            'ScalarField' => [
                'process_as_scalar' => true,
            ],
        ],
    ];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var FieldHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->fieldProvider = $this->prepareFieldProvider();
        $this->configProvider = $this->prepareConfigProvider();

        $this->helper = new FieldHelper($this->fieldProvider, $this->configProvider, new FieldTypeHelper([]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FieldProvider
     */
    protected function prepareFieldProvider()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected function prepareConfigProvider()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->any())->method('hasConfig')
            ->with($this->isType('string'), $this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        return isset($this->config[$entityName][$fieldName]);
                    }
                )
            );
        $configProvider->expects($this->any())->method('getConfig')
            ->with($this->isType('string'), $this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
                        $entityConfig->expects($this->any())->method('has')->with($this->isType('string'))
                            ->will(
                                $this->returnCallback(
                                    function ($parameter) use ($entityName, $fieldName) {
                                        return isset($this->config[$entityName][$fieldName][$parameter]);
                                    }
                                )
                            );
                        $entityConfig->expects($this->any())->method('get')->with($this->isType('string'))
                            ->will(
                                $this->returnCallback(
                                    function ($parameter, $isStrict, $default) use ($entityName, $fieldName) {
                                        return isset($this->config[$entityName][$fieldName][$parameter])
                                            ? $this->config[$entityName][$fieldName][$parameter]
                                            : $default;
                                    }
                                )
                            );

                        return $entityConfig;
                    }
                )
            );

        return $configProvider;
    }

    public function testGetFields()
    {
        $entityName = 'TestEntity';
        $withRelations = true;
        $expectedFields = [['name' => 'field']];

        $this->fieldProvider->expects($this->once())->method('getFields')->with($entityName, $withRelations)
            ->will($this->returnValue($expectedFields));

        $this->assertEquals($expectedFields, $this->helper->getFields($entityName, $withRelations));

        // do not call twice
        $this->assertEquals($expectedFields, $this->helper->getFields($entityName, $withRelations));
    }

    /**
     * @param mixed $expected
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @param mixed|null $default
     * @param bool $hasConfig
     * @dataProvider getConfigValueDataProvider
     */
    public function testGetConfigValue($expected, $entityName, $fieldName, $parameter, $default, $hasConfig = true)
    {
        if (!is_null($expected)) {
            $this->assertTrue($this->helper->hasConfig($entityName, $fieldName));
        }

        $value = $this->helper->getConfigValue($entityName, $fieldName, $parameter, $default);
        $this->assertSame($expected, $value);
        $this->assertSame($value, $this->helper->getConfigValue($entityName, $fieldName, $parameter, $default));

        // has config from caches
        $this->assertEquals($hasConfig, $this->helper->hasConfig($entityName, $fieldName));
    }

    /**
     * @return array
     */
    public function getConfigValueDataProvider()
    {
        return [
            'unknown entity or field' => [
                'expected' => null,
                'entityName' => 'UnknownEntity',
                'fieldName' => 'unknownField',
                'parameter' => 'someParameter',
                'default' => null,
                'hasConfig' => false
            ],
            'no parameter with default' => [
                'expected' => false,
                'entityName' => 'TestEntity',
                'fieldName' => 'testField',
                'parameter' => 'unknownParameter',
                'default' => false,
            ],
            'existing parameter' => [
                'expected' => 1,
                'entityName' => 'TestEntity',
                'fieldName' => 'testField',
                'parameter' => 'testParameter',
                'default' => null,
            ],
        ];
    }

    /**
     * @param boolean $expected
     * @param array $field
     * @dataProvider relationDataProvider
     */
    public function testIsRelation($expected, array $field)
    {
        $this->assertSame($expected, $this->helper->isRelation($field));
    }

    /**
     * @return array
     */
    public function relationDataProvider()
    {
        return [
            'no relation type' => [
                'expected' => false,
                'field' => [
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'no related entity name' => [
                'expected' => false,
                'field' => [
                    'relation_type' => 'ref-one',
                ],
            ],
            'valid relation' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ],
            ]
        ];
    }

    /**
     * @param boolean $expected
     * @param array $field
     * @dataProvider singleRelationDataProvider
     */
    public function testIsSingleRelation($expected, array $field)
    {
        $this->assertSame($expected, $this->helper->isSingleRelation($field));
    }

    /**
     * @return array
     */
    public function singleRelationDataProvider()
    {
        return [
            'single relation ref-one' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'single relation manyToOne' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'manyToOne',
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'multiple relation' => [
                'expected' => false,
                'field' => [
                    'relation_type' => 'ref-many',
                    'related_entity_name' => 'TestEntity',
                ],
            ]
        ];
    }

    /**
     * @param boolean $expected
     * @param array $field
     * @dataProvider multipleRelationDataProvider
     */
    public function testIsMultipleRelation($expected, array $field)
    {
        $this->assertSame($expected, $this->helper->isMultipleRelation($field));
    }

    /**
     * @return array
     */
    public function multipleRelationDataProvider()
    {
        return [
            'multiple relation ref-many' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'ref-many',
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'multiple relation oneToMany' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'multiple relation manyToMany' => [
                'expected' => true,
                'field' => [
                    'relation_type' => 'manyToMany',
                    'related_entity_name' => 'TestEntity',
                ],
            ],
            'single relation' => [
                'expected' => false,
                'field' => [
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ],
            ]
        ];
    }

    /**
     * @param bool $expected
     * @param array $field
     * @dataProvider dateTimeDataProvider
     */
    public function testIsDateTimeField($expected, array $field)
    {
        $this->assertSame($expected, $this->helper->isDateTimeField($field));
    }

    /**
     * @return array
     */
    public function dateTimeDataProvider()
    {
        return [
            'date' => [
                'expected' => true,
                'field' => ['type' => 'date'],
            ],
            'time' => [
                'expected' => true,
                'field' => ['type' => 'time'],
            ],
            'datetime' => [
                'expected' => true,
                'field' => ['type' => 'datetime'],
            ],
            'string' => [
                'expected' => false,
                'field' => ['type' => 'string'],
            ],
        ];
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @param array  $exception
     *
     * @dataProvider objectValueProvider
     */
    public function testSetObjectValue($object, $fieldName, $value, array $exception)
    {
        if ($exception) {
            list($class, $message) = $exception;
            $this->setExpectedException($class, $message);
        }

        $this->helper->setObjectValue($object, $fieldName, $value);

        $this->assertSame($value, $this->helper->getObjectValue($object, $fieldName));
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @param array  $exception
     *
     * @dataProvider objectValueProvider
     */
    public function testGetObjectValue($object, $fieldName, $value, array $exception)
    {
        if ($exception) {
            list($class, $message) = $exception;
            $this->setExpectedException($class, $message);
        }

        $this->assertEquals(null, $this->helper->getObjectValue($object, $fieldName));
        $this->helper->setObjectValue($object, $fieldName, $value);
        $this->assertEquals($value, $this->helper->getObjectValue($object, $fieldName));
    }

    /**
     * @return array
     */
    public function objectValueProvider()
    {
        $object = new ImportEntity();

        return [
            'not_exists' => [
                'object'    => $object,
                'fieldName' => 'not_exists',
                'value'     => 'test',
                'exception' => [
                    '\Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException',
                    'Neither the property "not_exists" nor one of the methods '
                ]
            ],
            'protected'  => [
                'object'    => $object,
                'fieldName' => 'twitter',
                'value'     => 'username',
                'exception' => []
            ],
            'private'    => [
                'object'    => $object,
                'fieldName' => 'private',
                'value'     => 'should_be_set',
                'exception' => []
            ],
            'private of the parent' => [
                'object'    => $object,
                'fieldName' => 'basePrivate',
                'value'     => 'val',
                'exception' => [],
            ]
        ];
    }

    /**
     * @param mixed $data
     * @param string $fieldName
     * @param mixed $expected
     * @dataProvider getItemDataDataProvider
     */
    public function testGetItemData($data, $fieldName, $expected)
    {
        $this->assertSame($expected, $this->helper->getItemData($data, $fieldName));
    }

    public function getItemDataDataProvider()
    {
        return [
            'not an array' => [
                'data' => new \stdClass(),
                'fieldName' => 'field',
                'expected' => [],
            ],
            'null field' => [
                'data' => ['field' => 'value'],
                'fieldName' => null,
                'expected' => ['field' => 'value'],
            ],
            'existing field' => [
                'data' => ['field' => ['value']],
                'fieldName' => 'field',
                'expected' => ['value'],
            ],
            'not existing field' => [
                'data' => [],
                'fieldName' => 'field',
                'expected' => [],
            ],
        ];
    }

    public function testGetIdentityValues()
    {
        $this->config['stdClass'] = [
            'excludedField' => ['excluded' => true],
            'identityField' => ['identity' => true],
            'onlyWhenNotEmptyIdentityField' => ['identity' => true],
            'regularField'  => [],
        ];

        $fields = [
            ['name' => 'excludedField'],
            ['name' => 'identityField'],
            ['name' => 'onlyWhenNotEmptyIdentityField'],
            ['name' => 'regularField'],
        ];

        $entity = new \stdClass();
        $entity->excludedField = 'excludedValue';
        $entity->identityField = 'identityValue';
        $entity->onlyWhenNotEmptyIdentityField = 'onlyWhenNotEmptyIdentityValue';
        $entity->regularField  = 'regularValue';

        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with(get_class($entity), true)
            ->will($this->returnValue($fields));

        $value = $this->helper->getIdentityValues($entity);
        $this->assertEquals(
            [
                'identityField' => 'identityValue',
                'onlyWhenNotEmptyIdentityField' => 'onlyWhenNotEmptyIdentityValue'
            ],
            $value
        );
        $this->assertSame($value, $this->helper->getIdentityValues($entity));
    }

    /**
     * @dataProvider isRequiredIdentityFieldProvider
     */
    public function testIsRequiredIdentityField($identityValue, $expectedResult)
    {
        $this->config['stdClass'] = [
            'testField' => ['identity' => $identityValue]
        ];

        $this->assertEquals(
            $expectedResult,
            $this->helper->isRequiredIdentityField('stdClass', 'testField')
        );
    }

    public function isRequiredIdentityFieldProvider()
    {
        return [
            [false, false],
            [true, true],
            [FieldHelper::IDENTITY_ONLY_WHEN_NOT_EMPTY, false],
        ];
    }

    public function testProcessAsScalar()
    {
        $this->assertFalse($this->helper->processRelationAsScalar('TestEntity', 'testField'));
        $this->assertTrue($this->helper->processRelationAsScalar('TestEntityScalar', 'ScalarField'));
    }

    public function testGetRelations()
    {
        $entityName = 'TestEntity';
        $expectedRelations = [['name' => 'field']];

        $this->fieldProvider->expects($this->once())->method('getRelations')->with($entityName)
            ->will($this->returnValue($expectedRelations));

        $this->assertEquals($expectedRelations, $this->helper->getRelations($entityName));

        // do not call twice
        $this->assertEquals($expectedRelations, $this->helper->getRelations($entityName));
    }
}
