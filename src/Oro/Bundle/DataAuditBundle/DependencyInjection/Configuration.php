<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration of the Data Audit bundle.
 */
class Configuration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_data_audit';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('configuration_level_entities')
                    ->info(
                        'The entity whose id a configuration scope carries, per scope. The audit uses it to'
                        . ' show a readable name of what was configured. A bundle that contributes a'
                        . ' configuration scope declares its entity here; a bundle that replaces a scope'
                        . ' manager and thereby changes what the scope id refers to declares it as well.'
                    )
                    ->example(['website' => 'Oro\Bundle\WebsiteBundle\Entity\Website'])
                    ->useAttributeAsKey('scope')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
