<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Sorter;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry that allows to get a sorter for API resource filters for a specific request type.
 */
class FiltersSorterRegistry implements ResetInterface
{
    /** @var array [[sorter service id, request type expression], ...] */
    private array $sorters;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    /** @var FiltersSorterInterface[] [request type => FiltersSorterInterface, ...] */
    private array $cache = [];

    /**
     * @param array                    $sorters [[sorter service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $sorters,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->sorters = $sorters;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns a sorter for the given request type.
     */
    public function getSorter(RequestType $requestType): ?FiltersSorterInterface
    {
        $cacheKey = (string)$requestType;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var FiltersSorterInterface|null $sorter */
        $sorter = null;
        foreach ($this->sorters as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $sorter = $this->container->get($serviceId);
                break;
            }
        }

        $this->cache[$cacheKey] = $sorter;

        return $sorter;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cache = [];
    }
}
