<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

/**
 * Provide supporting 'radio' form type for the theme configuration section of theme.yml files
 */
class RadioBuilder extends AbstractChoiceBuilder
{
    #[\Override]
    public static function getType(): string
    {
        return 'radio';
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
        ];
    }
}
