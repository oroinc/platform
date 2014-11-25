<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            ->add(
                'title',
                'text',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.title.label'
                ]
            )
            ->add(
                'description',
                'textarea',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.description.label'
                ]
            )
            ->add(
                'start',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.start.label'
                ]
            )
            ->add(
                'end',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label'
                ]
            )
            ->add(
                'allDay',
                'checkbox',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.all_day.label'
                ]
            )
            ->add(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.backgroundColor.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.form.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->add(
                'reminders',
                'oro_reminder_collection',
                [
                    'required' => false,
                    'label'    => 'oro.reminder.entity_plural_label'
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_change_calendar' => false,
                'layout_template'       => false,
                'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'             => 'calendar_event'
            ]
        );
    }

    /**
     * PRE_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form   = $event->getForm();
        $config = $form->getConfig();

        if (!$config->getOption('allow_change_calendar')) {
            return;
        }

        if ($config->getOption('layout_template')) {
            $form->add(
                'calendarUid',
                'oro_calendar_choice_template',
                [
                    'required' => false,
                    'mapped'   => false,
                    'label'    => 'oro.calendar.calendarevent.calendar.label'
                ]
            );
        } else {
            /** @var CalendarEvent $data */
            $data = $event->getData();
            $form->add(
                $form->getConfig()->getFormFactory()->createNamed(
                    'calendarUid',
                    'oro_calendar_choice',
                    $this->getCalendarUid($data),
                    [
                        'required'        => false,
                        'mapped'          => false,
                        'auto_initialize' => false,
                        'is_new'          => !$data || !$data->getId(),
                        'label'           => 'oro.calendar.calendarevent.calendar.label'
                    ]
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event';
    }

    /**
     * @param CalendarEvent $data
     *
     * @return string
     */
    protected function getCalendarUid(CalendarEvent $data = null)
    {
        if ($data) {
            if ($data->getCalendar()) {
                return sprintf('%s_%d', Calendar::CALENDAR_ALIAS, $data->getCalendar()->getId());
            }
            if ($data->getSystemCalendar()) {
                $alias = $data->getSystemCalendar()->isPublic()
                    ? SystemCalendar::PUBLIC_CALENDAR_ALIAS
                    : SystemCalendar::CALENDAR_ALIAS;

                return sprintf('%s_%d', $alias, $data->getSystemCalendar()->getId());
            }
        }

        return null;
    }
}
