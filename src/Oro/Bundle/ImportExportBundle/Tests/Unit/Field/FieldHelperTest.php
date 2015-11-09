<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\ImportEntity;

class FieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $config = array(
        'TestEntity' => array(
            'testField' => array(
                'testParameter' => 1,
            ),
        ),
    );

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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareFieldProvider()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
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
                                    function ($parameter) use ($entityName, $fieldName) {
                                        return isset($this->config[$entityName][$fieldName][$parameter])
                                            ? $this->config[$entityName][$fieldName][$parameter]
                                            : null;
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
        $expectedFields = array(array('name' => 'field'));

        $this->fieldProvider->expects($this->once())->method('getFields')->with($entityName, $withRelations)
            ->will($this->returnValue($expectedFields));

        $this->assertEquals($expectedFields, $this->helper->getFields($entityName, $withRelations));
    }

    /**
     * @param mixed $expected
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @param mixed|null $default
     * @dataProvider getConfigValueDataProvider
     */
    public function testGetConfigValue($expected, $entityName, $fieldName, $parameter, $default = null)
    {
        if (!is_null($expected)) {
            $this->assertTrue($this->helper->hasConfig($entityName, $fieldName));
        }

        $this->assertSame(
            $expected,
            $this->helper->getConfigValue($entityName, $fieldName, $parameter, $default)
        );
    }

    /**
     * @return array
     */
    public function getConfigValueDataProvider()
    {
        return array(
            'unknown entity or field' => array(
                'expected' => null,
                'entityName' => 'UnknownEntity',
                'fieldName' => 'unknownField',
                'parameter' => 'someParameter',
            ),
            'no parameter with default' => array(
                'expected' => false,
                'entityName' => 'TestEntity',
                'fieldName' => 'testField',
                'parameter' => 'unknownParameter',
                'default' => false,
            ),
            'existing parameter' => array(
                'expected' => 1,
                'entityName' => 'TestEntity',
                'fieldName' => 'testField',
                'parameter' => 'testParameter',
            ),
        );
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
        return array(
            'no relation type' => array(
                'expected' => false,
                'field' => array(
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'no related entity name' => array(
                'expected' => false,
                'field' => array(
                    'relation_type' => 'ref-one',
                ),
            ),
            'valid relation' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ),
            )
        );
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
        return array(
            'single relation ref-one' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'single relation manyToOne' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'manyToOne',
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'multiple relation' => array(
                'expected' => false,
                'field' => array(
                    'relation_type' => 'ref-many',
                    'related_entity_name' => 'TestEntity',
                ),
            )
        );
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
        return array(
            'multiple relation ref-many' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'ref-many',
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'multiple relation oneToMany' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'oneToMany',
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'multiple relation manyToMany' => array(
                'expected' => true,
                'field' => array(
                    'relation_type' => 'manyToMany',
                    'related_entity_name' => 'TestEntity',
                ),
            ),
            'single relation' => array(
                'expected' => false,
                'field' => array(
                    'relation_type' => 'ref-one',
                    'related_entity_name' => 'TestEntity',
                ),
            )
        );
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
        return array(
            'date' => array(
                'expected' => true,
                'field' => array('type' => 'date'),
            ),
            'time' => array(
                'expected' => true,
                'field' => array('type' => 'time'),
            ),
            'datetime' => array(
                'expected' => true,
                'field' => array('type' => 'datetime'),
            ),
            'string' => array(
                'expected' => false,
                'field' => array('type' => 'string'),
            ),
        );
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
        return array(
            'not an array' => array(
                'data' => new \stdClass(),
                'fieldName' => 'field',
                'expected' => array(),
            ),
            'null field' => array(
                'data' => array('field' => 'value'),
                'fieldName' => null,
                'expected' => array('field' => 'value'),
            ),
            'existing field' => array(
                'data' => array('field' => array('value')),
                'fieldName' => 'field',
                'expected' => array('value'),
            ),
            'not existing field' => array(
                'data' => array(),
                'fieldName' => 'field',
                'expected' => array(),
            ),
        );
    }

    public function testGetIdentityValues()
    {
        $this->config['stdClass'] = array(
            'excludedField' => array('excluded' => true),
            'identityField' => array('identity' => true),
            'regularField'  => array(),
        );

        $fields = array(
            array('name' => 'excludedField'),
            array('name' => 'identityField'),
            array('name' => 'regularField'),
        );

        $entity = new \stdClass();
        $entity->excludedField = 'excludedValue';
        $entity->identityField = 'identityValue';
        $entity->regularField  = 'regularValue';

        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with(get_class($entity), true)
            ->will($this->returnValue($fields));

        $this->assertEquals(
            array('identityField' => 'identityValue'),
            $this->helper->getIdentityValues($entity, $fields)
        );
    }
}
