<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds "self" HATEOAS link to a whole document of success response.
 * @link https://jsonapi.org/recommendations/#including-links
 */
class AddHateoasLinks implements ProcessorInterface
{
    /** @var RestRoutesRegistry */
    private $routesRegistry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /**
     * @param RestRoutesRegistry    $routesRegistry
     * @param UrlGeneratorInterface $urlGenerator
     * @param ResourcesProvider     $resourcesProvider
     */
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
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder || !$context->isSuccessResponse()) {
            return;
        }

        $requestType = $context->getRequestType();
        $entityClass = $context->getClassName();
        if (ApiActions::GET_LIST !== $context->getAction()
            && $this->isGetListActionExcluded($entityClass, $context->getVersion(), $requestType)
        ) {
            return;
        }

        $documentBuilder->addLinkMetadata(ApiDoc::LINK_SELF, new RouteLinkMetadata(
            $this->urlGenerator,
            $this->routesRegistry->getRoutes($requestType)->getListRouteName(),
            [],
            ['entity' => $documentBuilder->getEntityAlias($entityClass, $requestType)]
        ));
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return bool
     */
    private function isGetListActionExcluded(string $entityClass, string $version, RequestType $requestType): bool
    {
        return \in_array(
            ApiActions::GET_LIST,
            $this->resourcesProvider->getResourceExcludeActions($entityClass, $version, $requestType),
            true
        );
    }
}
