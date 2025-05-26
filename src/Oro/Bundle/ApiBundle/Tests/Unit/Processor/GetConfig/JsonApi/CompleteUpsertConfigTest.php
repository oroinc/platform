<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\JsonApi;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi\CompleteUpsertConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use PHPUnit\Framework\MockObject\MockObject;

class CompleteUpsertConfigTest extends ConfigProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityIdHelper&MockObject $entityIdHelper;
    private CompleteUpsertConfig $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new CompleteUpsertConfig($this->doctrineHelper, $this->entityIdHelper);
    }

    public function testProcessWhenCompleteUpsertConfigAlreadyProcessed(): void
    {
        $definition = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());
        $this->entityIdHelper->expects(self::never())
            ->method(self::anything());

        $this->context->setProcessed(CompleteUpsertConfig::OPERATION_NAME);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertFalse($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsDisabled(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(true);
        $definition->getUpsertConfig()->addFields(['field1']);
        $definition->getUpsertConfig()->setEnabled(false);

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());
        $this->entityIdHelper->expects(self::never())
            ->method(self::anything());

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertFalse($definition->getUpsertConfig()->isEnabled());
        self::assertFalse($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForNotManageableEntity(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(true);
        $definition->getUpsertConfig()->addFields(['field1']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->entityIdHelper->expects(self::never())
            ->method(self::anything());

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($definition->getUpsertConfig()->isEnabled());
        self::assertTrue($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([['field1']], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForManageableEntityWithoutIdGenerator(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id']);
        $definition->addField('id');

        $metadata = new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->identifier = ['id'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->entityIdHelper->expects(self::never())
            ->method(self::anything());

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($definition->getUpsertConfig()->isEnabled());
        self::assertTrue($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForManageableEntityWithIdGenerator(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id']);
        $definition->addField('id');

        $metadata = new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->identifier = ['id'];
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->entityIdHelper->expects(self::once())
            ->method('isEntityIdentifierEqual')
            ->with(['id'], self::identicalTo($definition))
            ->willReturn(true);

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertFalse($definition->getUpsertConfig()->isEnabled());
        self::assertFalse($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForManageableEntityWithIdGeneratorAndCustomResourceId(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['field1']);
        $definition->addField('id');
        $definition->addField('field1');

        $metadata = new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->identifier = ['id'];
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->entityIdHelper->expects(self::once())
            ->method('isEntityIdentifierEqual')
            ->with(['id'], self::identicalTo($definition))
            ->willReturn(false);

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($definition->getUpsertConfig()->isEnabled());
        self::assertTrue($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForManageableEntityWithUniqueConstraints(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id']);
        $definition->addField('id');
        $definition->addField('field1');
        $definition->addField('field2');
        $definition->addField('renamedField3')->setPropertyPath('field3');
        $definition->addField('field4');
        $definition->addField('renamedField5')->setPropertyPath('field5');

        $metadata = new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->identifier = ['id'];
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $metadata->fieldMappings = [
            'id'     => ['unique' => true],
            'field1' => ['unique' => false],
            'field2' => ['unique' => true],
            'field3' => ['unique' => true],
            'field4' => [],
            'field5' => [],
            'field6' => ['unique' => true],
            'field7' => []
        ];
        $metadata->table['uniqueConstraints'] = [
            ['columns' => ['field5', 'field1']],
            ['columns' => ['field7', 'field1']]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->entityIdHelper->expects(self::once())
            ->method('isEntityIdentifierEqual')
            ->with(['id'], self::identicalTo($definition))
            ->willReturn(true);

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($definition->getUpsertConfig()->isEnabled());
        self::assertFalse($definition->getUpsertConfig()->isAllowedById());
        self::assertSame(
            [['field2'], ['renamedField3'], ['field1', 'renamedField5']],
            $definition->getUpsertConfig()->getFields()
        );
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertByIdIsDisabledInConfig(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->removeFields(['id']);

        $metadata = new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->identifier = ['id'];
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $metadata->fieldMappings = [
            'id' => ['unique' => true]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->entityIdHelper->expects(self::never())
            ->method('isEntityIdentifierEqual');

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($definition->getUpsertConfig()->hasEnabled());
        self::assertFalse($definition->getUpsertConfig()->isEnabled());
        self::assertFalse($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }

    public function testProcessWhenUpsertIsEnabledForManageableEntityWithUniqueConstraintsAndIdOnlyRequested(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(true);
        $definition->getUpsertConfig()->addFields(['field1']);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');
        $this->entityIdHelper->expects(self::never())
            ->method('isEntityIdentifierEqual');

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertFalse($definition->getUpsertConfig()->hasEnabled());
        self::assertTrue($definition->getUpsertConfig()->isEnabled());
        self::assertTrue($definition->getUpsertConfig()->isAllowedById());
        self::assertSame([['field1']], $definition->getUpsertConfig()->getFields());
        self::assertTrue($this->context->isProcessed(CompleteUpsertConfig::OPERATION_NAME));
    }
}
