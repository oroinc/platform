<?php

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type to select ThemeConfiguration entities.
 */
class ThemeConfigurationSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => ThemeConfigurationType::class,
                'configs' => [
                    'placeholder' => 'oro.theme.themeconfiguration.form.choose',
                ],
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_theme_configuration_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
