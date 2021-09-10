<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Serializer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Serializer\EntityFieldNormalizer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class EntityFieldNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FieldTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeProvider;

    /** @var EntityFieldNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);

        $this->normalizer = new EntityFieldNormalizer($this->registry, $this->configManager, $this->fieldTypeProvider);
    }

    /**
     * @dataProvider supportsNormalizationProvider
     */
    public function testSupportsNormalization(mixed $inputData, bool $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($inputData));
    }

    /**
     * @dataProvider supportsDenormalizationProvider
     */
    public function testSupportsDenormalization(array $inputData, bool $expected)
    {
        $this->assertEquals(
            $expected,
            $this->normalizer->supportsDenormalization($inputData['data'], $inputData['type'])
        );
    }

    /**
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
     */
    public function testDenormalizeException(array $data)
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Data doesn't contains entity id");

        $this->normalizer->denormalize($data, '');
    }

    public function denormalizeExceptionDataProvider(): array
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
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize(array $inputData, FieldConfigModel $expectedData)
    {
        /* @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->createMock(ObjectManager::class);

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

    public function supportsDenormalizationProvider(): array
    {
        return [
            'supported' => [
                'input' => [
                    'data' => [
                        'type' => 'type1',
                        'fieldName' => 'field1',
                    ],
                    'type' => FieldConfigModel::class,
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
                    'type' => FieldConfigModel::class,
                ],
                'expected' => false
            ],
        ];
    }

    public function supportsNormalizationProvider(): array
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

    public function normalizeProvider(): array
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeProvider(): array
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
                        'class' => EntityConfigModel::class,
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

    private function getEnumOptions(): array
    {
        return [EntityFieldNormalizer::CONFIG_TYPE => EntityFieldNormalizer::TYPE_ENUM];
    }

    private function getOptions(string $type): array
    {
        return [EntityFieldNormalizer::CONFIG_TYPE => $type];
    }

    private function getConfigProvider(string $scope): ConfigProvider
    {
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);

        return $provider;
    }

    private function getEntityConfigModel(int $objectId, string $className): EntityConfigModel
    {
        $model = new EntityConfigModel($className);
        ReflectionUtil::setId($model, $objectId);

        return $model;
    }

    private function getFieldConfigModel(
        ?int $objectId,
        ?string $fieldName,
        string $type,
        array $scopes
    ): FieldConfigModel {
        $model = new FieldConfigModel($fieldName, $type);
        ReflectionUtil::setId($model, $objectId);
        foreach ($scopes as $scope => $values) {
            $model->fromArray($scope, $values);
        }

        return $model;
    }
}
