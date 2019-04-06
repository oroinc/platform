<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Autocomplete select form type for enabled localizations.
 */
class EnabledLocalizationSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_locale_enabled_localization';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_enabled_localization',
            'create_enabled' => false,
            'grid_name' => 'enabled-localizations-select-grid',
            'configs' => [
                'component' => 'autocomplete-enabledlocalization',
            ],
        ]);
    }
}
