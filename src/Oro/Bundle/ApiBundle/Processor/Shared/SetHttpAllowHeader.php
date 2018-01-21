<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * A base implementation for processors that set "Allow" HTTP header
 * if the response status code is 405 (Method Not Allowed).
 */
abstract class SetHttpAllowHeader implements ProcessorInterface
{
    const RESPONSE_HEADER_NAME = 'Allow';

    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PATCH  = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /**
     * @param ResourcesProvider $resourcesProvider
     */
    public function __construct(ResourcesProvider $resourcesProvider)
    {
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (Response::HTTP_METHOD_NOT_ALLOWED !== $context->getResponseStatusCode()) {
            return;
        }
        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the header is already added to the response
            return;
        }

        $allowedHttpMethods = $this->getAllowedHttpMethods($this->getExcludeActions($context));
        if ($allowedHttpMethods) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $allowedHttpMethods);
        } else {
            $context->setResponseStatusCode(Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @return array [action => HTTP method, ...]
     */
    abstract protected function getHttpMethodToActionsMap();

    /**
     * @param Context $context
     *
     * @return string[]
     */
    protected function getExcludeActions(Context $context)
    {
        return $this->getExcludeActionsForClass(
            $context->getClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string[]
     */
    protected function getExcludeActionsForClass($entityClass, $version, RequestType $requestType)
    {
        return $this->resourcesProvider->getResourceExcludeActions($entityClass, $version, $requestType);
    }

    /**
     * @param string[] $excludeActions
     *
     * @return string
     */
    private function getAllowedHttpMethods(array $excludeActions)
    {
        $map = $this->getHttpMethodToActionsMap();
        $allowedActions = array_diff(array_values($map), $excludeActions);

        $map = array_flip($map);
        $allowedMethods = [];
        foreach ($allowedActions as $action) {
            $allowedMethods[] = $map[$action];
        }

        return implode(', ', $allowedMethods);
    }
}
