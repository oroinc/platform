<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Bundle\CommentBundle\Api\Processor\AddCommentAssociationDescriptions;
use Oro\Bundle\CommentBundle\Entity\Comment;

class AddCommentAssociationDescriptionsTest extends ConfigProcessorTestCase
{
    /** @var CommentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $commentAssociationProvider;

    /** @var ResourceDocParserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $docParser;

    /** @var AddCommentAssociationDescriptions */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commentAssociationProvider = $this->createMock(CommentAssociationProvider::class);
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

        $this->processor = new AddCommentAssociationDescriptions(
            $this->commentAssociationProvider,
            $resourceDocParserProvider,
            $entityNameProvider
        );
    }

    public function testProcessWhenNoTargetAction(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::never())
            ->method('getCommentAssociationName');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessWhenTargetActionIsOptions(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::never())
            ->method('getCommentAssociationName');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::OPTIONS);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForEntityWithCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'target_class' => Comment::class,
                    'data_type'    => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroCommentBundle/Resources/doc/api/comment_association.md');
        $this->docParser->expects(self::exactly(2))
            ->method('getFieldDocumentation')
            ->withConsecutive(
                ['%comment_target_entity%', '%comments_association%', $targetAction],
                ['%comment_target_entity%', '%comments_association%', null]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                'Description for comments associated with the "%entity_name%".'
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for comments associated with the "entity (description)".'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationAndHasOwnDescriptionForTargetAction(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'target_class' => Comment::class,
                    'data_type'    => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroCommentBundle/Resources/doc/api/comment_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%comment_target_entity%', '%comments_association%', $targetAction)
            ->willReturn('Description for comments associated with the "%entity_name%" for target action.');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for comments associated with the "entity (description)"'
                            . ' for target action.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationWhenAssociationAlreadyHasDescription(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'target_class' => Comment::class,
                    'data_type'    => 'unidirectionalAssociation:association1',
                    'description'  => 'Existing description.'
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getFieldDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Existing description.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationWhenAssociationDoesNotExist(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getFieldDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessSubresourceForEntityWithCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'comments';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroCommentBundle/Resources/doc/api/comment_association.md');
        $this->docParser->expects(self::once())
            ->method('getSubresourceDocumentation')
            ->with('%comment_target_entity%', '%comments_association%', $targetAction)
            ->willReturn('Documentation for comments associated with the "%entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Documentation for comments associated with the "parent (description)".'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForEntityWithCommentsAssociationWhenItAlreadyHasDocumentation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'comments';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([
            'documentation' => 'Existing documentation.'
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
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

    public function testProcessSubresourceForEntityWithCommentsAssociationForNotCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'another';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
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
