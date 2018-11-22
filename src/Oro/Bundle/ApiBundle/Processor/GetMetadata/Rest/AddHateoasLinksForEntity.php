<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
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
    /** @var RestRoutesRegistry */
    private $routesRegistry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * @param RestRoutesRegistry    $routesRegistry
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(RestRoutesRegistry $routesRegistry, UrlGeneratorInterface $urlGenerator)
    {
        $this->routesRegistry = $routesRegistry;
        $this->urlGenerator = $urlGenerator;
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

        if (!$entityMetadata->hasLink(ApiDoc::LINK_SELF)) {
            $entityMetadata->addLink(ApiDoc::LINK_SELF, new RouteLinkMetadata(
                $this->urlGenerator,
                $this->routesRegistry->getRoutes($context->getRequestType())->getItemRouteName(),
                [
                    'entity' => DataAccessorInterface::ENTITY_TYPE,
                    'id'     => DataAccessorInterface::ENTITY_ID
                ]
            ));
        }
    }
}
