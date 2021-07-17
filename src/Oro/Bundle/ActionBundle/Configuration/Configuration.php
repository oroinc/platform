<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/actions.yml" files.
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'actions';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $node = $rootNode->children();
        $this->appendActionGroups($node);
        $this->appendOperations($node);

        return $treeBuilder;
    }

    protected function appendActionGroups(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode('action_groups')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();

        $children
            ->variableNode('acl_resource')->end()
            ->arrayNode('parameters')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('message')->end()
                        ->variableNode('default')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('conditions')
                ->prototype('variable')->end()
            ->end()
            ->arrayNode('actions')
                ->prototype('variable')->end()
            ->end()
        ->end();
    }

    protected function appendOperations(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode('operations')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();

        $children
            ->arrayNode('replace')
                ->beforeNormalization()
                    ->always(
                        function ($replace) {
                            return (array)$replace;
                        }
                    )
                ->end()
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode('label')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('substitute_operation')->end()
            ->arrayNode('applications')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('groups')
                ->prototype('scalar')->end()
            ->end()
            ->booleanNode('for_all_entities')
                ->defaultFalse()
            ->end()
            ->arrayNode('entities')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('exclude_entities')
                ->prototype('scalar')->end()
            ->end()
            ->booleanNode('for_all_datagrids')
                ->defaultFalse()
            ->end()
            ->arrayNode('datagrids')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('exclude_datagrids')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('routes')
                ->prototype('scalar')->end()
            ->end()
            ->variableNode('acl_resource')->end()
            ->integerNode('order')
                ->defaultValue(0)
            ->end()
            ->booleanNode('enabled')
                ->defaultTrue()
            ->end()
            ->booleanNode('page_reload')
                ->defaultTrue()
            ->end()
            ->append($this->getAttributesNode())
            ->append($this->getButtonOptionsNode())
            ->append($this->getFrontendOptionsNode())
            ->append($this->getDatagridOptionsNode())
            ->append($this->getFormOptionsNode())
        ->end();

        $this->appendActionsNodes($children);
        $this->appendConditionsNodes($children);
    }

    /**
     * @param NodeBuilder $builder
     */
    protected function appendActionsNodes($builder)
    {
        foreach (OperationDefinition::getAllowedActions() as $nodeName) {
            $builder
                ->arrayNode($nodeName)
                    ->prototype('variable')->end()
                ->end();
        }
    }

    protected function appendConditionsNodes(NodeBuilder $builder)
    {
        foreach (OperationDefinition::getAllowedConditions() as $nodeName) {
            $builder
                ->arrayNode($nodeName)
                    ->prototype('variable')->end()
                ->end();
        }
    }

    /**
     * @return NodeDefinition
     */
    protected function getAttributesNode()
    {
        $builder = new TreeBuilder('attributes');
        $node = $builder->getRootNode();
        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->enumNode('type')
                        ->defaultNull()
                        ->values(['bool', 'boolean', 'int', 'integer', 'float', 'string', 'array', 'object', 'entity'])
                    ->end()
                    ->scalarNode('label')->end()
                    ->scalarNode('property_path')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('entity_acl')->end()
                    ->arrayNode('options')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
                ->validate()
                    ->always(function ($config) {
                        $this->checkEntityAcl($config);
                        $this->checkOptionClass($config, in_array($config['type'], ['object', 'entity'], true));
                        $this->checkPropertyPath($config);

                        return $config;
                    })
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getButtonOptionsNode()
    {
        $builder = new TreeBuilder('button_options');
        $node = $builder->getRootNode();
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('icon')->end()
                ->scalarNode('class')->end()
                ->scalarNode('group')->end()
                ->scalarNode('template')->end()
                ->scalarNode('page_component_module')->end()
                ->arrayNode('page_component_options')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('data')
                    ->prototype('variable')->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getFrontendOptionsNode()
    {
        $builder = new TreeBuilder('frontend_options');
        $node = $builder->getRootNode();
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->variableNode('confirmation')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($value) {
                            return !empty($value) ? ['message' => $value] : [];
                        })
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->prototype('variable')->end()
                ->end()
                ->scalarNode('template')->end()
                ->scalarNode('title')->end()
                ->arrayNode('title_parameters')
                    ->prototype('variable')->end()
                ->end()
                ->booleanNode('show_dialog')
                    ->defaultTrue()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getDatagridOptionsNode()
    {
        $builder = new TreeBuilder('datagrid_options');
        $node = $builder->getRootNode();
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('mass_action_provider')->end()
                ->arrayNode('mass_action')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('data')
                    ->prototype('variable')->end()
                ->end()
                ->scalarNode('aria_label')->end()
            ->end()
            ->validate()
                ->always(function ($config) {
                    if (!empty($config['mass_action_provider']) && !empty($config['mass_action'])) {
                        throw new \Exception(
                            'Must be specified only one parameter "mass_action_provider" or "mass_action"'
                        );
                    }

                    return $config;
                })
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getFormOptionsNode()
    {
        $builder = new TreeBuilder('form_options');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->arrayNode('validation_groups')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('attribute_fields')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('form_type')->end()
                            ->arrayNode('options')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('attribute_default_values')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @throws \Exception
     */
    protected function checkEntityAcl(array $config)
    {
        if ($config['type'] !== 'entity' && array_key_exists('entity_acl', $config)) {
            throw new \Exception(sprintf(
                'Attribute "%s" with type "%s" can\'t have entity ACL',
                $config['label'],
                $config['type']
            ));
        }
    }

    /**
     * @param array $config
     * @param bool $require
     * @throws \Exception
     */
    protected function checkOptionClass(array $config, $require)
    {
        if ($require && empty($config['options']['class'])) {
            throw new \Exception(sprintf('Option "class" is required for "%s" type', $config['type']));
        } elseif (!$require && !empty($config['options']['class'])) {
            throw new \Exception(sprintf('Option "class" cannot be used for "%s" type', $config['type']));
        }
    }

    /**
     * @throws \Exception
     */
    protected function checkPropertyPath(array $config)
    {
        if (empty($config['property_path']) && empty($config['label'])) {
            throw new \Exception('Option "label" or "property_path" is required');
        }

        if (empty($config['property_path']) && empty($config['type'])) {
            throw new \Exception('Option "type" or "property_path" is required');
        }
    }
}
