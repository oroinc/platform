<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class CalendarEventApiType extends CalendarEventType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden', array('mapped' => false))
            ->add(
                'calendar',
                'oro_entity_identifier',
                array(
                    'label'    => 'oro.calendar.entity_name',
                    'required' => true,
                    'class'    => 'OroCalendarBundle:Calendar',
                    'multiple' => false
                )
            )
            ->add(
                'title',
                'text',
                array(
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.title.label'
                )
            )
            ->add(
                'start',
                'datetime',
                array(
                    'label'          => 'oro.calendar.calendarevent.start.label',
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                )
            )
            ->add(
                'end',
                'datetime',
                array(
                    'label'          => 'oro.calendar.calendarevent.end.label',
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                )
            )
            ->add(
                'allDay',
                'checkbox',
                array(
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.all_day.label'
                )
            )
            ->add(
                'reminders',
                'oro_reminder_collection',
                [
                    'required' => false,
                    'label'    => 'oro.reminder.entity_plural_label'
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'      => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'       => 'calendar_event',
                'csrf_protection' => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event_api';
    }
}
