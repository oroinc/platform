<?php

/*
 * This file is a copy of {@see Symfony\Bundle\FrameworkBundle\Test\TestContainer}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\MigrationBundle\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\Container as DependencyInjectionContainer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Provides access to the private services in the migrations and fixtures.
 * Must be used carefully and only for migration loading.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class MigrationContainer extends DependencyInjectionContainer
{
    /** @var DependencyInjectionContainer */
    private $publicContainer;

    /** @var PsrContainerInterface */
    private $privateContainer;

    public function __construct(
        ?ParameterBagInterface $parameterBag,
        DependencyInjectionContainer $publicContainer,
        PsrContainerInterface $privateContainer
    ) {
        $this->parameterBag = $parameterBag ?? $publicContainer->getParameterBag();
        $this->publicContainer = $publicContainer;
        $this->privateContainer = $privateContainer;
    }

    #[\Override]
    public function compile()
    {
        $this->publicContainer->compile();
    }

    #[\Override]
    public function isCompiled(): bool
    {
        return $this->publicContainer->isCompiled();
    }

    #[\Override]
    public function set(string $id, ?object $service)
    {
        $this->publicContainer->set($id, $service);
    }

    #[\Override]
    public function has(string $id): bool
    {
        return $this->publicContainer->has($id) || $this->privateContainer->has($id);
    }

    #[\Override]
    public function get($id, $invalidBehavior = /* self::EXCEPTION_ON_INVALID_REFERENCE */ 1): ?object
    {
        return $this->privateContainer->has($id)
            ? $this->privateContainer->get($id)
            : $this->publicContainer->get($id, $invalidBehavior);
    }

    #[\Override]
    public function initialized(string $id): bool
    {
        return $this->publicContainer->initialized($id);
    }

    #[\Override]
    public function reset()
    {
        $this->publicContainer->reset();
    }

    #[\Override]
    public function getServiceIds(): array
    {
        return $this->publicContainer->getServiceIds();
    }

    #[\Override]
    public function getRemovedIds(): array
    {
        return $this->publicContainer->getRemovedIds();
    }
}
