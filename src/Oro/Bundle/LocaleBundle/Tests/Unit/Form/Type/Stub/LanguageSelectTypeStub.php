<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return LanguageSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => Intl::getLanguageBundle()->getLanguageNames(),
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
