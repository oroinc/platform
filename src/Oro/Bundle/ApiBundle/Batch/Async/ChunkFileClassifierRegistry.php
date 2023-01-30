<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the chunk file classifier for a specific request type.
 */
class ChunkFileClassifierRegistry
{
    /** @var array [[classifier service id, request type expression], ...] */
    private array $classifiers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $classifiers [[classifier service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $classifiers, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->classifiers = $classifiers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the chunk file classifier for the given request type.
     */
    public function getClassifier(RequestType $requestType): ?ChunkFileClassifierInterface
    {
        foreach ($this->classifiers as [$serviceId, $expression]) {
            if ($this->matcher->matchValue($expression, $requestType)) {
                return $this->container->get($serviceId);
            }
        }

        return null;
    }
}
