<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CountryType as SymfonyCountryType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_loader' => new CallbackChoiceLoader(function () {
                    return array_flip(Intl::getRegionBundle()->getCountryNames('en'));
                })
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SymfonyCountryType::class;
    }

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
        return 'oro_locale_country';
    }
}
