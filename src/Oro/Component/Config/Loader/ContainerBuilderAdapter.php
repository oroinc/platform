<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adapts ContainerBuilder to ResourcesContainerInterface.
 */
class ContainerBuilderAdapter implements ResourcesContainerInterface
{
    /** @var ContainerBuilder */
    private $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources(): array
    {
        return $this->container->getResources();
    }

    /**
     * {@inheritdoc}
     */
    public function addResource(ResourceInterface $resource): void
    {
        $this->container->addResource($resource);
    }
}
