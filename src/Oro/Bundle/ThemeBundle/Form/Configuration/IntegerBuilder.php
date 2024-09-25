<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Provide supporting 'integer' form type for the theme configuration section of theme.yml files
 */
class IntegerBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override]
    public static function getType(): string
    {
        return 'integer';
    }

    #[\Override]
    protected function getTypeClass(): string
    {
        return IntegerType::class;
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'constraints' => [
                new PositiveOrZero()
            ]
        ];
    }
}
