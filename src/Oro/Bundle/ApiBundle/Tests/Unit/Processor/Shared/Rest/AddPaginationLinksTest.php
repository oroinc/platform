<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\FirstPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\PrevPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\AddPaginationLinks;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddPaginationLinksTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $urlGenerator;

    /** @var AddPaginationLinks */
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

        $this->processor = new AddPaginationLinks(
            new RestRoutesRegistry([[$routes, null]], $requestExpressionMatcher),
            new FilterNamesRegistry([[$filterNames, null]], $requestExpressionMatcher),
            $this->urlGenerator
        );
    }

    public function testProcessWhenNoDocumentBuilder()
    {
        $this->context->setClassName('Test\Entity');
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForNotSuccessResponse()
    {
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $documentBuilder->expects(self::never())
            ->method('getEntityAlias');
        $documentBuilder->expects(self::never())
            ->method('addLinkMetadata');

        $this->context->setClassName('Test\Entity');
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->processor->process($this->context);
    }

    public function testProcessForSuccessResponse()
    {
        $entityClass = 'Test\Entity';
        $entityAlias = 'test_entity';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $pageNumberFilterName = 'page[number]';
        $queryStringAccessor = $this->context->getFilterValues();
        $expectedBaseLink = new RouteLinkMetadata(
            $this->urlGenerator,
            'list',
            [],
            ['entity' => $entityAlias]
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
            ->with($entityClass, $this->context->getRequestType())
            ->willReturn($entityAlias);
        $documentBuilder->expects(self::exactly(3))
            ->method('addLinkMetadata')
            ->withConsecutive(
                ['first', $expectedFirstPageLink],
                ['prev', $expectedPrevPageLink],
                ['next', $expectedNextPageLink]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }
}
