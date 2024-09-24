<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Provide supporting 'text' form type for the theme configuration section of theme.yml files
 */
class TextBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'text';
    }

    #[\Override] protected function getTypeClass(): string
    {
        return TextType::class;
    }

    #[\Override] protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
        ];
    }
}
