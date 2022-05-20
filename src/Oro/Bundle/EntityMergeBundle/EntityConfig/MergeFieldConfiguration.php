<?php

namespace Oro\Bundle\EntityMergeBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for merge scope.
 */
class MergeFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'merge';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('label')
                ->info('`string` the field label that should be displayed for this field in merge UI, value can ' .
                'be translated.')
            ->end()
            ->node('display', 'normalized_boolean')
                ->info('`boolean` a display merge form for this field.')
            ->end()
            ->node('readonly', 'normalized_boolean')
                ->info('`boolean` turn the field into read-only during merge.')
            ->end()
            ->variableNode('merge_modes')
                ->info('mode of merge with values replace and unite, which can be an array or a single mode:' . "\n" .
                ' - replace - replaces one value with a selected value;' . "\n" .
                ' - unite - merges all values into one (applicable to collections and lists).')
            ->end()
            ->node('is_collection', 'normalized_boolean')
                ->info('`boolean` a flag for a collection of fields. This fields supports unite mode by default.')
            ->end()
            ->variableNode('cast_method')
                ->info('options for rendering field value in the UI. Method is used to cast value to a string ' .
                '(applicable only to values that are objects).')
            ->end()
            ->scalarNode('template')
                ->info('`string` a template can be used to render the value of a field.')
            ->end()
            ->variableNode('setter')
                ->info('a method for setting a value to an entity.')
            ->end()
            ->variableNode('getter')
                ->info('a method for getting a value to an entity.')
            ->end()
            ->node('inverse_display', 'normalized_boolean')
                ->info('can be used to see merge form for this field for an entity on the other side of relation. ' .
                'Let’s consider an example where the Call entity with a field referenced to Account uses ' .
                'ManyToOne unidirectional relation. As Account does not have access to a collection of calls the ' .
                'only possible place to configure calls merging for account is this field in the Call entity.')
            ->end()
            ->variableNode('inverse_merge_modes')
                ->info('the same as merge_mode but is used for the relation entity.')
            ->end()
            ->scalarNode('inverse_label')
                ->info('`string` the same as label but used for the relation entity.')
            ->end()
            ->variableNode('inverse_cast_method')
                ->info('the same as cast_method but used for the relation entity.')
            ->end()
            ->scalarNode('render_number_style')
                ->info('`string` a localization number type. Default localisation handler support: decimal, ' .
                'currency, percent, default_style, scientific, ordinal, duration, spellout.')
            ->end()
            ->scalarNode('render_date_type')
                ->info('`string` a type of date formatting, one of the format type constants. Possible values: NONE, ' .
                'FULL, LONG, MEDIUM, SHORT.')
            ->end()
            ->scalarNode('render_time_type')
                ->info('`string` a type of time formatting, one of the format type constants. Possible values: NONE, ' .
                'FULL, LONG, MEDIUM, SHORT.')
            ->end()
            ->scalarNode('render_datetime_pattern')
                ->info('`string` a date/time pattern. Example: ‘m/d/Y’.')
            ->end()
            ->node('autoescape', 'normalized_boolean')
                ->info('controls escaping of the value when rendered in the Merge table. Use ‘false’ to disable ' .
                'escaping for the field (i.e., RichText) or set the Twig ‘escape’ method to enable: ' .
                '‘html’ (or true), ‘html_attr’, ‘css’, ‘js’, ‘url’.')
                ->defaultTrue()
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the merge state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
