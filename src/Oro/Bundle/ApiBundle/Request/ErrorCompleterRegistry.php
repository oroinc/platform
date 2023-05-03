<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the error completer for a specific request type.
 */
class ErrorCompleterRegistry
{
    /** @var array [[error completer service id, request type expression], ...] */
    private array $errorCompleters;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $errorCompleters [[error completer service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $errorCompleters,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->errorCompleters = $errorCompleters;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Creates a new instance of ErrorCompleterInterface
     * responsible to build a document for the specific request type.
     *
     * @throws \LogicException if an error completer does not exist for the given request type
     */
    public function getErrorCompleter(RequestType $requestType): ErrorCompleterInterface
    {
        $errorCompleterServiceId = null;
        foreach ($this->errorCompleters as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $errorCompleterServiceId = $serviceId;
                break;
            }
        }
        if (null === $errorCompleterServiceId) {
            throw new \LogicException(sprintf(
                'Cannot find an error completer for the request "%s".',
                (string)$requestType
            ));
        }

        return $this->container->get($errorCompleterServiceId);
    }
}
