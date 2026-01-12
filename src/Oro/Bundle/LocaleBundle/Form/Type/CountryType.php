<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CountryType as SymfonyCountryType;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting countries with localized names.
 *
 * This form type extends Symfony's {@see CountryType} to provide a customized country
 * selector that loads country names dynamically in English locale. It uses a
 * callback choice loader to fetch the list of countries from the Intl component,
 * allowing for flexible country selection in forms.
 */
class CountryType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_loader' => new CallbackChoiceLoader(function () {
                    return array_flip(Countries::getNames('en'));
                })
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyCountryType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_locale_country';
    }
}
