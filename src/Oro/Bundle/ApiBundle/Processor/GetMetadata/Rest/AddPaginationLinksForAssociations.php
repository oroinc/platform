<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds metadata for "next" pagination link to all to-many associations.
 * @link https://jsonapi.org/format/#fetching-pagination
 */
class AddPaginationLinksForAssociations implements ProcessorInterface
{
    private RestRoutesRegistry $routesRegistry;
    private FilterNamesRegistry $filterNamesRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private SubresourcesProvider $subresourcesProvider;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        FilterNamesRegistry $filterNamesRegistry,
        UrlGeneratorInterface $urlGenerator,
        SubresourcesProvider $subresourcesProvider
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->subresourcesProvider = $subresourcesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            // metadata is not loaded
            return;
        }

        $requestType = $context->getRequestType();
        $subresources = $this->subresourcesProvider->getSubresources(
            $entityMetadata->getClassName(),
            $context->getVersion(),
            $requestType
        );
        if (null === $subresources) {
            return;
        }

        $relationshipRouteName = $this->routesRegistry
            ->getRoutes($requestType)
            ->getRelationshipRouteName();
        $pageNumberFilterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getPageNumberFilterName();

        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $association) {
            if (!$association->isCollection()) {
                continue;
            }

            $associationName = $association->getName();
            $subresource = $subresources->getSubresource($associationName);
            if (null === $subresource) {
                continue;
            }

            if (!$association->hasRelationshipLink(ApiDoc::LINK_NEXT)
                && !$subresource->isExcludedAction(ApiAction::GET_RELATIONSHIP)
            ) {
                $association->addRelationshipLink(ApiDoc::LINK_NEXT, new NextPageLinkMetadata(
                    new RouteLinkMetadata(
                        $this->urlGenerator,
                        $relationshipRouteName,
                        [
                            'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                            'id'     => DataAccessorInterface::OWNER_ENTITY_ID
                        ],
                        ['association' => $associationName]
                    ),
                    $pageNumberFilterName
                ));
            }
        }
    }
}
