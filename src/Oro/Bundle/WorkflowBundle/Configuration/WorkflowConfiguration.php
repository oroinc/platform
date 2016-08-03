<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class WorkflowConfiguration implements ConfigurationInterface
{
    const NODE_STEPS = 'steps';
    const NODE_ATTRIBUTES = 'attributes';
    const NODE_TRANSITIONS = 'transitions';
    const NODE_TRANSITION_DEFINITIONS = 'transition_definitions';
    const NODE_ENTITY_RESTRICTIONS = 'entity_restrictions';

    const DEFAULT_TRANSITION_DISPLAY_TYPE = 'dialog';
    const DEFAULT_ENTITY_ATTRIBUTE = 'entity';

    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, array($configs));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('configuration');
        $this->addWorkflowNodes($rootNode->children());

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $nodeBuilder
     * @return NodeBuilder
     */
    public function addWorkflowNodes(NodeBuilder $nodeBuilder)
    {
        $nodeBuilder
            ->scalarNode('name')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('label')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('entity')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->booleanNode('is_system')
                ->defaultFalse()
            ->end()
            ->scalarNode('start_step')
                ->defaultNull()
            ->end()
            ->scalarNode('entity_attribute')
                ->defaultValue(self::DEFAULT_ENTITY_ATTRIBUTE)
            ->end()
            ->booleanNode('steps_display_ordered')
                ->defaultFalse()
            ->end()
            ->append($this->getStepsNode())
            ->append($this->getAttributesNode())
            ->append($this->getTransitionsNode())
            ->append($this->getTransitionDefinitionsNode())
            ->append($this->getEntityRestrictionsNode());

        return $nodeBuilder;
    }

    /**
     * @return NodeDefinition
     */
    protected function getStepsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NODE_STEPS);
        $rootNode
            ->useAttributeAsKey('name')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('label')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->integerNode('order')
                        ->defaultValue(0)
                    ->end()
                    ->booleanNode('is_final')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('_is_start')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('entity_acl')
                        ->prototype('array')
                            ->children()
                                ->booleanNode('update')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('delete')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('allowed_transitions')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                    ->arrayNode('position')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }

    /**
     * @return NodeDefinition
     */
    protected function getAttributesNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NODE_ATTRIBUTES);
        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('label')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('type')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('entity_acl')
                        ->children()
                            ->booleanNode('update')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('delete')
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('property_path')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('options')
                        ->prototype('variable')
                        ->end()
                    ->end()
                ->end()
                ->validate()
                    ->always(
                        function ($value) {
                            if (array_key_exists('entity_acl', $value) && $value['type'] != 'entity') {
                                throw new WorkflowException(
                                    'Entity ACL only can be defined for attributes with type "entity"'
                                );
                            }
                            return $value;
                        }
                    )
                ->end()
            ->end();

        return $rootNode;
    }

    /**
     * @return NodeDefinition
     */
    protected function getTransitionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NODE_TRANSITIONS);
        $rootNode
            ->useAttributeAsKey('name')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('label')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('step_to')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('is_start')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('is_hidden')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('is_unavailable_hidden')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('acl_resource')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('acl_message')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('schedule')
                        ->children()
                            ->scalarNode('cron')->end()
                            ->scalarNode('filter')->end()
                            ->booleanNode('check_conditions_before_job_creation')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('message')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('transition_definition')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('frontend_options')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->scalarNode('form_type')
                        ->defaultValue(WorkflowTransitionType::NAME)
                    ->end()
                    ->enumNode('display_type')
                        ->values(array('dialog', 'page'))
                        ->defaultValue(self::DEFAULT_TRANSITION_DISPLAY_TYPE)
                    ->end()
                    ->arrayNode('form_options')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->scalarNode('page_template')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('dialog_template')
                        ->defaultNull()
                    ->end()
                ->end()
                ->validate()
                    ->always(
                        function ($value) {
                            if ($value['display_type'] == 'page' && empty($value['form_options'])) {
                                throw new WorkflowException(
                                    'Display type "page" require "form_options" to be set.'
                                );
                            }
                            return $value;
                        }
                    )
                ->end()
            ->end();

        return $rootNode;
    }

    /**
     * @return NodeDefinition
     */
    protected function getTransitionDefinitionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NODE_TRANSITION_DEFINITIONS);
        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('pre_conditions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('conditions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('post_actions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }

    /**
     * @return NodeDefinition
     */
    protected function getEntityRestrictionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(self::NODE_ENTITY_RESTRICTIONS);
        $rootNode
            ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('attribute')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('step')
                        ->end()
                        ->scalarNode('field')
                        ->end()
                        ->enumNode('mode')
                            ->defaultValue('full')
                            ->values(['full', 'disallow', 'allow'])
                        ->end()
                        ->arrayNode('values')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }
}
