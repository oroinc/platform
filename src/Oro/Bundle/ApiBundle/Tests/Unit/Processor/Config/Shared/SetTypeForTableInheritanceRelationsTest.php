<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\SetTypeForTableInheritanceRelations;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class SetTypeForTableInheritanceRelationsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SetTypeForTableInheritanceRelations */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetTypeForTableInheritanceRelations($this->doctrineHelper);
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'property_path' => 'realField2'
                ],
                'association1' => null,
                'association2' => [
                    'property_path' => 'realAssociation2'
                ],
                'association3' => [
                    'fields' => [
                        '__class__' => null
                    ]
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->exactly(5))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['field1', false],
                    ['realField2', false],
                    ['association1', true],
                    ['realAssociation2', true],
                    ['association3', true],
                ]
            );
        $rootEntityMetadata->expects($this->exactly(3))
            ->method('getAssociationMapping')
            ->willReturnMap(
                [
                    ['association1', ['targetEntity' => 'Test\Association1Target']],
                    ['realAssociation2', ['targetEntity' => 'Test\Association2Target']],
                    ['association3', ['targetEntity' => 'Test\Association3Target']],
                ]
            );

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $association2Metadata                  = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;

        $association3Metadata                  = $this->getClassMetadataMock('Test\Association3Target');
        $association3Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(4))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                    ['Test\Association2Target', true, $association2Metadata],
                    ['Test\Association3Target', true, $association3Metadata],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'       => null,
                    'field2'       => [
                        'property_path' => 'realField2'
                    ],
                    'association1' => null,
                    'association2' => [
                        'property_path' => 'realAssociation2',
                        'target_class'  => 'Test\Association2Target',
                        'fields'        => [
                            '__class__' => null
                        ]
                    ],
                    'association3' => [
                        'target_class' => 'Test\Association3Target',
                        'fields'       => [
                            '__class__' => null
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
