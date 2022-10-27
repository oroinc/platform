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

    /**
     * {@inheritdoc}
     */
    public function compile()
    {
        $this->publicContainer->compile();
    }

    /**
     * {@inheritdoc}
     */
    public function isCompiled()
    {
        return $this->publicContainer->isCompiled();
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $id, ?object $service)
    {
        $this->publicContainer->set($id, $service);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id)
    {
        return $this->publicContainer->has($id) || $this->privateContainer->has($id);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $invalidBehavior = /* self::EXCEPTION_ON_INVALID_REFERENCE */ 1)
    {
        return $this->privateContainer->has($id)
            ? $this->privateContainer->get($id)
            : $this->publicContainer->get($id, $invalidBehavior);
    }

    /**
     * {@inheritdoc}
     */
    public function initialized(string $id)
    {
        return $this->publicContainer->initialized($id);
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->publicContainer->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceIds()
    {
        return $this->publicContainer->getServiceIds();
    }

    /**
     * {@inheritdoc}
     */
    public function getRemovedIds()
    {
        return $this->publicContainer->getRemovedIds();
    }
}
