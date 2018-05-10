<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\Rest;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Sets the location of the newly created entity to the "Location" response header.
 */
class SetLocationHeader implements ProcessorInterface
{
    const RESPONSE_HEADER_NAME = 'Location';

    /** @var RouterInterface */
    protected $router;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var string */
    private $getRouteName;

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
        $this->getRouteName = 'oro_rest_api_get';
    }

    /**
     * @param string $routeName
     */
    public function setGetRouteName($routeName)
    {
        $this->getRouteName = $routeName;
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
        $entityId = $this->entityIdTransformer->transform($context->getId(), $metadata);
        $location = $this->router->generate(
            $this->getRouteName,
            ['entity' => $entityType, 'id' => $entityId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $location);
    }
}
