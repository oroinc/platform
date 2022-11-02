<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Finds all services with the given tag name, orders them by their priority
 * and registers them inside the given service locator.
 */
class ServiceLocatorCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /** @var string */
    private $serviceLocatorServiceId;

    /** @var string */
    private $tagName;

    /** @var string|null */
    private $nameAttribute;

    /** @var bool */
    private $isServiceLocatorOptional;

    public function __construct(
        string $serviceLocatorServiceId,
        string $tagName,
        string $nameAttribute = null,
        bool $isServiceLocatorOptional = false
    ) {
        $this->serviceLocatorServiceId = $serviceLocatorServiceId;
        $this->tagName = $tagName;
        $this->nameAttribute = $nameAttribute;
        $this->isServiceLocatorOptional = $isServiceLocatorOptional;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($this->isServiceLocatorOptional && !$container->hasDefinition($this->serviceLocatorServiceId)) {
            return;
        }

        $services = $this->findAndSortTaggedServicesWithOptionalNameAttribute(
            $this->tagName,
            $container,
            $this->nameAttribute
        );

        $container->getDefinition($this->serviceLocatorServiceId)
            ->setArgument(0, $services);
    }
}
