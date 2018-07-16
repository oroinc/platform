<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Serializer\EntityFieldNormalizer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class EntityFieldNormalizerTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CONFIG_MODEL_CLASS_NAME = 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel';
    const FIELD_CONFIG_MODEL_CLASS_NAME = 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var FieldTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
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

        $this->normalizer = new EntityFieldNormalizer($this->registry, $this->configManager, $this->fieldTypeProvider);
    }

    /**
     * @param mixed $inputData
     * @param bool $expected
     *
     * @dataProvider supportsNormalizationProvider
     */
    public function testSupportsNormalization($inputData, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($inputData));
    }

    /**
     * @param array $inputData
     * @param bool $expected
     *
     * @dataProvider supportsDenormalizationProvider
     */
    public function testSupportsDenormalization(array $inputData, $expected)
    {
        $this->assertEquals(
            $expected,
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
            $expectedData,
            $this->normalizer->normalize($inputData['object'])
        );
    }

    /**
     * @dataProvider denormalizeExceptionDataProvider
     *
     * @@expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     * @expectedExceptionMessage Data doesn't contains entity id
     *
     * @param array $data
     */
    public function testDenormalizeException(array $data)
    {
        $this->normalizer->denormalize($data, null);
    }

    /**
     * @return array
     */
    public function denormalizeExceptionDataProvider()
    {
        return [
            [
                'data' => ['type' => 'test', 'fieldName' => 'test']
            ],
            [
                'data' => ['type' => 'test', 'fieldName' => 'test', 'entity' => ['id' => null]]
            ],
            [
                'data' => []
            ]
        ];
    }

    /**
     * @param array $inputData
     * @param FieldConfigModel $expectedData
     *
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize(array $inputData, FieldConfigModel $expectedData)
    {
        /* @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

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

        $this->assertEquals($expectedData, $this->normalizer->denormalize($inputData['data'], $inputData['class']));
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
                    'type' => self::FIELD_CONFIG_MODEL_CLASS_NAME,
                ],
                'expected' => true
            ],
            'not supported type' => [
                'input' => [
                    'data' => [
                        'type' => 'type2',
                        'fieldName' => 'field2',
                    ],
                    'type' => 'stdClass',
                ],
                'expected' => false
            ],
            'data is not array' => [
                'input' => [
                    'data' => 'testdata',
                    'type' => self::FIELD_CONFIG_MODEL_CLASS_NAME,
                ],
                'expected' => false
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
                'input' => new FieldConfigModel(),
                'expected' => true
            ],
            'not supported object' => [
                'input' => new \stdClass(),
                'expected' => false
            ],
            'not supported value' => [
                'input' => 'data',
                'expected' => false
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
                    'id' => 11,
                    'fieldName' => 'field1',
                    'type' => 'type1',
                    'scope1.code1' => 'value1',
                    'scope1.code2' => 'value2',
                    'scope2.code1' => 'value1',
                    'scope2.code2' => 'value2',
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
                        'fieldName' => null,
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
                        'enum.code1.1.is_default' => null,
                        'enum.code1.1.label' => null,
                        'enum.code1.2.label' => 'label2',
                        'enum.code1.2.is_default' => '',
                        'enum.code1.3.is_default' => null,
                        'enum.code1.3.label' => null,
                        'enum.code1.4.is_default' => null,
                        'enum.code1.5.label' => null,
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
                                'code1' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code2' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code3' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code4' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code5' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code6' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code7' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code8' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                                'code9' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_BOOLEAN)],
                            ],
                            'int' => [
                                'code1' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                                'code2' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                                'code3' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                                'code4' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                                'code5' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                                'code6' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_INTEGER)],
                            ],
                            'str' => [
                                'code1' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_STRING)],
                                'code2' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_STRING)],
                                'code3' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_STRING)],
                                'code4' => ['options' => $this->getOptions(EntityFieldNormalizer::TYPE_STRING)],
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
                'expected' => $this->getFieldConfigModel(null, null, 'fieldType1', [
                    'bool' => [
                        'code1' => false,
                        'code2' => false,
                        'code3' => false,
                        'code4' => false,
                        'code6' => true,
                        'code7' => true,
                        'code8' => true,
                        'code9' => false,
                    ],
                    'int' => [
                        'code1' => 1,
                        'code2' => 2,
                        'code3' => 0,
                        'code4' => 4,
                        'code5' => 0,
                    ],
                    'str' => [
                        'code1' => '1',
                        'code2' => '2',
                        'code3' => '',
                    ],
                    'unknown' => [
                        'code1' => '1',
                    ],
                    'enum' => [
                        'code1' => [
                            [
                                'id' => null,
                                'label' => 'label1',
                                'is_default' => true,
                                'priority' => null
                            ],
                            [
                                'id' => null,
                                'label' => 'label2',
                                'is_default' => false,
                                'priority' => null
                            ]
                        ],
                    ]
                ])->setEntity($this->getEntityConfigModel(1, 'className1')),
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getEnumOptions()
    {
        return [EntityFieldNormalizer::CONFIG_TYPE => EntityFieldNormalizer::TYPE_ENUM];
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getOptions($type)
    {
        return [EntityFieldNormalizer::CONFIG_TYPE => $type];
    }

    /**
     * @param string $scope
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProvider($scope)
    {
        /* @var $provider ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
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
     * @param string $className
     * @return EntityConfigModel
     */
    protected function getEntityConfigModel($objectId, $className)
    {
        return $this->getEntity(self::ENTITY_CONFIG_MODEL_CLASS_NAME, ['id' => $objectId, 'className' => $className]);
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
        /** @var FieldConfigModel $model */
        $model = $this->getEntity(
            self::FIELD_CONFIG_MODEL_CLASS_NAME,
            ['id' => $objectId, 'fieldName' => $fieldName, 'type' => $type]
        );

        foreach ($scopes as $scope => $values) {
            $model->fromArray($scope, $values, []);
        }

        return $model;
    }

    /**
     * @param string $className
     * @param array $properties
     * @return object
     */
    protected function getEntity($className, array $properties)
    {
        $reflectionClass = new \ReflectionClass($className);
        $entity = $reflectionClass->newInstance();

        foreach ($properties as $property => $value) {
            $method = $reflectionClass->getProperty($property);
            $method->setAccessible(true);
            $method->setValue($entity, $value);
        }

        return $entity;
    }
}
