<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The same as PriorityNamedTaggedServiceCompilerPass,
 * but uses ksort() function instead of krsort() to sort services by priority.
 *
 * @deprecated use {@see PriorityNamedTaggedServiceCompilerPass} for new tags
 */
class InverseNamedTaggedServiceCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $tagName;

    /** @var string */
    private $nameAttribute;

    /** @var bool */
    private $isServiceOptional;

    public function __construct(
        string $serviceId,
        string $tagName,
        string $nameAttribute,
        bool $isServiceOptional = false
    ) {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->nameAttribute = $nameAttribute;
        $this->isServiceOptional = $isServiceOptional;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($this->isServiceOptional && !$container->hasDefinition($this->serviceId)) {
            return;
        }

        $services = $this->findAndInverseSortTaggedServices($this->tagName, $this->nameAttribute, $container);

        $container->getDefinition($this->serviceId)
            ->setArgument(0, array_keys($services))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
