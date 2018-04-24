<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Oro\Bundle\ReminderBundle\Form\Type\MethodType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderIntervalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReminderType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'method',
            MethodType::class,
            array(
                'required' => true,
                'attr'     => array('class' => 'method'),
            )
        );

        $builder->add(
            'interval',
            ReminderIntervalType::class,
            array('required' => true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\\Bundle\\ReminderBundle\\Entity\\Reminder',
                'csrf_token_id'        => 'reminder',
                'error_bubbling'       => false,
            )
        );
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
        return 'oro_reminder';
    }
}
