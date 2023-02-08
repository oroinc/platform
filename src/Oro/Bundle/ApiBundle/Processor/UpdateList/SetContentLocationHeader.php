<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Sets the URL of GET API resource of the asynchronous operation to the "Content-Location" response header.
 */
class SetContentLocationHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'Content-Location';

    private RestRoutesRegistry $routesRegistry;
    private RouterInterface $router;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        RouterInterface $router,
        ValueNormalizer $valueNormalizer
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->router = $router;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the Content-Location header is already set
            return;
        }

        $operationId = $context->getOperationId();
        if (null === $operationId) {
            return;
        }

        $requestType = $context->getRequestType();
        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            AsyncOperation::class,
            $requestType
        );

        $location = $this->router->generate(
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            ['entity' => $entityType, 'id' => (string)$operationId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $location);
    }
}
