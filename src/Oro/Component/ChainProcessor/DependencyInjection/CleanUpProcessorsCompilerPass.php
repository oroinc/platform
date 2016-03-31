<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This DI compiler pass can be used to move processors do not depend on any service
 * from DI container to a separate factory.
 * It allows you to define all processors in DI configuration files and not worry about a size of DI container.
 */
class CleanUpProcessorsCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $simpleProcessorFactoryServiceId;

    /** @var string */
    protected $processorTagName;

    /**
     * @param string $simpleProcessorFactoryServiceId
     * @param string $processorTagName
     */
    public function __construct(
        $simpleProcessorFactoryServiceId,
        $processorTagName
    ) {
        $this->simpleProcessorFactoryServiceId = $simpleProcessorFactoryServiceId;
        $this->processorTagName                = $processorTagName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->simpleProcessorFactoryServiceId)) {
            return;
        }

        $factoryServiceDef = $container->getDefinition($this->simpleProcessorFactoryServiceId);
        $taggedServices    = $container->findTaggedServiceIds($this->processorTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            $processorServiceDef = $container->getDefinition($id);
            if ($processorServiceDef->isLazy() || $processorServiceDef->isAbstract()) {
                continue;
            }

            $arguments = $processorServiceDef->getArguments();
            if (empty($arguments)) {
                $factoryServiceDef->addMethodCall('addProcessor', [$id, $processorServiceDef->getClass()]);
                $container->removeDefinition($id);
            }
        }
    }
}
