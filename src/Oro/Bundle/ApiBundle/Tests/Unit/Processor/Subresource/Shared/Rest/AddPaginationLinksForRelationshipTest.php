<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FirstPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\PrevPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest\AddPaginationLinksForRelationship;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddPaginationLinksForRelationshipTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $urlGenerator;

    /** @var AddPaginationLinksForRelationship */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getPageNumberFilterName')
            ->willReturn('page[number]');
        $routes = new RestRoutes('item', 'list', 'subresource', 'relationship');
        $requestExpressionMatcher = new RequestExpressionMatcher();

        $this->processor = new AddPaginationLinksForRelationship(
            new RestRoutesRegistry([[$routes, null]], $requestExpressionMatcher),
            new FilterNamesRegistry([[$filterNames, null]], $requestExpressionMatcher),
            $this->urlGenerator
        );
    }

    public function testProcessWhenNoDocumentBuilder()
    {
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

    public function testProcessForSuccessResponse()
    {
        $parentClassName = 'Test\Entity';
        $parentEntityId = '_123';
        $associationName = 'testAssociation';
        $parentEntityAlias = 'test_entity';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $pageNumberFilterName = 'page[number]';
        $queryStringAccessor = $this->context->getFilterValues();
        $expectedBaseLink = new RouteLinkMetadata(
            $this->urlGenerator,
            'relationship',
            [],
            ['entity' => $parentEntityAlias, 'id' => $parentEntityId, 'association' => $associationName]
        );
        $expectedFirstPageLink = new FirstPageLinkMetadata(
            $expectedBaseLink,
            $pageNumberFilterName,
            $queryStringAccessor
        );
        $expectedPrevPageLink = new PrevPageLinkMetadata(
            $expectedBaseLink,
            $pageNumberFilterName,
            $queryStringAccessor
        );
        $expectedNextPageLink = new NextPageLinkMetadata(
            $expectedBaseLink,
            $pageNumberFilterName,
            $queryStringAccessor
        );

        $documentBuilder->expects(self::once())
            ->method('getEntityAlias')
            ->with($parentClassName, $this->context->getRequestType())
            ->willReturn($parentEntityAlias);
        $documentBuilder->expects(self::once())
            ->method('getEntityId')
            ->with(123, $this->context->getRequestType())
            ->willReturn($parentEntityId);
        $documentBuilder->expects(self::exactly(3))
            ->method('addLinkMetadata')
            ->withConsecutive(
                ['first', $expectedFirstPageLink],
                ['prev', $expectedPrevPageLink],
                ['next', $expectedNextPageLink]
            );

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId(123);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata());
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }
}
