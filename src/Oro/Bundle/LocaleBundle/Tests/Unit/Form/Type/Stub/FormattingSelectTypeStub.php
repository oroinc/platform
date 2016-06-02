<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormattingSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return FormattingSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => Intl::getLocaleBundle()->getLocaleNames(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
