<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a birthday date.
 *
 * This type extends {@see OroDateType} with pre-configured options suitable for birthday
 * selection, including a year range from 120 years ago to the current year, and
 * appropriate minimum and maximum date constraints.
 */
class OroBirthdayType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('years', range(date('Y') - 120, date('Y')));
        $resolver->setDefault('minDate', '-120y');
        $resolver->setDefault('maxDate', '0');
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroDateType::class;
    }
}
