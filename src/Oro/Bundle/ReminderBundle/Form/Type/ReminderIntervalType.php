<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReminderIntervalType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'number',
            'integer',
            array(
                'required' => true,
                'attr'     => array('class' => 'number'),
            )
        );

        $builder->add(
            'unit',
            'oro_reminder_interval_unit',
            array(
                'required' => true,
                'attr'     => array('class' => 'unit'),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Oro\\Bundle\\ReminderBundle\\Model\\ReminderInterval',
                'cascade_validation' => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder_interval';
    }
}
