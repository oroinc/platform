<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a localization with autocomplete and inline creation.
 *
 * This form type provides an entity select field with autocomplete functionality
 * for choosing a localization. It supports inline creation of new localizations
 * through a dedicated form route, allowing users to quickly add new localization
 * options without leaving the current form.
 */
class LocalizationSelectType extends AbstractType
{
    public const NAME = 'oro_locale_localization_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_localization',
                'create_form_route' => 'oro_locale_localization_create',
                'configs' => [
                    'placeholder' => 'oro.locale.localization.form.placeholder.choose',
                ]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
