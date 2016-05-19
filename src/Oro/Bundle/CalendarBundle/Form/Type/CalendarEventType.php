<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarUidSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\ChildEventsSubscriber;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ManagerRegistry $registry
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ManagerRegistry $registry, SecurityFacade $securityFacade)
    {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
    }

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
                'attendees',
                'oro_calendar_event_attendees',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.attendees.label',
                    'layout_template' => $options['layout_template'],
                ]
            )
            ->add(
                'notifyInvitedUsers',
                'hidden',
                [
                    'mapped' => false
                ]
            );

        $builder->addEventSubscriber(new CalendarUidSubscriber());
        $builder->addEventSubscriber(new ChildEventsSubscriber($builder, $this->registry, $this->securityFacade));
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $calendarEvent = $event->getData();
            if (!$calendarEvent) {
                return;
            }

            $this->setDefaultOrigin($calendarEvent);
        }, 10);
    }

    /**
     * @param CalendarEvent $event
     */
    protected function setDefaultOrigin(CalendarEvent $event)
    {
        if (!$event->getOrigin()) {
            $calendarEventServer = $this->registry
                ->getRepository(ExtendHelper::buildEnumValueClassName(CalendarEvent::ORIGIN_ENUM_CODE))
                ->find(CalendarEvent::ORIGIN_SERVER);

            $event->setOrigin($calendarEventServer);
        }

        $attendeeServer = $this->registry
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::ORIGIN_ENUM_CODE))
            ->find(Attendee::ORIGIN_SERVER);

        $attendees = $event->getAttendees();
        foreach ($attendees as $attendee) {
            if ($attendee->getOrigin()) {
                continue;
            }

            $attendee->setOrigin($attendeeServer);
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event';
    }
}
