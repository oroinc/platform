<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The registry that allows to get the error completer for a specific request type.
 * The implementation of this registry assumes that all error completers
 * are declared in DIC as public services.
 * @see \Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ErrorCompleterCompilerPass
 */
class ErrorCompleterRegistry
{
    /** @var array [[error completer service id, request type expression], ...] */
    private $errorCompleters;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

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
     * @param RequestType $requestType
     *
     * @return ErrorCompleterInterface
     *
     * @throws \LogicException if an error completer does not exist for the given request type
     */
    public function getErrorCompleter(RequestType $requestType): ErrorCompleterInterface
    {
        $errorCompleterServiceId = null;
        foreach ($this->errorCompleters as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $errorCompleterServiceId = $serviceId;
                break;
            }
        }
        if (null === $errorCompleterServiceId) {
            throw new \LogicException(
                sprintf('Cannot find an error completer for the request "%s".', (string)$requestType)
            );
        }

        return $this->container->get($errorCompleterServiceId);
    }
}
