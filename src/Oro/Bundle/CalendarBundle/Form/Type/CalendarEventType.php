<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'textarea', ['required' => true, 'label' => 'oro.calendar.calendarevent.title.label'])
            ->add('start', 'oro_datetime', ['required' => true, 'label' => 'oro.calendar.calendarevent.start.label'])
            ->add('end', 'oro_datetime', ['required' => true, 'label' => 'oro.calendar.calendarevent.end.label'])
            ->add('allDay', 'checkbox', ['required' => false, 'label' => 'oro.calendar.calendarevent.all_day.label']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'  => 'calendar_event',
            ]
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
