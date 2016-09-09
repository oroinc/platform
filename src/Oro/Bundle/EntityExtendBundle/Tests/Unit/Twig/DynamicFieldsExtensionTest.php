<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($this->configProvider));

        $this->fieldTypeHelper = $this->getMockBuilder(FieldTypeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $this->extension = new DynamicFieldsExtension(
            $this->configManager,
            $this->fieldTypeHelper,
            $this->dispatcher,
            $this->securityFacade
        );
    }

    /**
     * @param array $fields
     * @param array $configValues
     * @param array $expected
     * @param array $nonAccessibleFields
     *
     * @dataProvider fieldsDataProviderWithNonAccessibleFields
     */
    public function testGetFieldsWithNonAccessibleFields(
        array $fields,
        array $configValues,
        array $expected,
        array $nonAccessibleFields
    ) {
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

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(
                function ($argument, FieldVote $field) use ($nonAccessibleFields) {
                    return !in_array($field->getField(), $nonAccessibleFields);
                }
            );

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

        $rows = $this->extension->getFields($entity);

        $this->assertEquals(json_encode($expected), json_encode($rows));
    }

    /**
     * @return array
     */
    public function fieldsDataProviderWithNonAccessibleFields()
    {
        return [
            'one field' => [
                [$this->getFieldMock('field', 'type1')],
                [],
                [],
                ['field']
            ],
            'two fields without sorting' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                [],
                [
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                ],
                ['field1']
            ],
            'two sorted fields' => [
                [$this->getFieldMock('field1', 'type1'), $this->getFieldMock('field2', 'type2')],
                ['type1', 'field1', 10],
                [
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                ],
                ['field2']
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
                    'type3', 'field3', null
                ],
                [
                    'field3' => ['type' => 'type3', 'label' => 'field3', 'value' => 'field3'],
                    'field2' => ['type' => 'type2', 'label' => 'field2', 'value' => 'field2'],
                    'field1' => ['type' => 'type1', 'label' => 'field1', 'value' => 'field1'],
                ],
                ['field5', 'field4']
            ]
        ];
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
        $entity = new \stdClass();
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

        $config = $this->getMockBuilder(ConfigInterface::class)->getMock();

        $this->configProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($config));

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

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
        $field = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $configId = $this
            ->getMockBuilder(FieldConfigId::class)
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

    public function testSkipFieldIfNotVisible()
    {
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);
        
        $this->dispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($eventName, $event) {
                $event->setFieldVisibility(false);
            });

        $configFieldId = $this->getMockBuilder(FieldConfigId::class)->disableOriginalConstructor()->getMock();
        $configFieldId->expects($this->once())
            ->method('getFieldName')
            ->willReturn('getFieldValue');
        $configField = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $configField->expects($this->once())
            ->method('getId')
            ->willReturn($configFieldId);
        $this->configProvider->expects($this->once())
            ->method('filter')
            ->willReturn([$configField]);

        $entityMock = $this->getMockBuilder(ValueRenderEvent::class)->disableOriginalConstructor()->getMock();
        $entityMock->expects($this->once())
            ->method('getFieldValue')
            ->willReturn('testValue');

        $rows = $this->extension->getFields($entityMock, ValueRenderEvent::class);
        $this->assertEmpty($rows);
    }
}
