<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\JsonApi\FixFieldNaming;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class FixFieldNamingTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var FixFieldNaming */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new FixFieldNaming($this->doctrineHelper);
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

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
                'id'   => null,
                'type' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithIdentifierNamedId()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, false)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithIdentifierNotNamedId()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, false)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithCompositeIdentifier()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id', 'id1']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, false)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenReservedFieldsHavePropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => [
                    'property_path' => 'realId'
                ],
                'type' => [
                    'property_path' => 'realType'
                ],
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'realId'
                    ],
                    'classType' => [
                        'property_path' => 'realType'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "id" reserved word cannot be used as a field name and it cannot be renamed to "classId" because a field with this name already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenIdFieldWithGuessedNameAlreadyExists()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'      => null,
                'classId' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "type" reserved word cannot be used as a field name and it cannot be renamed to "classType" because a field with this name already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenTypeFieldWithGuessedNameAlreadyExists()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type'      => null,
                'classType' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }
}
