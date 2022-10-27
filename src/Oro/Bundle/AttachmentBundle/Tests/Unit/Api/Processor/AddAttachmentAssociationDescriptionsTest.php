<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Bundle\AttachmentBundle\Api\Processor\AddAttachmentAssociationDescriptions;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class AddAttachmentAssociationDescriptionsTest extends ConfigProcessorTestCase
{
    /** @var AttachmentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationProvider;

    /** @var ResourceDocParserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $docParser;

    /** @var AddAttachmentAssociationDescriptions */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentAssociationProvider = $this->createMock(AttachmentAssociationProvider::class);
        $this->docParser = $this->createMock(ResourceDocParserInterface::class);

        $resourceDocParserProvider = $this->createMock(ResourceDocParserProvider::class);
        $resourceDocParserProvider->expects(self::any())
            ->method('getResourceDocParser')
            ->with($this->context->getRequestType())
            ->willReturn($this->docParser);

        $entityNameProvider = $this->createMock(EntityNameProvider::class);
        $entityNameProvider->expects(self::any())
            ->method('getEntityName')
            ->with(self::isType('string'))
            ->willReturnCallback(function (string $className) {
                return strtolower(substr($className, strrpos($className, '\\') + 1)) . ' (description)';
            });

        $this->processor = new AddAttachmentAssociationDescriptions(
            $this->attachmentAssociationProvider,
            $resourceDocParserProvider,
            $entityNameProvider
        );
    }

    public function testProcessWhenNoTargetAction(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::never())
            ->method('getAttachmentAssociationName');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessWhenTargetActionIsOptions(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::never())
            ->method('getAttachmentAssociationName');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::OPTIONS);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForEntityWithAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'target_class' => Attachment::class,
                    'data_type'    => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroAttachmentBundle/Resources/doc/api/attachment_association.md');
        $this->docParser->expects(self::exactly(2))
            ->method('getFieldDocumentation')
            ->withConsecutive(
                ['%attachment_target_entity%', '%attachments_association%', $targetAction],
                ['%attachment_target_entity%', '%attachments_association%', null]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                'Description for attachments associated with the "%entity_name%".'
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for attachments associated with the "entity (description)".'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationAndHasOwnDescriptionForTargetAction(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'target_class' => Attachment::class,
                    'data_type'    => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroAttachmentBundle/Resources/doc/api/attachment_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%attachment_target_entity%', '%attachments_association%', $targetAction)
            ->willReturn('Description for attachments associated with the "%entity_name%" for target action.');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for attachments associated with the "entity (description)"'
                            . ' for target action.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationWhenAssociationAlreadyHasDescription(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'attachments' => [
                    'target_class' => Attachment::class,
                    'data_type'    => 'unidirectionalAssociation:association1',
                    'description'  => 'Existing description.'
                ]
            ]
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'attachments' => [
                        'target_class' => Attachment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Existing description.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithAttachmentsAssociationWhenAssociationDoesNotExist(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessSubresourceForEntityWithAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'attachments';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroAttachmentBundle/Resources/doc/api/attachment_association.md');
        $this->docParser->expects(self::once())
            ->method('getSubresourceDocumentation')
            ->with('%attachment_target_entity%', '%attachments_association%', $targetAction)
            ->willReturn('Documentation for attachments associated with the "%entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Documentation for attachments associated with the "parent (description)".'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForEntityWithAttachmentsAssociationWhenItAlreadyHasDocumentation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'attachments';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([
            'documentation' => 'Existing documentation.'
        ]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Existing documentation.'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForEntityWithAttachmentsAssociationForNotAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'another';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }
}
