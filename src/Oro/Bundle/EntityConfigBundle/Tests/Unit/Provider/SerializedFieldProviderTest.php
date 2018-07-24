<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

class SerializedFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $extendConfigProvider;
    /**
     * @var SerializedFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializedFieldProvider;

    protected function setUp()
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->serializedFieldProvider = new SerializedFieldProvider($this->extendConfigProvider);
    }

    /**
     * @return FieldConfigModel
     */
    private function checkIsSerializedWrongType()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'wrong_type');
        $this->extendConfigProvider->expects($this->never())
            ->method('getPropertyConfig');

        return $fieldConfigModel;
    }

    public function testIsSerializedWrongType()
    {
        $fieldConfigModel = $this->checkIsSerializedWrongType();
        $this->assertFalse($this->serializedFieldProvider->isSerialized($fieldConfigModel));
    }

    public function testIsSerializedByDataWrongType()
    {
        $fieldConfigModel = $this->checkIsSerializedWrongType();
        $this->assertFalse($this->serializedFieldProvider->isSerializedByData($fieldConfigModel, []));
    }

    /**
     * @return FieldConfigModel
     */
    private function expectsEmptyPropertiesValues()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $propertyConfigContainer = $this->getMockBuilder(PropertyConfigContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getRequiredPropertiesValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);
        $this->extendConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);

        return $fieldConfigModel;
    }

    public function testIsSerializedException()
    {
        $fieldConfigModel = $this->expectsEmptyPropertiesValues();
        $this->assertFalse($this->serializedFieldProvider->isSerialized($fieldConfigModel));
    }

    public function testIsSerializedByDataException()
    {
        $fieldConfigModel = $this->expectsEmptyPropertiesValues();
        $this->assertFalse($this->serializedFieldProvider->isSerializedByData($fieldConfigModel, []));
    }

    public function testIsSerializedByModelFalse()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $fieldConfigModel->fromArray('attribute', ['sortable' => true]);

        $this->assertExtendConfigProvider();

        $isSerialized = $this->serializedFieldProvider->isSerialized($fieldConfigModel);

        $this->assertFalse($isSerialized);
    }

    public function testIsSerializedByModelTrue()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $fieldConfigModel->fromArray('attribute', ['sortable' => false, 'enabled' => true]);

        $this->assertExtendConfigProvider();

        $isSerialized = $this->serializedFieldProvider->isSerialized($fieldConfigModel);

        $this->assertTrue($isSerialized);
    }

    public function testIsSerializedByDataFalse()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $data = ['attribute' => ['sortable' => true]];

        $this->assertExtendConfigProvider();

        $isSerialized = $this->serializedFieldProvider->isSerializedByData($fieldConfigModel, $data);

        $this->assertFalse($isSerialized);
    }

    public function testIsSerializedByDataTrue()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $data = ['attribute' => ['sortable' => false, 'enabled' => true]];

        $this->assertExtendConfigProvider();

        $isSerialized = $this->serializedFieldProvider->isSerializedByData($fieldConfigModel, $data);
        
        $this->assertTrue($isSerialized);
    }

    /**
     * @return array
     */
    public function allowEmptyDataProvider()
    {
        return [
            'empty allowed' => [
                'allowEmpty' => true,
                'isSerialized' => true
            ],
            'empty not allowed' => [
                'allowEmpty' => false,
                'isSerialized' => false
            ]
        ];
    }

    protected function assertExtendConfigProvider()
    {
        $propertyConfigContainer = $this->createMock(PropertyConfigContainer::class);
        $propertyConfigContainer->expects($this->once())
            ->method('getRequiredPropertiesValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(
                [
                    'is_serialized' => [
                        [
                            'config_id' => ['scope' => 'attribute'],
                            'code' => 'sortable',
                            'value' => false,
                        ],
                    ],
                ]
            );

        $this->extendConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
    }
}
