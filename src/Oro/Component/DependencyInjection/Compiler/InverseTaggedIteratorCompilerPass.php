<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The same as "!tagged_iterator tag_name",
 * but uses ksort() function instead of krsort() to sort services by priority.
 *
 * @deprecated use "!tagged_iterator tag_name" for new tags
 */
class InverseTaggedIteratorCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $tagName;

    /** @var bool */
    private $isServiceOptional;

    public function __construct(
        string $serviceId,
        string $tagName,
        bool $isServiceOptional = false
    ) {
        $this->serviceId = $serviceId;
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

        $services = $this->findAndInverseSortTaggedServices($this->tagName, $container);

        $container->getDefinition($this->serviceId)
            ->setArgument(0, new IteratorArgument(array_values($services)));
    }
}
