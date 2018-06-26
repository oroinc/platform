<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains all entity identifier value transformers
 * and allows to get a transformer suitable for a specific request type.
 */
class EntityIdTransformerRegistry
{
    /** @var array [[transformer service id, request type expression], ...] */
    private $transformers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var EntityIdResolverRegistry */
    private $resolverRegistry;

    /** @var array [request type => EntityIdTransformerInterface, ...] */
    private $cache = [];

    /**
     * @param array                    $transformers
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     * @param EntityIdResolverRegistry $resolverRegistry
     */
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
     *
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    public function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        $cacheKey = (string)$requestType;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $entityIdTransformer = null;
        foreach ($this->transformers as list($serviceId, $expression)) {
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
