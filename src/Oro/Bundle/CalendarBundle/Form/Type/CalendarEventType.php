<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextareaType::class, array('required' => true))
            ->add('start', 'oro_datetime', array('required' => true))
            ->add('end', 'oro_datetime', array('required' => true))
            ->add('allDay', CheckboxType::class, array('required' => false))
            ->add('reminder', CheckboxType::class, array('required' => false));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'  => 'calendar_event',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event';
    }
}
