<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Twig;

use Psr\Container\ContainerInterface;

/**
 * Caches instantiated services in memory to speed up getting they.
 */
class TwigServiceLocatorDecorator implements ContainerInterface
{
    private array $services = [];

    public function __construct(
        private readonly ContainerInterface $innerServiceLocator
    ) {
    }

    #[\Override]
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        $service = $this->innerServiceLocator->get($id);
        $this->services[$id] = $service;

        return $service;
    }

    #[\Override]
    public function has(string $id): bool
    {
        return $this->innerServiceLocator->has($id);
    }
}
