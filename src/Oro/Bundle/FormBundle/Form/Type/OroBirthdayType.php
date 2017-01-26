<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroBirthdayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('years', range(date('Y') - 120, date('Y')));
        $resolver->setDefault('minDate', '-120y');
        $resolver->setDefault('maxDate', '0');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroDateType::class;
    }
}
