<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Cron\CronExpression;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class WorkflowConfiguration extends AbstractConfiguration implements ConfigurationInterface
{
    const NODE_STEPS = 'steps';
    const NODE_ATTRIBUTES = 'attributes';
    const NODE_TRANSITIONS = 'transitions';
    const NODE_TRANSITION_DEFINITIONS = 'transition_definitions';
    const NODE_ENTITY_RESTRICTIONS = 'entity_restrictions';
    const NODE_EXCLUSIVE_ACTIVE_GROUPS = 'exclusive_active_groups';
    const NODE_EXCLUSIVE_RECORD_GROUPS = 'exclusive_record_groups';
    const NODE_TRANSITION_TRIGGERS = 'triggers';
    const NODE_INIT_ENTITIES = 'init_entities';
    const NODE_INIT_ROUTES = 'init_routes';

    const DEFAULT_TRANSITION_DISPLAY_TYPE = 'dialog';
    const DEFAULT_ENTITY_ATTRIBUTE = 'entity';
    const DEFAULT_INIT_CONTEXT_ATTRIBUTE = 'init_context';

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
            ->arrayNode('defaults')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('active')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()
            ->integerNode('priority')
                ->defaultValue(0)
            ->end()
            ->append($this->getStepsNode())
            ->append($this->getAttributesNode())
            ->append($this->getTransitionsNode())
            ->append($this->getTransitionDefinitionsNode())
            ->append($this->getEntityRestrictionsNode())
            ->append($this->getGroupsNode(self::NODE_EXCLUSIVE_ACTIVE_GROUPS))
            ->append($this->getGroupsNode(self::NODE_EXCLUSIVE_RECORD_GROUPS));

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
                        ->beforeNormalization()
                            ->always(function ($config) {
                                return $this->mergeConfigs([
                                    'form_init' => 'init_actions',
                                ], $config);
                            })
                        ->end()
                    ->end()
                    ->scalarNode('page_template')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('dialog_template')
                        ->defaultNull()
                    ->end()
                    ->arrayNode(self::NODE_INIT_ENTITIES)
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode(self::NODE_INIT_ROUTES)
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('init_context_attribute')
                        ->defaultValue(self::DEFAULT_INIT_CONTEXT_ATTRIBUTE)
                    ->end()
                    ->append($this->getTransitionTriggers())
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
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function getTransitionTriggers()
    {
        $builder = new TreeBuilder();
        $triggersNode = $builder->root(self::NODE_TRANSITION_TRIGGERS);
        $triggersNode
            ->prototype('array')
                ->children()
                    ->enumNode('event')
                        ->defaultNull()
                        ->values(TransitionEventTrigger::getAllowedEvents())
                    ->end()
                    ->scalarNode('field')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('require')
                        ->defaultNull()
                    ->end()
                    ->booleanNode('queued')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode('entity_class')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('relation')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('cron')
                        ->defaultNull()
                        ->validate()
                        ->always(
                            function ($value) {
                                if ($value !== null) {
                                    // validate expression string
                                    CronExpression::factory($value);
                                }
                                return $value;
                            }
                        )
                        ->end()
                    ->end()
                    ->scalarNode('filter')
                        ->info('DQL "where" part to filter entities that match for cron trigger.')
                        ->defaultNull()
                    ->end()
                ->end()
                ->validate()
                    ->ifTrue(
                        function ($data) {
                            return $data['event'] && $data['cron'];
                        }
                    )
                    ->thenInvalid('Only one child node "event" or "cron" must be configured.')
                ->end()
                ->validate()
                    ->always(
                        function ($data) {
                            $eventFields = ['relation', 'field', 'entity_class', 'require'];
                            if ($data['cron']) {
                                foreach ($eventFields as $field) {
                                    if ($data[$field]) {
                                        throw new \LogicException(
                                            sprintf('Field "%s" only allowed for event node', $field)
                                        );
                                    }
                                }
                            }

                            return $data;
                        }
                    )
                ->end()
                ->validate()
                    ->ifTrue(
                        function ($data) {
                            return $data['field'] && $data['event'] !== EventTriggerInterface::EVENT_UPDATE;
                        }
                    )->thenInvalid('The "field" option is only allowed for update event.')
                ->end()
                ->validate()
                    ->ifTrue(
                        function ($data) {
                            return $data['relation'] && !$data['entity_class'];
                        }
                    )
                    ->thenInvalid(
                        'Field `entity_class` is mandatory for custom (non-workflow related) entity.'
                    )
                ->end()
            ->end();

        return $triggersNode;
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
                    ->arrayNode('preactions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('preconditions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('pre_conditions')->end() // deprecated, use `preconditions` instead
                    ->arrayNode('conditions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('actions')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->arrayNode('post_actions')->end() // deprecated, use `actions` instead
                ->end()
                ->beforeNormalization()
                    ->always(function ($config) {
                        return $this->mergeConfigs([
                            'preconditions' => 'pre_conditions',
                            'actions' => 'post_actions',
                        ], $config);
                    })
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

    /**
     * @param string $nodeName
     * @return NodeDefinition
     */
    protected function getGroupsNode($nodeName)
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($nodeName);
        $rootNode
            ->beforeNormalization()
                ->always()
                ->then(function ($v) {
                    return array_map('strtolower', $v);
                })
            ->end()
            ->prototype('scalar')
            ->end();

        return $rootNode;
    }
}
