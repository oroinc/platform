<?php

namespace Oro\Bundle\UserBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds metadata for HATEOAS links to the user profile API resource.
 */
class AddUserProfileHateoasLinks implements ProcessorInterface
{
    private RestRoutesRegistry $routesRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private ValueNormalizer $valueNormalizer;
    private SubresourcesProvider $subresourcesProvider;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        UrlGeneratorInterface $urlGenerator,
        ValueNormalizer $valueNormalizer,
        SubresourcesProvider $subresourcesProvider
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->valueNormalizer = $valueNormalizer;
        $this->subresourcesProvider = $subresourcesProvider;
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
        $userEntityType = ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, User::class, $requestType);
        $routes = $this->routesRegistry->getRoutes($requestType);

        $this->addHateoasLinksForEntity($entityMetadata, $routes, $userEntityType);

        $subresources = $this->subresourcesProvider->getSubresources(User::class, $context->getVersion(), $requestType);
        if (null !== $subresources) {
            $this->addHateoasLinksForAssociations($entityMetadata, $routes, $userEntityType, $subresources);
        }
    }

    private function addHateoasLinksForEntity(
        EntityMetadata $entityMetadata,
        RestRoutes $routes,
        string $userEntityType
    ): void {
        $entityMetadata->addLink(ApiDoc::LINK_SELF, new RouteLinkMetadata(
            $this->urlGenerator,
            $routes->getListRouteName(),
            ['entity' => DataAccessorInterface::ENTITY_TYPE]
        ));
        $entityMetadata->addLink(ApiDoc::LINK_RELATED, new RouteLinkMetadata(
            $this->urlGenerator,
            $routes->getItemRouteName(),
            ['id' => DataAccessorInterface::ENTITY_ID],
            ['entity' => $userEntityType]
        ));
    }

    private function addHateoasLinksForAssociations(
        EntityMetadata $entityMetadata,
        RestRoutes $routes,
        string $userEntityType,
        ApiResourceSubresources $subresources
    ): void {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $association) {
            $associationName = $association->getName();
            $subresource = $subresources->getSubresource($associationName);
            if (null === $subresource) {
                continue;
            }

            if (!$subresource->isExcludedAction(ApiAction::GET_RELATIONSHIP)) {
                $association->addRelationshipLink(ApiDoc::LINK_SELF, new RouteLinkMetadata(
                    $this->urlGenerator,
                    $routes->getRelationshipRouteName(),
                    ['id' => DataAccessorInterface::OWNER_ENTITY_ID],
                    ['entity' => $userEntityType, 'association' => $associationName]
                ));
            }

            if (!$subresource->isExcludedAction(ApiAction::GET_SUBRESOURCE)) {
                $association->addRelationshipLink(ApiDoc::LINK_RELATED, new RouteLinkMetadata(
                    $this->urlGenerator,
                    $routes->getSubresourceRouteName(),
                    ['id' => DataAccessorInterface::OWNER_ENTITY_ID],
                    ['entity' => $userEntityType, 'association' => $associationName]
                ));
            }
        }
    }
}
