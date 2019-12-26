<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Finds all services with the given tag name, orders them by their priority
 * and adds them to the definition of the given service via the given method name.
 * NOTE: prefer injecting tagged services in the constructor via "!tagged tag_name" in services.yml.
 */
class PriorityTaggedServiceViaAddMethodCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;
    use TaggedServiceTrait;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $addMethodName;

    /** @var string */
    private $tagName;

    /** @var bool */
    private $isServiceOptional;

    /**
     * @param string $serviceId
     * @param string $addMethodName
     * @param string $tagName
     * @param bool   $isServiceOptional
     */
    public function __construct(
        string $serviceId,
        string $addMethodName,
        string $tagName,
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

        $this->registerTaggedServicesViaAddMethod(
            $container,
            $this->serviceId,
            $this->addMethodName,
            $this->findAndSortTaggedServices($this->tagName, $container)
        );
    }
}
