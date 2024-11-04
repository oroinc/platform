<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\JsonApi;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi\CompleteStatusCodes;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompleteStatusCodesTest extends ConfigProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CompleteStatusCodes */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new CompleteStatusCodes($this->doctrineHelper);
    }

    public function testAddStatusCodesForCreateAndForNotManageableEntity(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or a client-generated identifier already exists'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithCompositeId(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id1', 'id2']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or a client-generated identifier already exists'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithoutIdGenerator(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id']);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(false);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or a client-generated identifier already exists'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithIdGenerator(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id']);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithIdGeneratorButWhenConfiguredIdDoesNotMatchEntityId(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id']);
        $definition->addField('id')->setPropertyPath('notIdField');

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or a client-generated identifier already exists'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithIdGeneratorAndRenamedId(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['renamedId']);
        $definition->addField('renamedId')->setPropertyPath('id');

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithoutIdGeneratorAndUpsertByFieldsAllowed(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id']);
        $definition->getUpsertConfig()->addFields(['field1']);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(false);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or a client-generated identifier already exists'
                        . ' or when more than one entities were found by the upsert operation'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndEntityWithIdGeneratorAndUpsertByFieldsAllowed(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id']);
        $definition->getUpsertConfig()->addFields(['field1']);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type does not match the server\'s endpoint'
                        . ' or when more than one entities were found by the upsert operation'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForCreateAndWhenConflictStatusCodeAlreadyAdded(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->getStatusCodes()->addCode(Response::HTTP_CONFLICT, new StatusCodeConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => null
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForUpdate(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => [
                    'description' => 'Returned when the specified entity type and identifier'
                        . ' do not match the server\'s endpoint'
                ]
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForUpdateAndWhenConflictStatusCodeAlreadyAdded(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->getStatusCodes()->addCode(Response::HTTP_CONFLICT, new StatusCodeConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => null
            ],
            $definition->getStatusCodes()->toArray()
        );
    }
}
