<?php

namespace Oro\Bundle\ActivityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for activity scope.
 */
class ActivityEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'activity';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder

            ->arrayNode('activities')
                ->info('`string[]` is the list of activities’ class names that can be assigned to the entity.')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('inheritance_targets')
                ->info('`string[]` are target entity classes whose activities will be shown in the current entity. ' .
                    'If entity 1 has relation with entity 2 and activity is enabled for both of them, we can ' .
                    'configure entity 1 using this option to show activities from related entity 2. In the example ' .
                    'of the configuration for unidirectional relations below, we are going to join the “user” ' .
                    'relation to the “Test” entity through the “Example” entity relation.')
                ->example([
                    'target' => 'Oro\Bundle\ExampleBundle\Entity\Test',
                    'path' => [
                        0 => [
                            'join' => 'Oro\Bundle\ExampleBundle\Entity\Example',
                            'conditionType' => 'WITH',
                            'field' => 'test_id'
                        ],
                        1 => 'user'
                    ]
                ])
                ->prototype('variable')->end()
            ->end()
            ->variableNode('immutable')
                ->info('`boolean or array` is used to prohibit changing the activity state (regardless of whether ' .
                    'it is enabled or not) for the entity. If TRUE, then activity state cannot be changed. '.
                    'It can also be an array with the list of class names of activities whose state cannot be changed.')
            ->end()
            ->scalarNode('route')
                ->info('`string` is the route name for the controller that can be used to render the list of this ' .
                    'type of activities. This controller must have $entityClass and $entityId. Parameters to pass ' .
                    'the target entity. This attribute must be defined for each activity entity (an entity included ' .
                    'in the ‘activity’ group, see ‘grouping’ scope).')
            ->end()
            ->scalarNode('acl')
                ->info('`string` is used to check whether this type of activity is available in the current ' .
                    'security context.')
            ->end()
            ->scalarNode('priority')
                ->info('`integer` can be used to change the order of this type of activity on the UI.')
            ->end()
            ->scalarNode('action_button_widget')
                ->info('`string` is the widget name of the activity action used to render a button. This widget ' .
                    'should be defined in placeholders.yml. This attribute can be defined for the activity entity. ' .
                    'Please note that an activity should provide both action_link_widget and action_link_widget, ' .
                    'because actions can be rendered both as a button as a dropdown menu.')
            ->end()
            ->scalarNode('show_on_page')
                ->info('`string` s used to change a page, which will display the “activity list” and activity ' .
                    'buttons. Can be used as bitmask. See available index states in ActivityScope.php(https://github' .
                    '.com/oroinc/platform/blob/master/src/Oro/Bundle/ActivityBundle/EntityConfig/ActivityScope.php)')
                ->defaultValue(sprintf('\\%s::VIEW_PAGE', ActivityScope::class))
            ->end()
            ->scalarNode('action_link_widget')
                ->info('`string` is the widget name of the activity action used to render a link in the dropdown ' .
                    'menu. This widget should be defined in placeholders.yml. This attribute can be defined for the ' .
                    'activity entity. Please note that an activity should provide both action_link_widget and ' .
                    'action_link_widget, because actions can be rendered as a button as a dropdown menu.')
            ->end()
        ;
    }
}
