<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuarterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'format'         => 'dMMMy',
                'input'          => 'array',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_quarter';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'date';
    }
}
