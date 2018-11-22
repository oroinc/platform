<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
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
 * Adds metadata for "self" and "related" HATEOAS links to all associations.
 * @link https://jsonapi.org/format/#document-resource-object-relationships
 */
class AddHateoasLinksForAssociations implements ProcessorInterface
{
    /** @var RestRoutesRegistry */
    private $routesRegistry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /**
     * @param RestRoutesRegistry    $routesRegistry
     * @param UrlGeneratorInterface $urlGenerator
     * @param SubresourcesProvider  $subresourcesProvider
     */
    public function __construct(
        RestRoutesRegistry $routesRegistry,
        UrlGeneratorInterface $urlGenerator,
        SubresourcesProvider $subresourcesProvider
    ) {
        $this->routesRegistry = $routesRegistry;
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

        $routes = $this->routesRegistry->getRoutes($requestType);
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $association) {
            $associationName = $association->getName();
            $subresource = $subresources->getSubresource($associationName);
            if (null === $subresource) {
                continue;
            }

            if (!$association->hasRelationshipLink(ApiDoc::LINK_SELF)
                && !$subresource->isExcludedAction(ApiActions::GET_RELATIONSHIP)
            ) {
                $association->addRelationshipLink(ApiDoc::LINK_SELF, new RouteLinkMetadata(
                    $this->urlGenerator,
                    $routes->getRelationshipRouteName(),
                    [
                        'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                        'id'     => DataAccessorInterface::OWNER_ENTITY_ID
                    ],
                    ['association' => $associationName]
                ));
            }

            if (!$association->hasRelationshipLink(ApiDoc::LINK_RELATED)
                && !$subresource->isExcludedAction(ApiActions::GET_SUBRESOURCE)
            ) {
                $association->addRelationshipLink(ApiDoc::LINK_RELATED, new RouteLinkMetadata(
                    $this->urlGenerator,
                    $routes->getSubresourceRouteName(),
                    [
                        'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                        'id'     => DataAccessorInterface::OWNER_ENTITY_ID
                    ],
                    ['association' => $associationName]
                ));
            }
        }
    }
}
