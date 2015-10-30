<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Serializer;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Serializer\EntityFieldNormalizer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class EntityFieldNormalizerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var FieldTypeProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $fieldTypeProvider;

    /** @var EntityFieldNormalizer */
    protected $normalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldTypeProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new EntityFieldNormalizer();
        $this->normalizer->setRegistry($this->registry);
        $this->normalizer->setConfigManager($this->configManager);
        $this->normalizer->setFieldTypeProvider($this->fieldTypeProvider);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider supportsNormalizationProvider
     */
    public function testSupportsNormalization(array $inputData, array $expectedData)
    {
        $this->assertEquals(
            $expectedData['supports'],
            $this->normalizer->supportsNormalization($inputData['data'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider supportsDenormalizationProvider
     */
    public function testSupportsDenormalization(array $inputData, array $expectedData)
    {
        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn($inputData['supportedTypes']);

        $this->assertEquals(
            $expectedData['supports'],
            $this->normalizer->supportsDenormalization($inputData['data'], $inputData['type'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider normalizeProvider
     */
    public function testNormalize(array $inputData, array $expectedData)
    {
        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn($inputData['providers']);

        $this->assertEquals(
            $expectedData['result'],
            $this->normalizer->normalize($inputData['object'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize(array $inputData, array $expectedData)
    {
        /* @var $objectManager ObjectManager */
        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($inputData['configModel']['class'])
            ->willReturn($objectManager);

        $objectManager->expects($this->once())
            ->method('find')
            ->with($inputData['configModel']['class'], $inputData['configModel']['id'])
            ->willReturn($inputData['configModel']['object']);

        $this->fieldTypeProvider->expects($this->once())
            ->method('getFieldProperties')
            ->with($inputData['fieldType']['modelType'])
            ->willReturn($inputData['fieldType']['fieldProperties']);

        $this->assertEquals(
            $expectedData['result'],
            $this->normalizer->denormalize($inputData['data'], $inputData['class'])
        );
    }

    /**
     * @return array
     */
    public function supportsDenormalizationProvider()
    {
        return [
            'supported' => [
                'input' => [
                    'data' => [
                        'type' => 'type1',
                        'fieldName' => 'field1',
                    ],
                    'type' => new FieldConfigModel(),
                    'supportedTypes' => ['type1'],
                ],
                'expected' => [
                    'supports' => true,
                ],
            ],
            'not supported type' => [
                'input' => [
                    'data' => [
                        'type' => 'type2',
                        'fieldName' => 'field2',
                    ],
                    'type' => new \stdClass(),
                    'supportedTypes' => ['type2'],
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
            'data[type] is not in supportedTypes' => [
                'input' => [
                    'data' => [
                        'type' => 'type3',
                        'fieldName' => 'field3',
                    ],
                    'type' => new FieldConfigModel(),
                    'supportedTypes' => ['type'],
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
            'empty data[type]' => [
                'input' => [
                    'data' => [
                        'fieldName' => 'field4',
                    ],
                    'type' => new FieldConfigModel(),
                    'supportedTypes' => ['type4'],
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
            'empty data[fieldName]' => [
                'input' => [
                    'data' => [
                        'type' => 'type5',
                    ],
                    'type' => new FieldConfigModel(),
                    'supportedTypes' => ['type5'],
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
            'data is not array' => [
                'input' => [
                    'data' => 'testdata',
                    'type' => new FieldConfigModel(),
                    'supportedTypes' => ['type6'],
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function supportsNormalizationProvider()
    {
        return [
            'supported' => [
                'input' => [
                    'data' => new FieldConfigModel(),
                ],
                'expected' => [
                    'supports' => true,
                ],
            ],
            'not supported' => [
                'input' => [
                    'data' => new \stdClass(),
                ],
                'expected' => [
                    'supports' => false,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function normalizeProvider()
    {
        return [
            [
                'input' => [
                    'providers' => [
                        $this->getConfigProvider('scope1'),
                        $this->getConfigProvider('scope2'),
                        $this->getConfigProvider('scope3'),
                    ],
                    'object' => $this->getFieldConfigModel(11, 'field1', 'type1', [
                        'scope1' => [
                            'code1' => 'value1',
                            'code2' => 'value2',
                        ],
                        'scope2' => [
                            'code1' => 'value1',
                            'code2' => 'value2',
                        ],
                    ]),
                ],
                'expected' => [
                    'result' => [
                        'id' => 11,
                        'fieldName' => 'field1',
                        'type' => 'type1',
                        'scope1.code1' => 'value1',
                        'scope1.code2' => 'value2',
                        'scope2.code1' => 'value1',
                        'scope2.code2' => 'value2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeProvider()
    {
        return [
            [
                'input' => [
                    'data' => [
                        'type' => 'fieldType1',
                        'fieldName' => 'fieldName1',
                        'entity' => [
                            'id' => 11,
                        ],

                        'bool.code1' => 'no',
                        'bool.code2' => 'False',
                        'bool.code3' => '0',
                        'bool.code4' => '',
                        'bool.code5' => null,
                        'bool.code6' => 'Yes',
                        'bool.code7' => 'TRUE',
                        'bool.code8' => '1',
                        'bool.code9' => 'true value',

                        'int.code1' => 1,
                        'int.code2' => '2',
                        'int.code3' => 'v3',
                        'int.code4' => '4v',
                        'int.code5' => '',
                        'int.code6' => null,

                        'str.code1' => '1',
                        'str.code2' => 2,
                        'str.code3' => '',
                        'str.code4' => null,

                        'unknown.code1' => 1,

                        'enum.code1.0.label' => 'label1',
                        'enum.code1.0.is_default' => 'yes',
                        'enum.code1.1.label' => 'label2',
                        'enum.code1.1.is_default' => '',

                        'notsupportedsope.code1' => 'value7',
                    ],
                    'class' => 'testClass1',
                    'configModel' => [
                        'id' => 11,
                        'class' => 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
                        'object' => $this->getEntityConfigModel(1, 'className1'),
                    ],
                    'fieldType' => [
                        'modelType' => 'fieldType1',
                        'fieldProperties' => [
                            'bool' => [
                                'code1' => ['options' => $this->getBoolOptions()],
                                'code2' => ['options' => $this->getBoolOptions()],
                                'code3' => ['options' => $this->getBoolOptions()],
                                'code4' => ['options' => $this->getBoolOptions()],
                                'code5' => ['options' => $this->getBoolOptions()],
                                'code6' => ['options' => $this->getBoolOptions()],
                                'code7' => ['options' => $this->getBoolOptions()],
                                'code8' => ['options' => $this->getBoolOptions()],
                                'code9' => ['options' => $this->getBoolOptions()],
                            ],
                            'int' => [
                                'code1' => ['options' => $this->getIntOptions()],
                                'code2' => ['options' => $this->getIntOptions()],
                                'code3' => ['options' => $this->getIntOptions()],
                                'code4' => ['options' => $this->getIntOptions()],
                                'code5' => ['options' => $this->getIntOptions()],
                                'code6' => ['options' => $this->getIntOptions()],
                            ],
                            'str' => [
                                'code1' => ['options' => $this->getStringOptions()],
                                'code2' => ['options' => $this->getStringOptions()],
                                'code3' => ['options' => $this->getStringOptions()],
                                'code4' => ['options' => $this->getStringOptions()],
                            ],
                            'unknown' => [
                                'code1' => [],
                            ],
                            'enum' => [
                                'code1' => ['options' => $this->getEnumOptions()],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'result' => $this->getFieldConfigModel(null, 'fieldName1', 'fieldType1', [
                        'bool' => [
                            'code1' => false,
                            'code2' => false,
                            'code3' => false,
                            'code4' => false,
                            'code5' => true,
                            'code6' => true,
                            'code7' => true,
                            'code8' => true,
                            'code9' => true,
                        ],
                        'int' => [
                            'code1' => 1,
                            'code2' => 2,
                            'code3' => 0,
                            'code4' => 4,
                            'code5' => 0,
                            'code6' => 0,
                        ],
                        'str' => [
                            'code1' => '1',
                            'code2' => '2',
                            'code3' => '',
                            'code4' => '',
                        ],
                        'unknown' => [
                            'code1' => '1',
                        ],
                        'enum' => [
                            'code1' => [
                                [
                                    'label' => 'label1',
                                    'is_default' => true,
                                ],
                                [
                                    'label' => 'label2',
                                    'is_default' => false,
                                ]
                            ],
                        ]
                    ])->setEntity($this->getEntityConfigModel(1, 'className1')),
                ],
            ],
        ];
    }

    /**
     * @param bool $default
     * @return array
     */
    protected function getBoolOptions($default = true)
    {
        return $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN, $default);
    }

    /**
     * @param int $default
     * @return array
     */
    protected function getIntOptions($default = 0)
    {
        return $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER, $default);
    }

    /**
     * @param string $default
     * @return array
     */
    protected function getStringOptions($default = '')
    {
        return $this->getOptions(EntityFieldNormalizer::TYPE_STRING, $default);
    }

    /**
     * @return array
     */
    protected function getEnumOptions()
    {
        return [
            EntityFieldNormalizer::CONFIG_TYPE => EntityFieldNormalizer::TYPE_ENUM,
        ];
    }

    /**
     * @param string $type
     * @param mixed $default
     * @return array
     */
    protected function getOptions($type, $default)
    {
        return [
            EntityFieldNormalizer::CONFIG_TYPE => $type,
            EntityFieldNormalizer::CONFIG_DEFAULT => $default,
        ];
    }

    /**
     * @param string $scope
     * @return ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProvider($scope)
    {
        /* @var $provider ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);

        return $provider;
    }

    /**
     * @param int $objectId
     * @return EntityConfigModel
     */
    protected function getEntityConfigModel($objectId, $className)
    {
        $model = new EntityConfigModel($className);

        $this->setPropertyValue($model, 'id', $objectId);

        return $model;
    }

    /**
     * @param int $objectId
     * @param string $fieldName
     * @param string $type
     * @param array $scopes
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel($objectId, $fieldName, $type, array $scopes)
    {
        $model = new FieldConfigModel($fieldName, $type);

        $this->setPropertyValue($model, 'id', $objectId);

        foreach ($scopes as $scope => $values) {
            $model->fromArray($scope, $values, []);
        }

        return $model;
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed $value
     */
    protected function setPropertyValue($object, $propertyName, $value)
    {
        $reflectionClass = new \ReflectionClass(get_class($object));
        $method = $reflectionClass->getProperty($propertyName);
        $method->setAccessible(true);
        $method->setValue($object, $value);
    }
}
