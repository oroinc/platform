<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Provides supporting {@see ChoiceType} for the theme configuration section of theme.yml files
 */
abstract class AbstractChoiceBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override]
    protected function getTypeClass(): string
    {
        return ChoiceType::class;
    }

    #[\Override]
    protected function getConfiguredOptions($option): array
    {
        $options = [
            'choice_attr' => function ($choice, string $key, mixed $value) use ($option) {
                $choiceAttr = [];
                if (\array_key_exists('previews', $option) && !empty($option['previews'])) {
                    $preview = $this->getOptionPreview($option, $value);
                    if ($preview) {
                        $choiceAttr['data-preview'] = $preview;
                    }
                }

                return $choiceAttr;
            }
        ];

        if (\array_key_exists('values', $option) && !empty($option['values'])) {
            $options['choices'] = \array_flip($option['values']);
        }

        return \array_merge(parent::getConfiguredOptions($option), $options);
    }
}
