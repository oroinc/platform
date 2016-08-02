<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetRelationConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\LoadFromMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class LoadFromMetadataTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LoadFromMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadFromMetadata($this->doctrineHelper);
    }

    public function testProcessWhenConfigIsAlreadyInitialized()
    {
        $config = [
            'fields' => [
                'id' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertFalse($this->context->getResult()->hasFields());
    }

    public function testProcessForManageableEntity()
    {
        $metadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);

        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'collapse'         => true,
                'fields'           => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithTableInheritance()
    {
        $metadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);

        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'collapse'         => true,
                'fields'           => [
                    'id'        => null,
                    '__class__' => [
                        'meta_property' => true,
                        'data_type'     => 'string'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
