<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds metadata for "self" HATEOAS link to an entity.
 * @link https://jsonapi.org/format/#document-resource-object-linkage
 */
class AddHateoasLinksForEntity implements ProcessorInterface
{
    private RestRoutesRegistry $routesRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private ResourcesProvider $resourcesProvider;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        UrlGeneratorInterface $urlGenerator,
        ResourcesProvider $resourcesProvider
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritdoc}
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
        if (!$entityMetadata->hasLink(ApiDoc::LINK_SELF)
            && !$this->isGetActionExcluded($entityMetadata->getClassName(), $context->getVersion(), $requestType)
        ) {
            $entityMetadata->addLink(ApiDoc::LINK_SELF, new RouteLinkMetadata(
                $this->urlGenerator,
                $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
                [
                    'entity' => DataAccessorInterface::ENTITY_TYPE,
                    'id'     => DataAccessorInterface::ENTITY_ID
                ]
            ));
        }
    }

    private function isGetActionExcluded(string $entityClass, string $version, RequestType $requestType): bool
    {
        return \in_array(
            ApiAction::GET,
            $this->resourcesProvider->getResourceExcludeActions($entityClass, $version, $requestType),
            true
        );
    }
}
