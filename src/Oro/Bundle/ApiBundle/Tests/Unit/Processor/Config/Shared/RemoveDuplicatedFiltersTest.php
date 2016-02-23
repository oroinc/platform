<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\RemoveDuplicatedFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RemoveDuplicatedFiltersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var RemoveDuplicatedFilters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new RemoveDuplicatedFilters($this->doctrineHelper);
    }

    public function testProcessWhenNoFilters()
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'          => [
                    'property_path' => 'realField1'
                ],
                'association1'    => null,
                'association1.id' => null,
                'association2'    => null,
                'association2_id' => [
                    'property_path' => 'association2.id'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'          => [
                        'property_path' => 'realField1'
                    ],
                    'association1'    => null,
                    'association1.id' => null,
                    'association2'    => null,
                    'association2_id' => [
                        'property_path' => 'association2.id'
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessForManageableEntity()
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'            => [
                    'property_path' => 'realField1'
                ],
                'association1'      => null,
                'association1.id'   => null,
                'association2'      => null,
                'association2_id'   => [
                    'property_path' => 'association2.id'
                ],
                'association3'      => null,
                'association3.name' => null,
                'association4'      => null,
                'association4_id'   => [
                    'property_path' => 'association4.id'
                ],
            ]
        ];

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association2Metadata = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association3Metadata = $this->getClassMetadataMock('Test\Association3Target');
        $association3Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(4))
            ->method('findEntityMetadataByPath')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, ['association1'], $association1Metadata],
                    [self::TEST_CLASS_NAME, ['association2'], $association2Metadata],
                    [self::TEST_CLASS_NAME, ['association3'], $association3Metadata],
                    [self::TEST_CLASS_NAME, ['association4'], null],
                ]
            );

        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'            => [
                        'property_path' => 'realField1'
                    ],
                    'association1'      => null,
                    'association2'      => null,
                    'association3'      => null,
                    'association3.name' => null,
                    'association4'      => null,
                    'association4_id'   => [
                        'property_path' => 'association4.id'
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }
}
