<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Bundle\AttachmentBundle\Api\Processor\AddAttachmentAssociations;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class AddAttachmentAssociationsTest extends ConfigProcessorTestCase
{
    /** @var AttachmentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationProvider;

    /** @var AddAttachmentAssociations */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentAssociationProvider = $this->createMock(AttachmentAssociationProvider::class);

        $this->processor = new AddAttachmentAssociations($this->attachmentAssociationProvider);
    }

    public function testProcessForEntityWithoutAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForEntityWithAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationForUpdateAction(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'form_options' => [
                            'mapped' => false
                        ]
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationWhenAssociationIsDisabled(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'exclude' => true
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'exclude'      => true
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationWhenAssociationConfiguredManually(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'data_type' => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationWhenItReconfiguredToUseAnotherDataType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The association "attachments" cannot be added to "Test\Entity"'
            . ' because an association with this name already exists.'
        );

        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'data_type' => 'int'
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);
    }
}
