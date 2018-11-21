<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\AddHateoasLinks;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddHateoasLinksTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $urlGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var AddHateoasLinks */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $routes = new RestRoutes('item', 'list', 'subresource', 'relationship');

        $this->processor = new AddHateoasLinks(
            new RestRoutesRegistry([[$routes, null]], new RequestExpressionMatcher()),
            $this->urlGenerator,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenNoDocumentBuilder()
    {
        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setClassName('Test\Entity');
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForNotSuccessResponse()
    {
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

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
        $excludeActions = [];

        $expectedSelfLink = new RouteLinkMetadata(
            $this->urlGenerator,
            'list',
            [],
            ['entity' => $entityAlias]
        );

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($excludeActions);

        $documentBuilder->expects(self::once())
            ->method('getEntityAlias')
            ->with($entityClass, $this->context->getRequestType())
            ->willReturn($entityAlias);
        $documentBuilder->expects(self::once())
            ->method('addLinkMetadata')
            ->with('self', $expectedSelfLink);

        $this->context->setClassName($entityClass);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }

    public function testProcessForSuccessResponseButGetListActionIsDisabled()
    {
        $entityClass = 'Test\Entity';
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $excludeActions = [ApiActions::GET_LIST];

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($excludeActions);

        $documentBuilder->expects(self::never())
            ->method('getEntityAlias');
        $documentBuilder->expects(self::never())
            ->method('addLinkMetadata');

        $this->context->setClassName($entityClass);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);
    }
}
