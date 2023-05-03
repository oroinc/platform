<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\FirstPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\PrevPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The base class for processors that add pagination links to response document.
 */
abstract class AbstractAddPaginationLinks implements ProcessorInterface
{
    private RestRoutesRegistry $routesRegistry;
    private FilterNamesRegistry $filterNamesRegistry;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        FilterNamesRegistry $filterNamesRegistry,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->urlGenerator = $urlGenerator;
    }

    protected function getRoutes(RequestType $requestType): RestRoutes
    {
        return $this->routesRegistry->getRoutes($requestType);
    }

    protected function getFilterNames(RequestType $requestType): FilterNames
    {
        return $this->filterNamesRegistry->getFilterNames($requestType);
    }

    protected function getRouteLinkMetadata(string $routeName, array $defaultParams = []): RouteLinkMetadata
    {
        return new RouteLinkMetadata(
            $this->urlGenerator,
            $routeName,
            [],
            $defaultParams
        );
    }

    protected function addLinks(
        DocumentBuilderInterface $documentBuilder,
        LinkMetadataInterface $baseLink,
        string $pageNumberFilterName,
        QueryStringAccessorInterface $queryStringAccessor
    ): void {
        $documentBuilder->addLinkMetadata(
            ApiDoc::LINK_FIRST,
            new FirstPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
        $documentBuilder->addLinkMetadata(
            ApiDoc::LINK_PREV,
            new PrevPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
        $documentBuilder->addLinkMetadata(
            ApiDoc::LINK_NEXT,
            new NextPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
    }
}
