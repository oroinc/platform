<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Finds all services with the given tag name, orders them by their priority
 * and adds an array of keys received by the given "name" attribute and a service locator contains them
 * to the definition of the given service.
 */
class PriorityNamedTaggedServiceCompilerPass implements CompilerPassInterface
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

        $services = $this->findAndSortTaggedServices($this->tagName, $this->nameAttribute, $container);

        $container->getDefinition($this->serviceId)
            ->setArgument(0, array_keys($services))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
