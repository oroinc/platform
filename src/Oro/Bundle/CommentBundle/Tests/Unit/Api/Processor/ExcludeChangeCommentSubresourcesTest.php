<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Bundle\CommentBundle\Api\Processor\ExcludeChangeCommentSubresources;

class ExcludeChangeCommentSubresourcesTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $commentAssociationProvider;

    /** @var ExcludeChangeCommentSubresources */
    private $processor;

    /** @var CollectSubresourcesContext */
    private $context;

    protected function setUp(): void
    {
        $this->commentAssociationProvider = $this->createMock(CommentAssociationProvider::class);

        $this->processor = new ExcludeChangeCommentSubresources($this->commentAssociationProvider);

        $this->context = new CollectSubresourcesContext();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->setVersion('1.1');
    }

    private function getApiResourceSubresources(ApiResource $resource): ApiResourceSubresourcesCollection
    {
        $entitySubresources = new ApiResourceSubresources($resource->getEntityClass());
        $subresources = new ApiResourceSubresourcesCollection();
        $subresources->add($entitySubresources);

        return $subresources;
    }

    public function testProcessForDisabledSubresources(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $resource->setExcludedActions([ApiAction::GET_SUBRESOURCE]);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->commentAssociationProvider->expects(self::never())
            ->method('getCommentAssociationName');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutCommentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutSubresources(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult(new ApiResourceSubresourcesCollection());
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutCommentsSubresource(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithCommentsSubresource(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $commentsSubresource = new ApiSubresource();
        $commentsSubresource->addExcludedAction(ApiAction::UPDATE_SUBRESOURCE);
        $commentsSubresource->addExcludedAction(ApiAction::ADD_SUBRESOURCE);
        $commentsSubresource->addExcludedAction(ApiAction::DELETE_SUBRESOURCE);
        $subresources->get($entityClass)->addSubresource('comments', $commentsSubresource);

        $this->commentAssociationProvider->expects(self::once())
            ->method('getCommentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                ApiAction::UPDATE_SUBRESOURCE,
                ApiAction::ADD_SUBRESOURCE,
                ApiAction::DELETE_SUBRESOURCE,
                ApiAction::UPDATE_RELATIONSHIP,
                ApiAction::ADD_RELATIONSHIP,
                ApiAction::DELETE_RELATIONSHIP
            ],
            $commentsSubresource->getExcludedActions()
        );
    }
}
