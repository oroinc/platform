<?php

namespace Oro\Component\ChainProcessor;

use Psr\Container\ContainerInterface;

/**
 * The provider that can be used if each action has own the ProcessorBag configuration provider.
 * Use this provider if there are a lot of actions and you do not want to load configuration for all action every time.
 */
class LazyProcessorBagConfigProvider implements ProcessorBagConfigProviderInterface
{
    /** @var string[] */
    private $actions;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param string[]           $actions
     * @param ContainerInterface $container
     */
    public function __construct(array $actions, ContainerInterface $container)
    {
        $this->actions = $actions;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(string $action): array
    {
        if (!$this->container->has($action)) {
            return [];
        }

        /** @var ProcessorBagActionConfigProvider $configProvider */
        $configProvider = $this->container->get($action);

        return $configProvider->getGroups();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(string $action): array
    {
        if (!$this->container->has($action)) {
            return [];
        }

        /** @var ProcessorBagActionConfigProvider $configProvider */
        $configProvider = $this->container->get($action);

        return $configProvider->getProcessors();
    }
}
