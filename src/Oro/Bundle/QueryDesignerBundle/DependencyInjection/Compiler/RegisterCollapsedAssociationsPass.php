<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers configuration of entities whose associations can be used in the query designer
 * without expanding their fields.
 */
class RegisterCollapsedAssociationsPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $collapsedAssociations = $container->getParameter('oro_query_designer.collapsed_associations');
        $container->getParameterBag()->remove('oro_query_designer.collapsed_associations');

        $dictionaryVirtualFieldProvider = $container->getDefinition('oro_entity.virtual_field_provider.dictionary');
        $dictionaryEntityDataProvider = $container->getDefinition('oro_entity.dictionary_entity_data_provider');
        foreach ($collapsedAssociations as $entityClass => $config) {
            $dictionaryVirtualFieldProvider
                ->addMethodCall('registerDictionary', [$entityClass, $config['virtual_fields']]);
            $dictionaryEntityDataProvider
                ->addMethodCall('registerDictionary', [$entityClass, $config['search_fields']]);
        }
    }
}
