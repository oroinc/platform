<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\LocaleType as SymfonyLocaleType;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting locales with localized names.
 *
 * This form type extends Symfony's {@see LocaleType} to provide a customized locale
 * selector that loads locale names dynamically in English locale. It uses a
 * callback choice loader to fetch the list of available locales from the Intl
 * component, enabling flexible locale selection in forms.
 */
class LocaleType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_loader' => new CallbackChoiceLoader(function () {
                    return array_flip(Locales::getNames('en'));
                })
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyLocaleType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_locale';
    }
}
