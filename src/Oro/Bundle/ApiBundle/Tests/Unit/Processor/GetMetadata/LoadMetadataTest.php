<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\AssociationMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\LoadMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

class LoadMetadataTest extends MetadataProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ObjectMetadataLoader&MockObject $objectMetadataLoader;
    private EntityMetadataLoader&MockObject $entityMetadataLoader;
    private AssociationMetadataLoader&MockObject $associationMetadataLoader;
    private LoadMetadata $processor;

    #[\Override]
    protected function setUp(): void
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

    public function testProcessForAlreadyLoadedMetadata(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        self::assertSame($metadata, $this->context->getResult());
    }

    public function testProcessForNotManageableEntityWithoutFieldsInConfig(): void
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');

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

    public function testProcessForNotManageableEntity(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        $entityMetadata = new EntityMetadata('Test\Entity');

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

    public function testProcessForManageableEntity(): void
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');

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
