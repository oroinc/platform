<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Provide supporting 'number' form type for the theme configuration section of theme.yml files
 */
class NumberBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'number';
    }

    #[\Override] protected function getTypeClass(): string
    {
        return NumberType::class;
    }

    #[\Override] protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'constraints' => [
                new PositiveOrZero()
            ]
        ];
    }
}
