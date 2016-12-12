<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return LocalizationSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
