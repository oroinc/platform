<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormattingSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return FormattingSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_flip(Locales::getNames())
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
