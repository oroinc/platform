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
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataLoader */
    private $objectMetadataLoader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityMetadataLoader */
    private $entityMetadataLoader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationMetadataLoader */
    private $associationMetadataLoader;

    /** @var LoadMetadata */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->objectMetadataLoader = $this->createMock(ObjectMetadataLoader::class);
        $this->entityMetadataLoader = $this->createMock(EntityMetadataLoader::class);
        $this->associationMetadataLoader = $this->createMock(AssociationMetadataLoader::class);

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

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        self::assertSame($metadata, $this->context->getResult());
    }

    public function testProcessForNotManageableEntityWithoutFieldsInConfig()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->objectMetadataLoader->expects(self::once())
            ->method('loadObjectMetadata')
            ->with(
                self::TEST_CLASS_NAME,
                self::identicalTo($config),
                false,
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
        $this->context->setTargetAction('targetAction');
        $this->processor->process($this->context);

        self::assertSame($entityMetadata, $this->context->getResult());
    }

    public function testProcessForNotManageableEntity()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        $entityMetadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
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

        self::assertSame($entityMetadata, $this->context->getResult());
    }

    public function testProcessForManageableEntity()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
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

        self::assertSame($entityMetadata, $this->context->getResult());
    }
}
