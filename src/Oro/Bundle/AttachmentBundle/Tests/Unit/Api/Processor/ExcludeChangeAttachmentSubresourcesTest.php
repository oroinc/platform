<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Bundle\AttachmentBundle\Api\Processor\ExcludeChangeAttachmentSubresources;

class ExcludeChangeAttachmentSubresourcesTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationProvider;

    /** @var ExcludeChangeAttachmentSubresources */
    private $processor;

    /** @var CollectSubresourcesContext */
    private $context;

    protected function setUp(): void
    {
        $this->attachmentAssociationProvider = $this->createMock(AttachmentAssociationProvider::class);

        $this->processor = new ExcludeChangeAttachmentSubresources($this->attachmentAssociationProvider);

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

        $this->attachmentAssociationProvider->expects(self::never())
            ->method('getAttachmentAssociationName');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutAttachmentsAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
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

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult(new ApiResourceSubresourcesCollection());
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutAttachmentsSubresource(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn('association1');

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithAttachmentsSubresource(): void
    {
        $entityClass = 'Test\Entity';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $attachmentsSubresource = new ApiSubresource();
        $attachmentsSubresource->addExcludedAction(ApiAction::UPDATE_SUBRESOURCE);
        $attachmentsSubresource->addExcludedAction(ApiAction::ADD_SUBRESOURCE);
        $attachmentsSubresource->addExcludedAction(ApiAction::DELETE_SUBRESOURCE);
        $subresources->get($entityClass)->addSubresource('attachments', $attachmentsSubresource);

        $this->attachmentAssociationProvider->expects(self::once())
            ->method('getAttachmentAssociationName')
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
            $attachmentsSubresource->getExcludedActions()
        );
    }
}
