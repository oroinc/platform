<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds "self" HATEOAS link to a whole document of success response for a subresource.
 * @link https://jsonapi.org/recommendations/#including-links
 */
class AddHateoasLinksForSubresource implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder || !$context->isSuccessResponse()) {
            return;
        }

        $parentMetadata = $context->getParentMetadata();
        if (null === $parentMetadata) {
            return;
        }

        $requestType = $context->getRequestType();
        $subresource = $this->subresourcesProvider->getSubresource(
            $context->getParentClassName(),
            $context->getAssociationName(),
            $context->getVersion(),
            $requestType
        );
        if (null === $subresource || $subresource->isExcludedAction(ApiActions::GET_SUBRESOURCE)) {
            return;
        }

        $parentEntityAlias = $documentBuilder->getEntityAlias($context->getParentClassName(), $requestType);
        $parentEntityId = $documentBuilder->getEntityId($context->getParentId(), $requestType, $parentMetadata);
        $documentBuilder->addLinkMetadata(ApiDoc::LINK_SELF, new RouteLinkMetadata(
            $this->urlGenerator,
            $this->routesRegistry->getRoutes($requestType)->getSubresourceRouteName(),
            [],
            [
                'entity'      => $parentEntityAlias,
                'id'          => $parentEntityId,
                'association' => $context->getAssociationName()
            ]
        ));
    }
}
