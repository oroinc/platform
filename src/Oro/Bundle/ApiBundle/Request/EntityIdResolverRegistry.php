<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains resolvers for all predefined identifiers of API resources
 * and allows to get a transformer suitable for a specific predefined identifier, entity and request type.
 */
class EntityIdResolverRegistry
{
    /** @var array [entity id => [entity class => [resolver service id, request type expression], ...], ...] */
    private array $resolvers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    public function __construct(
        array $resolvers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->resolvers = $resolvers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets an instance of EntityIdResolverInterface that can resolve
     * the given predefined identifier for the given entity and the request type.
     *
     * @param string      $entityId    A predefined identifier of an entity
     * @param string      $entityClass The FQCN of an entity
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return EntityIdResolverInterface|null
     */
    public function getResolver(
        string $entityId,
        string $entityClass,
        RequestType $requestType
    ): ?EntityIdResolverInterface {
        if (isset($this->resolvers[$entityId][$entityClass])) {
            foreach ($this->resolvers[$entityId][$entityClass] as [$serviceId, $expression]) {
                if ($this->isMatched($expression, $requestType)) {
                    return $this->instantiateResolver($serviceId);
                }
            }
        }

        return null;
    }

    /**
     * Gets descriptions of all predefined identifiers of API resources
     * that can be resolved by all registered resolvers for the given request type.
     * These descriptions are used in auto-generated documentation, including API sandbox.
     *
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[]
     */
    public function getDescriptions(RequestType $requestType): array
    {
        $descriptions = [];
        foreach ($this->resolvers as $idData) {
            foreach ($idData as $classData) {
                foreach ($classData as [$serviceId, $expression]) {
                    if ($this->isMatched($expression, $requestType)) {
                        $descriptions[] = $this->instantiateResolver($serviceId)->getDescription();
                    }
                }
            }
        }

        return $descriptions;
    }

    private function isMatched(mixed $expression, RequestType $requestType): bool
    {
        return !$expression || $this->matcher->matchValue($expression, $requestType);
    }

    private function instantiateResolver(string $serviceId): EntityIdResolverInterface
    {
        return $this->container->get($serviceId);
    }
}
