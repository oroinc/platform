<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventType extends AbstractType
{
    /**
     * @var CalendarEvent
     */
    protected $parentEvent;

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
                'oro_resizeable_rich_text',
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
                    'label'    => 'oro.calendar.calendarevent.start.label',
                    'attr'     => ['class' => 'start'],
                ]
            )
            ->add(
                'end',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label',
                    'attr'     => ['class' => 'end'],
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
                    'label'              => 'oro.calendar.calendarevent.background_color.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.calendarevent.no_color',
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $this->subscribeOnChildEvents($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $childEventsFieldName
     */
    protected function subscribeOnChildEvents(FormBuilderInterface $builder, $childEventsFieldName = 'childEvents')
    {
        // extract master event
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);

        // get existing events
        $builder->get($childEventsFieldName)
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitChildEvents']);

        // synchronize child events
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * PRE_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();
        if ($data) {
            $this->parentEvent = $data;
        }
    }

    /**
     * POST_SUBMIT event handler for 'childEvents' child field
     *
     * @param FormEvent $event
     */
    public function postSubmitChildEvents(FormEvent $event)
    {
        /** @var CalendarEvent[] $data */
        $data = $event->getForm()->getData();
        if ($data && $this->parentEvent) {
            foreach ($data as $key => $calendarEvent) {
                $existingEvent = $this->parentEvent->getChildEventByCalendar($calendarEvent->getCalendar());
                if ($existingEvent) {
                    $data[$key] = $existingEvent;
                }
            }
        }
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var CalendarEvent $parentEvent */
        $parentEvent = $event->getForm()->getData();
        if ($parentEvent && !$parentEvent->getChildEvents()->isEmpty()) {
            $this->setDefaultEventStatus($parentEvent, CalendarEvent::ACCEPTED);

            foreach ($parentEvent->getChildEvents() as $calendarEvent) {
                $calendarEvent
                    ->setTitle($parentEvent->getTitle())
                    ->setDescription($parentEvent->getDescription())
                    ->setStart($parentEvent->getStart())
                    ->setEnd($parentEvent->getEnd())
                    ->setAllDay($parentEvent->getAllDay());

                $this->setDefaultEventStatus($calendarEvent);
            }
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param string        $status
     */
    protected function setDefaultEventStatus(CalendarEvent $calendarEvent, $status = CalendarEvent::NOT_RESPONDED)
    {
        if (!$calendarEvent->getInvitationStatus()) {
            $calendarEvent->setInvitationStatus($status);
        }
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
                    $data ? $data->getCalendarUid() : null,
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
}
