<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\Rest;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Sets the location of the newly created entity to the "Location" response header.
 */
class SetLocationHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'Location';

    /** @var RouterInterface */
    protected $router;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param RouterInterface              $router
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        RouterInterface $router,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->router = $router;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the Location header is already set
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

        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $context->getClassName(),
            $context->getRequestType()
        );

        $entityId = $this->entityIdTransformer->transform($entityId, $metadata);

        $location = $this->router->generate(
            'oro_rest_api_item',
            ['entity' => $entityType, 'id' => $entityId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $location);
    }
}
