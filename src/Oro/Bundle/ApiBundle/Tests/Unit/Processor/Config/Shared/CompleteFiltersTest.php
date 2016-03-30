<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteFiltersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var CompleteFilters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteFilters($this->doctrineHelper);
    }

    public function testProcessForAlreadyCompletedFilters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => null,
                'field4' => null,
            ]
        ];

        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude' => true
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessForNotCompletedFiltersButForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $filters = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessForNotCompletedFilters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'field3'       => null,
                'field4'       => null,
                'field5'       => null,
                'association1' => null,
                'association3' => null,
            ]
        ];

        $filters = [
            'fields' => [
                'field1'       => [
                    'data_type' => 'string'
                ],
                'field2'       => null,
                'field3'       => [
                    'exclude' => true
                ],
                'association3' => [
                    'data_type'   => 'string',
                    'allow_array' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects($this->once())
            ->method('getIndexedFields')
            ->with($this->identicalTo($rootEntityMetadata))
            ->willReturn(
                [
                    'field1' => 'integer',
                    'field5' => 'integer',
                    'field6' => 'integer',
                ]
            );
        $this->doctrineHelper->expects($this->once())
            ->method('getIndexedAssociations')
            ->with($this->identicalTo($rootEntityMetadata))
            ->willReturn(
                [
                    'association1' => 'integer',
                    'association2' => 'integer',
                    'association3' => 'integer',
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'       => [
                        'data_type' => 'string',
                        'allow_array' => true
                    ],
                    'field2'       => [
                        'exclude' => true
                    ],
                    'field3'       => [
                        'exclude' => true
                    ],
                    'field5'       => [
                        'data_type' => 'integer',
                        'allow_array' => true
                    ],
                    'association1' => [
                        'data_type'   => 'integer',
                        'allow_array' => true
                    ],
                    'association3' => [
                        'data_type' => 'string'
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }
}
