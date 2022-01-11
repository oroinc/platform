<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Bundle\CommentBundle\Api\Processor\AddCommentAssociations;
use Oro\Bundle\CommentBundle\Entity\Comment;

class AddCommentAssociationsTest extends ConfigProcessorTestCase
{
    /** @var CommentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $commentAssociationProvider;

    /** @var AddCommentAssociations */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commentAssociationProvider = $this->createMock(CommentAssociationProvider::class);

        $this->processor = new AddCommentAssociations($this->commentAssociationProvider);
    }

    public function testProcessForEntityWithoutCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForEntityWithCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationForUpdateAction(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
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

    public function testProcessForEntityWithCommentsAssociationWhenAssociationIsDisabled(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'exclude' => true
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'exclude'      => true
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationWhenAssociationConfiguredManually(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'data_type' => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'comments' => [
                        'target_class' => Comment::class,
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithCommentsAssociationWhenAssociationReconfiguredToUseAnotherDataType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The association "comments" cannot be added to "Test\Entity"'
            . ' because an association with this name already exists.'
        );

        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'comments' => [
                    'data_type' => 'int'
                ]
            ]
        ]);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);
    }
}
