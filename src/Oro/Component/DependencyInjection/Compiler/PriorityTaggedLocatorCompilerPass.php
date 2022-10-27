<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Finds all services with the given tag name, orders them by their priority
 * and adds a service locator contains them and keyed by keys received by the given "name" attribute
 * to the definition of the given service.
 * NOTE: this compiler pass does the same as "!tagged_locator { tag: tag_name, index_by: name_attribute }"
 * in services.yml, but it more preferable in most cases because it throws an exception
 * if a tag does not have the given "name" attribute.
 */
class PriorityTaggedLocatorCompilerPass implements CompilerPassInterface
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
            ->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
