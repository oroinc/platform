<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension;

class DynamicFieldsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DynamicFieldsExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FieldTypeHelper
     */
    protected $fieldTypeHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $dispatcher;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($this->configProvider));

        $this->fieldTypeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->extension = new DynamicFieldsExtension($this->configManager, $this->fieldTypeHelper, $this->dispatcher);
    }

    /**
     * @param array $fields
     * @param array $configValues
     * @param array $expected
     *
     * @dataProvider fieldsDataProvider
     */
    public function testGetFields(array $fields, array $configValues, array $expected)
    {
        $entity = new \StdClass();
        foreach ($fields as $field) {
            /** @var ConfigInterface $field */
            $fieldId = $field->getId();
            /** @var FieldConfigId $fieldId */
            $fieldName = $fieldId->getFieldName();
            $entity->{$fieldName} = $fieldName;
        }

        $this->configProvider
            ->expects($this->once())
            ->method('filter')
            ->will($this->returnValue($fields));

        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $this->configProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($config));

        foreach ($configValues as $key => $configValue) {
            $config
                ->expects($this->at(($key)))
                ->method('get')
                ->will(
                    $this->returnCallback(
                        function ($value, $strict, $default) use ($configValue) {
                            if (!is_null($configValue)) {
                                return $configValue;
                            }

                            return $default;
                        }
                    )
                );
        }

        $this->dispatcher
            ->expects($this->exactly(sizeof($fields)))
            ->method('dispatch');

        $rows = $this->extension->getFields($entity);

        $this->assertEquals(json_encode($expected), json_encode($rows));
    }

    /**
     * @return array
     */
    public function fieldsDataProvider()
    {
        return [
            'one field' => [
                [$this->getFieldMock('field', 'type1')],
                [],
                ['field' => ['type' => 'type1', 'label' => 'field', 'value' => 'field']]
            ],
            'two fields without sorting' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                [],
                [
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                ]
            ],
            'two sorted fields' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                ['type1', 'field1', 10, 'type2', 'field2', 15],
                [
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                ]
            ],
            'two sorted one without priority' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                ['type1', 'field1', null, 'type2', 'field2', 5],
                [
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                ]
            ],
            'two sorted another without priority' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                ['type1', 'field1', 5, 'type2', 'field2', null],
                [
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                ]
            ],
            'two sorted with less than zero' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                ['type1', 'field1', null, 'type2', 'field2', -10],
                [
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                ]
            ],
            'full' => [
                [
                    $this->getFieldMock('field1', 'type1'),
                    $this->getFieldMock('field2', 'type2'),
                    $this->getFieldMock('field3', 'type3'),
                    $this->getFieldMock('field4', 'type4'),
                    $this->getFieldMock('field5', 'type5'),
                ],
                [
                    'type1', 'field1', -10, 'type2', 'field2', -5,
                    'type3', 'field3', null, 'type4', 'field4', 0, 'type5', 'field5', 10
                ],
                [
                    'field5' => ['type' => 'type5', 'label' => 'field5', 'value' => 'field5'],
                    'field3' => ['type' => 'type3', 'label' => 'field3', 'value' => 'field3'],
                    'field4' => ['type' => 'type4', 'label' => 'field4', 'value' => 'field4'],
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                ]
            ]
        ];
    }

    /**
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigInterface
     */
    public function getFieldMock($fieldName, $fieldType)
    {
        $field = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configId = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $configId
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $configId
            ->expects($this->any())
            ->method('getFieldType')
            ->will($this->returnValue($fieldType));

        $field
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($configId));


        return $field;
    }
}
