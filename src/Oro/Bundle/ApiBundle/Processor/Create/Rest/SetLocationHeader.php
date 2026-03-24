<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\Rest;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sets the location of the newly created entity to the "Location" response header.
 */
class SetLocationHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'Location';

    private RestRoutesRegistry $routesRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private ValueNormalizer $valueNormalizer;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        UrlGeneratorInterface $urlGenerator,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the Location header is already set
            return;
        }

        if ($context->isExisting()) {
            // the Location header is needed only when a new entity was created
            return;
        }

        if ($context->getResponseStatusCode() === Response::HTTP_OK) {
            // the Location header is not needed for 200 (OK) response status code
            return;
        }

        $entityId = $context->getId();
        if (null === $entityId) {
            // an entity id does not exist
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // the metadata does not exist
            return;
        }

        $requestType = $context->getRequestType();
        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $context->getClassName(),
            $requestType
        );
        $entityId = $this->getEntityIdTransformer($requestType)->transform($entityId, $metadata);

        $location = $this->urlGenerator->generate(
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            ['entity' => $entityType, 'id' => $entityId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $location);
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
