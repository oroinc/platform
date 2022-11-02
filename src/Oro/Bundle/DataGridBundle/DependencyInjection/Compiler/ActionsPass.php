<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Registers datagrid actions.
 */
class ActionsPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /** @var string */
    private $factoryServiceId;

    /** @var string */
    private $tagName;

    public function __construct(string $factoryServiceId, string $tagName)
    {
        $this->factoryServiceId = $factoryServiceId;
        $this->tagName = $tagName;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndSortTaggedServices($this->tagName, 'type', $container);
        foreach ($services as $reference) {
            if ($container->getDefinition((string)$reference)->isShared()) {
                throw new RuntimeException(sprintf('The service "%s" should not be shared.', (string)$reference));
            }
        }

        $container->getDefinition($this->factoryServiceId)
            ->setArgument('$actionContainer', ServiceLocatorTagPass::register($container, $services));
    }
}
