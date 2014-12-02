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
                    'label'    => 'oro.calendar.entity_label',
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
                'description',
                'text',
                array(
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.description.label'
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
                'backgroundColor',
                'text',
                array(
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.backgroundColor.label'
                )
            )
            ->add(
                'reminders',
                'oro_reminder_collection',
                [
                    'required' => false,
                    'label'    => 'oro.reminder.entity_plural_label'
                ]
            )
            ->add(
                'childEvents',
                'oro_calendar_event_invitees',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.invitation.label'
                ]
            )
            ->add(
                'notifyInvitedUsers',
                'hidden',
                [
                    'mapped' => false
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        $this->subscribeOnChildEvents($builder);
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
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
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
