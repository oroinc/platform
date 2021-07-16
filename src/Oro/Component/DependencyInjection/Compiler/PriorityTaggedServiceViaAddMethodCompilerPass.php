<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Finds all services with the given tag name, orders them by their priority
 * and adds them to the definition of the given service via the given method name.
 * NOTE: prefer injecting tagged services in the constructor via "!tagged_iterator tag_name" in services.yml,
 * because in this case an iterator that supports lazy initialization of services is injected.
 *
 * @deprecated use "!tagged_iterator tag_name" for new tags
 */
class PriorityTaggedServiceViaAddMethodCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;
    use TaggedServiceTrait;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $tagName;

    /** @var string */
    private $addMethodName;

    /** @var bool */
    private $isServiceOptional;

    public function __construct(
        string $serviceId,
        string $tagName,
        string $addMethodName,
        bool $isServiceOptional = false
    ) {
        $this->serviceId = $serviceId;
        $this->addMethodName = $addMethodName;
        $this->tagName = $tagName;
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

        $taggedServices = $this->findAndSortTaggedServices($this->tagName, $container);

        $serviceDef = $container->getDefinition($this->serviceId);
        foreach ($taggedServices as $taggedService) {
            $serviceDef->addMethodCall($this->addMethodName, [$taggedService]);
        }
    }
}
