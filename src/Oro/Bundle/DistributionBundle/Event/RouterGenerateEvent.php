<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Event;

use Oro\Bundle\DistributionBundle\Routing\Router;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a route is being generated.
 * Allows to modify the route name, parameters, and reference type.
 *
 * {@see Router}
 */
class RouterGenerateEvent extends Event
{
    public function __construct(
        private string $routeName,
        private array $parameters,
        private int $referenceType
    ) {
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameter(string $key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    public function setParameter(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Remove a route parameter by key.
     */
    public function removeParameter(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * The type of reference to be generated (one of the constants in UrlGeneratorInterface).
     */
    public function getReferenceType(): int
    {
        return $this->referenceType;
    }

    /**
     * The type of reference to be generated (one of the constants in UrlGeneratorInterface).
     */
    public function setReferenceType(int $referenceType): void
    {
        $this->referenceType = $referenceType;
    }
}
