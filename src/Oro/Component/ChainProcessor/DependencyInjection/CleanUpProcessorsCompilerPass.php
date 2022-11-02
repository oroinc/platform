<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This DIC compiler pass can be used to move processors do not depend on any service
 * from DIC to a separate registry.
 * In additional if $processorRegistryServiceId is specified the rest of services are moved to a service locator.
 * It allows you to define all processors in DIC configuration files and not worry about a size of DIC.
 */
class CleanUpProcessorsCompilerPass implements CompilerPassInterface
{
    /** @var string */
    private $simpleProcessorRegistryServiceId;

    /** @var string */
    private $processorTagName;

    /** @var string|null */
    private $processorRegistryServiceId;

    public function __construct(
        string $simpleProcessorRegistryServiceId,
        string $processorTagName,
        string $processorRegistryServiceId = null
    ) {
        $this->simpleProcessorRegistryServiceId = $simpleProcessorRegistryServiceId;
        $this->processorTagName = $processorTagName;
        $this->processorRegistryServiceId = $processorRegistryServiceId;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $simpleProcessors = [];
        $containerProcessors = [];
        $taggedServices = $container->findTaggedServiceIds($this->processorTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            $processorServiceDef = $container->getDefinition($id);
            if ($this->isSimpleProcessor($processorServiceDef)) {
                $simpleProcessors[$id] = $processorServiceDef->getArguments()
                    ? [$processorServiceDef->getClass(), $processorServiceDef->getArguments()]
                    : $processorServiceDef->getClass();
                $container->removeDefinition($id);
            } elseif (null !== $this->processorRegistryServiceId) {
                $container->getDefinition($id)->setPublic(false);
                $containerProcessors[$id] = new Reference($id);
            }
        }

        if (!empty($simpleProcessors)) {
            $container->getDefinition($this->simpleProcessorRegistryServiceId)
                ->setArgument(0, $simpleProcessors);
        }

        if (null !== $this->processorRegistryServiceId) {
            $container->getDefinition($this->processorRegistryServiceId)
                ->setArgument(0, ServiceLocatorTagPass::register($container, $containerProcessors));
        }
    }

    private function isSimpleProcessor(Definition $processorServiceDef): bool
    {
        if ($processorServiceDef->isAbstract()) {
            return false;
        }
        if ($processorServiceDef->isLazy()) {
            return false;
        }
        if ($processorServiceDef->getMethodCalls()) {
            return false;
        }

        $arguments = $processorServiceDef->getArguments();
        foreach ($arguments as $argument) {
            if (!$this->isSimpleArgument($argument)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $argument
     *
     * @return bool
     */
    private function isSimpleArgument($argument): bool
    {
        return
            null === $argument
            || \is_string($argument)
            || \is_bool($argument)
            || \is_int($argument)
            || \is_float($argument);
    }
}
