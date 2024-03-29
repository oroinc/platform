<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Provide supporting 'radio' form type for the theme configuration section of theme.yml files
 */
class RadioBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'radio';
    }

    /**
     * {@inheritDoc}
     */
    public function supports(array $option): bool
    {
        return $option['type'] === self::getType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTypeClass(): string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfiguredOptions($option): array
    {
        return array_merge(parent::getConfiguredOptions($option), [
            'choices' => array_flip($option['values']),
            'data' => $option['default'],
        ]);
    }
}
