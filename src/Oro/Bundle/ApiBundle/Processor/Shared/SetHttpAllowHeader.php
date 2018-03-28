<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

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

        $map = $context->hasIdentifierFields()
            ? $this->getHttpMethodToActionsMap()
            : $this->getHttpMethodToActionsMapForResourceWithoutIdentifier();
        $allowedHttpMethods = $this->getAllowedHttpMethods($map, $this->getExcludeActions($context));
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
     * @return array [action => HTTP method, ...]
     */
    protected function getHttpMethodToActionsMapForResourceWithoutIdentifier()
    {
        return [
            self::METHOD_GET    => ApiActions::GET,
            self::METHOD_PATCH  => ApiActions::UPDATE,
            self::METHOD_POST   => ApiActions::CREATE,
            self::METHOD_DELETE => ApiActions::DELETE
        ];
    }

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
     * @param array    $httpMethodToActionsMap
     * @param string[] $excludeActions
     *
     * @return string
     */
    private function getAllowedHttpMethods(array $httpMethodToActionsMap, array $excludeActions)
    {
        $allowedActions = array_diff(array_values($httpMethodToActionsMap), $excludeActions);
        $httpMethodToActionsMap = array_flip($httpMethodToActionsMap);
        $allowedMethods = [];
        foreach ($allowedActions as $action) {
            $allowedMethods[] = $httpMethodToActionsMap[$action];
        }

        return implode(', ', $allowedMethods);
    }
}
