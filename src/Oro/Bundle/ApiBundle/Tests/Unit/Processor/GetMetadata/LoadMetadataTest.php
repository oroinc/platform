<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\AssociationMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\LoadMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class LoadMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectMetadataLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationMetadataLoader;

    /** @var LoadMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectMetadataLoader = $this->getMockBuilder(ObjectMetadataLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadataLoader = $this->getMockBuilder(EntityMetadataLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationMetadataLoader = $this->getMockBuilder(AssociationMetadataLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadMetadata(
            $this->doctrineHelper,
            $this->objectMetadataLoader,
            $this->entityMetadataLoader,
            $this->associationMetadataLoader
        );
    }

    public function testProcessForAlreadyLoadedMetadata()
    {
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $this->assertSame($metadata, $this->context->getResult());
    }

    public function testProcessForNotManageableEntityWithoutFieldsInConfig()
    {
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessForNotManageableEntity()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        $entityMetadata = new EntityMetadata();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->objectMetadataLoader->expects(self::once())
            ->method('loadObjectMetadata')
            ->with(
                self::TEST_CLASS_NAME,
                self::identicalTo($config),
                true,
                'targetAction'
            )
            ->willReturn($entityMetadata);
        $this->associationMetadataLoader->expects(self::once())
            ->method('completeAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($config),
                self::identicalTo($this->context)
            );

        $this->context->setConfig($config);
        $this->context->setWithExcludedProperties(true);
        $this->context->setTargetAction('targetAction');
        $this->processor->process($this->context);

        $this->assertSame($entityMetadata, $this->context->getResult());
    }

    public function testProcessForManageableEntityWithConfig()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->entityMetadataLoader->expects(self::once())
            ->method('loadEntityMetadata')
            ->with(
                self::TEST_CLASS_NAME,
                self::identicalTo($config),
                true,
                'targetAction'
            )
            ->willReturn($entityMetadata);
        $this->associationMetadataLoader->expects(self::once())
            ->method('completeAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($config),
                self::identicalTo($this->context)
            );

        $this->context->setConfig($config);
        $this->context->setWithExcludedProperties(true);
        $this->context->setTargetAction('targetAction');
        $this->processor->process($this->context);

        $this->assertSame($entityMetadata, $this->context->getResult());
    }
}
