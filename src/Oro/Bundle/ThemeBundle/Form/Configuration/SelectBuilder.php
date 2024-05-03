<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Provides supporting 'select' form type for the theme configuration section of theme.yml files
 */
class SelectBuilder extends AbstractChoiceBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'select';
    }

    /**
     * {@inheritDoc}
     */
    public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        if (array_key_exists('default', $option) && $this->isMultipleSelect($option)) {
            $option['default'] = [$option['default']];
        }

        parent::buildOption($builder, $option);
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
        ];
    }

    protected function isMultipleSelect(array $option): bool
    {
        return isset($option['options']['multiple']) && $option['options']['multiple'];
    }
}
