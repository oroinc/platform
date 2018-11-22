<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest\AddHateoasLinksForSubresource;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddHateoasLinksForSubresourceTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $urlGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SubresourcesProvider */
    private $subresourcesProvider;

    /** @var AddHateoasLinksForSubresource */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->subresourcesProvider = $this->createMock(SubresourcesProvider::class);
        $routes = new RestRoutes('item', 'list', 'subresource', 'relationship');

        $this->processor = new AddHateoasLinksForSubresource(
            new RestRoutesRegistry([[$routes, null]], new RequestExpressionMatcher()),
            $this->urlGenerator,
            $this->subresourcesProvider
        );
    }

    public function testProcessWhenNoDocumentBuilder()
    {
        $this->subresourcesProvider->expects(self::never())
            ->method('getSubresource');

        $this->context->setParentClassName('Test\Entity');
        $this->context->setParentId(123);
        $this->context->setAssociationName('testAssociation');
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForNotSuccessResponse()
    {
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $this->subresourcesProvider->expects(self::never())
            ->method('getSubresource');

        $documentBuilder->expects(self::never())
            ->method('getEntityAlias');
        $documentBuilder->expects(self::never())
            ->method('getEntityId');
        $documentBuilder->expects(self::never())
            ->method('addLinkMetadata');

        $this->context->setParentClassName('Test\Entity');
        $this->context->setParentId(123);
        $this->context->setAssociationName('testAssociation');
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoSubresource()
    {
        $parentClassName = 'Test\Entity';
        $associationName = 'testAssociation';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with(
                $parentClassName,
                $associationName,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(null);

        $documentBuilder->expects(self::never())
            ->method('getEntityAlias');
        $documentBuilder->expects(self::never())
            ->method('getEntityId');
        $documentBuilder->expects(self::never())
            ->method('addLinkMetadata');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId(123);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForExcludedSubresource()
    {
        $parentClassName = 'Test\Entity';
        $associationName = 'testAssociation';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $subresource = new ApiSubresource();
        $subresource->addExcludedAction(ApiActions::GET_SUBRESOURCE);

        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with(
                $parentClassName,
                $associationName,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($subresource);

        $documentBuilder->expects(self::never())
            ->method('getEntityAlias');
        $documentBuilder->expects(self::never())
            ->method('getEntityId');
        $documentBuilder->expects(self::never())
            ->method('addLinkMetadata');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId(123);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForSuccessResponse()
    {
        $parentClassName = 'Test\Entity';
        $parentEntityId = '_123';
        $associationName = 'testAssociation';
        $parentEntityAlias = 'test_entity';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $expectedSelfLink = new RouteLinkMetadata(
            $this->urlGenerator,
            'subresource',
            [],
            ['entity' => $parentEntityAlias, 'id' => $parentEntityId, 'association' => $associationName]
        );

        $subresource = new ApiSubresource();

        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with(
                $parentClassName,
                $associationName,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($subresource);

        $documentBuilder->expects(self::once())
            ->method('getEntityAlias')
            ->with($parentClassName, $this->context->getRequestType())
            ->willReturn($parentEntityAlias);
        $documentBuilder->expects(self::once())
            ->method('getEntityId')
            ->with(123, $this->context->getRequestType())
            ->willReturn($parentEntityId);
        $documentBuilder->expects(self::once())
            ->method('addLinkMetadata')
            ->with('self', $expectedSelfLink);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId(123);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }
}
