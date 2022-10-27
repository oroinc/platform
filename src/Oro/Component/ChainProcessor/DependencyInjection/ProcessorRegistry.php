<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Psr\Container\ContainerInterface;

/**
 * The registry that can be used to get processors from DIC.
 */
class ProcessorRegistry implements ProcessorRegistryInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor(string $processorId): ProcessorInterface
    {
        return $this->container->get($processorId);
    }
}
