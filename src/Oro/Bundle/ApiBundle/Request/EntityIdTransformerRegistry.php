<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all entity identifier value transformers
 * and allows to get a transformer suitable for a specific request type.
 */
class EntityIdTransformerRegistry
{
    /** @var array [[transformer service id, request type expression], ...] */
    private array $transformers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    private EntityIdResolverRegistry $resolverRegistry;
    /** @var array [request type => EntityIdTransformerInterface, ...] */
    private array $cache = [];

    public function __construct(
        array $transformers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher,
        EntityIdResolverRegistry $resolverRegistry
    ) {
        $this->transformers = $transformers;
        $this->container = $container;
        $this->matcher = $matcher;
        $this->resolverRegistry = $resolverRegistry;
    }

    /**
     * Gets entity identifier value transformer for the given request type.
     */
    public function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        $cacheKey = (string)$requestType;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $entityIdTransformer = null;
        foreach ($this->transformers as [$serviceId, $expression]) {
            if ($this->matcher->matchValue($expression, $requestType)) {
                $entityIdTransformer = $this->container->get($serviceId);
                break;
            }
        }
        if (null === $entityIdTransformer) {
            $entityIdTransformer = NullEntityIdTransformer::getInstance();
        }
        $entityIdTransformer = new CombinedEntityIdTransformer(
            $entityIdTransformer,
            $this->resolverRegistry,
            $requestType
        );

        $this->cache[$cacheKey] = $entityIdTransformer;

        return $entityIdTransformer;
    }
}
