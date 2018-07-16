<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig\JsonApi;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\JsonApi\CompleteStatusCodes;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\HttpFoundation\Response;

class CompleteStatusCodesTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var CompleteStatusCodes */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new CompleteStatusCodes($this->doctrineHelper);
    }

    public function testAddStatusCodesForCreateActionForNotManageableEntity()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionEntityWithCompositeId()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->setIdentifierFieldNames(['id1', 'id2']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionEntityWithoutIdGenerator()
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
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionEntityWithIdGenerator()
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
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionEntityWithIdGeneratorButWhenConfiguredIdDoesNotMatchEntityId()
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
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionEntityWithIdGeneratorAndRenamedId()
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
        $this->context->setTargetAction(ApiActions::CREATE);
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

    public function testAddStatusCodesForCreateActionWhenConflictStatusCodeAlreadyAdded()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->getStatusCodes()->addCode(Response::HTTP_CONFLICT, new StatusCodeConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiActions::CREATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => null
            ],
            $definition->getStatusCodes()->toArray()
        );
    }

    public function testAddStatusCodesForUpdateAction()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiActions::UPDATE);
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

    public function testAddStatusCodesForUpdateActionWhenConflictStatusCodeAlreadyAdded()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setStatusCodes(new StatusCodesConfig());
        $definition->getStatusCodes()->addCode(Response::HTTP_CONFLICT, new StatusCodeConfig());

        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiActions::UPDATE);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Response::HTTP_CONFLICT => null
            ],
            $definition->getStatusCodes()->toArray()
        );
    }
}
