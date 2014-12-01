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
            )
        ;

        $this->subscribeOnChildEvents($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function subscribeOnChildEvents(FormBuilderInterface $builder)
    {
        // extract master event
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        // get existing events
        $builder->get('childEvents')->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onChildPostSubmit']);

        // synchronize child events
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();
        if ($data) {
            $this->parentEvent = $data;
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onChildPostSubmit(FormEvent $event)
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
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var CalendarEvent $parentEvent */
        $parentEvent = $event->getForm()->getData();
        if ($parentEvent) {
            $this->checkEventStatus($parentEvent);

            foreach ($parentEvent->getChildEvents() as $calendarEvent) {
                $calendarEvent->setTitle($parentEvent->getTitle())
                    ->setDescription($parentEvent->getDescription())
                    ->setStart($parentEvent->getStart())
                    ->setEnd($parentEvent->getEnd())
                    ->setAllDay($parentEvent->getAllDay());

                $this->checkEventStatus($calendarEvent);
            }
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     */
    protected function checkEventStatus(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getInvitationStatus()) {
            $calendarEvent->setInvitationStatus(CalendarEvent::NOT_RESPONDED);
        }
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
