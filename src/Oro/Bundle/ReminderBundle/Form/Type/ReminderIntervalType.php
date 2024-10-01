<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Oro\Bundle\ReminderBundle\Form\Type\ReminderInterval\UnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReminderIntervalType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'number',
            IntegerType::class,
            array(
                'required' => true,
                'attr'     => array('class' => 'number'),
            )
        );

        $builder->add(
            'unit',
            UnitType::class,
            array(
                'required' => true,
                'attr'     => array('class' => 'unit'),
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Oro\\Bundle\\ReminderBundle\\Model\\ReminderInterval',
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_reminder_interval';
    }
}
