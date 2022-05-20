<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for form scope.
 */
class FormFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'form';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('form_options')
                ->info('`boolean` form options for a specific field. For more information, see ' .
                'Symfony Form Type Options(https://symfony.com/doc/current/forms.html#form-type-options).')
                ->prototype('variable')->end()
            ->end()
            ->node('is_enabled', 'normalized_boolean')
                ->info('`boolean` enables the ‘form’ functionality.')
                ->defaultTrue()
            ->end()
            ->scalarNode('form_type')
                ->info('`string` form type for a specific field.')
                ->example('Oro\Bundle\FormBundle\Form\Type\OroPercentType')
            ->end()
            ->scalarNode('type')
                ->info('same as form_type')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the form state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
