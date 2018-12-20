<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiActions;
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
    /** @var RestRoutesRegistry */
    private $routesRegistry;

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /**
     * @param RestRoutesRegistry    $routesRegistry
     * @param FilterNamesRegistry   $filterNamesRegistry
     * @param UrlGeneratorInterface $urlGenerator
     * @param SubresourcesProvider  $subresourcesProvider
     */
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
                && !$subresource->isExcludedAction(ApiActions::GET_RELATIONSHIP)
            ) {
                $association->addRelationshipLink(
                    ApiDoc::LINK_NEXT,
                    new NextPageLinkMetadata(
                        $this->getRelationshipLinkMetadata($relationshipRouteName, $associationName),
                        $pageNumberFilterName
                    )
                );
            }
        }
    }

    /**
     * @param string $relationshipRouteName
     * @param string $associationName
     *
     * @return string
     */
    private function getRelationshipLinkMetadata(
        string $relationshipRouteName,
        string $associationName
    ): RouteLinkMetadata {
        return new RouteLinkMetadata(
            $this->urlGenerator,
            $relationshipRouteName,
            [
                'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                'id'     => DataAccessorInterface::OWNER_ENTITY_ID
            ],
            ['association' => $associationName]
        );
    }
}
