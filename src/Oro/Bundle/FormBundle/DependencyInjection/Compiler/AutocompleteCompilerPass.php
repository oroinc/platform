<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all autocomplete search handlers.
 */
class AutocompleteCompilerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $handlers = [];
        $aclResources = [];
        $taggedServices = $container->findTaggedServiceIds('oro_form.autocomplete.search_handler');
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $name = $this->getAttribute($attributes, 'alias', $id);
                $handlers[$name] = new Reference($id);
                $aclResource = $this->getAttribute($attributes, 'acl_resource');
                if ($aclResource) {
                    $aclResources[$name] = $aclResource;
                }
            }
        }

        $container->getDefinition('oro_form.autocomplete.search_registry')
            ->setArgument('$searchHandlers', ServiceLocatorTagPass::register($container, $handlers));
        $container->getDefinition('oro_form.autocomplete.security')
            ->setArgument('$autocompleteAclResources', $aclResources);
    }
}
