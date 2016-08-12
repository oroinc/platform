<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetDataTransformers;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class SetDataTransformersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dataTransformerRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SetDataTransformers */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->dataTransformerRegistry = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetDataTransformers(
            $this->dataTransformerRegistry,
            $this->doctrineHelper
        );
    }

    public function testProcessForEmptyConfig()
    {
        $config = [];

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig([], $configObject);
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'exclusion_policy' => 'none',
            'fields'           => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'time'
                ],
                'field2' => [
                    'data_type' => 'integer'
                ],
                'field3' => null,
                'field4' => null,
                'field5' => [
                    'property_path' => 'realField5'
                ],
                'field6' => [
                    'property_path' => 'someAssociation.field6'
                ],
                'field7' => [
                    'data_transformer' => [
                        $this->getMock('Oro\Component\EntitySerializer\DataTransformerInterface')
                    ]
                ]
            ]
        ];

        $timeDataTransformer = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $this->dataTransformerRegistry->expects($this->any())
            ->method('getDataTransformer')
            ->willReturnMap(
                [
                    ['time', $timeDataTransformer],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, false)
            ->willReturn(null);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['data_transformer'] = [$timeDataTransformer];
        $this->assertConfig(
            $expectedConfig,
            $configObject
        );
    }

    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'time'
                ],
                'field2' => [
                    'data_type' => 'integer'
                ],
                'field3' => null,
                'field4' => null,
                'field5' => [
                    'property_path' => 'realField5'
                ],
                'field6' => [
                    'property_path' => 'someAssociation.field6'
                ],
                'field7' => [
                    'data_transformer' => [
                        $this->getMock('Oro\Component\EntitySerializer\DataTransformerInterface')
                    ]
                ],
            ]
        ];

        $timeDataTransformer = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $this->dataTransformerRegistry->expects($this->any())
            ->method('getDataTransformer')
            ->willReturnMap(
                [
                    ['time', $timeDataTransformer],
                ]
            );

        $metadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $metadata->expects($this->exactly(4))
            ->method('hasField')
            ->willReturnMap(
                [
                    ['field3', true],
                    ['field4', true],
                    ['realField5', true],
                    ['someAssociation.field6', false],
                ]
            );
        $metadata->expects($this->exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['field3', 'time'],
                    ['field4', 'integer'],
                    ['realField5', 'time'],
                ]
            );

        $this->doctrineHelper->expects($this->exactly(1))
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, false)
            ->willReturn($metadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['data_transformer'] = [$timeDataTransformer];
        $expectedConfig['fields']['field3']['data_transformer'] = [$timeDataTransformer];
        $expectedConfig['fields']['field5']['data_transformer'] = [$timeDataTransformer];
        $this->assertConfig(
            $expectedConfig,
            $configObject
        );
    }

    public function testProcessForManageableEntityWithAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'fields' => [
                        'field11' => null,
                        'field12' => null,
                        'field13' => [
                            'property_path' => 'realField13'
                        ],
                    ]
                ],
            ]
        ];

        $timeDataTransformer = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $this->dataTransformerRegistry->expects($this->any())
            ->method('getDataTransformer')
            ->willReturnMap(
                [
                    ['time', $timeDataTransformer],
                ]
            );

        $metadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, false, $metadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $metadata->expects($this->exactly(1))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['association1', true],
                ]
            );
        $metadata->expects($this->exactly(1))
            ->method('getAssociationTargetClass')
            ->willReturnMap(
                [
                    ['association1', 'Test\Association1Target'],
                ]
            );

        $association1Metadata->expects($this->exactly(3))
            ->method('hasField')
            ->willReturnMap(
                [
                    ['field11', true],
                    ['field12', true],
                    ['realField13', true],
                ]
            );
        $association1Metadata->expects($this->exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['field11', 'time'],
                    ['field12', 'integer'],
                    ['realField13', 'time'],
                ]
            );

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['association1']['fields']['field11']['data_transformer'] = [$timeDataTransformer];
        $expectedConfig['fields']['association1']['fields']['field13']['data_transformer'] = [$timeDataTransformer];
        $this->assertConfig(
            $expectedConfig,
            $configObject
        );
    }
}
