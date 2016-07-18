<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\ChildEventsSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;

class CalendarEventApiType extends CalendarEventType
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param CalendarEventManager $calendarEventManager
     * @param ManagerRegistry      $registry
     * @param SecurityFacade       $securityFacade
     * @param RequestStack         $requestStack
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(
        CalendarEventManager $calendarEventManager,
        ManagerRegistry $registry,
        SecurityFacade $securityFacade,
        RequestStack $requestStack,
        AttendeeRelationManager $attendeeRelationManager
    ) {
        parent::__construct($registry, $securityFacade);
        $this->calendarEventManager = $calendarEventManager;
        $this->requestStack         = $requestStack;
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden', ['mapped' => false])
            ->add(
                'calendar',
                'integer',
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add(
                'calendarAlias',
                'text',
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add('title', 'text', ['required' => true])
            ->add('description', 'text', ['required' => false])
            ->add(
                'start',
                'datetime',
                [
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'end',
                'datetime',
                [
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add('allDay', 'checkbox', ['required' => false])
            ->add('backgroundColor', 'text', ['required' => false])
            ->add('reminders', 'oro_reminder_collection', ['required' => false])
            ->add(
                $builder->create(
                    'attendees',
                    'oro_collection',
                    [
                        'property_path' => 'attendees',
                        'type' => 'oro_calendar_event_attendees_api',
                        'error_bubbling' => false,
                        'options' => [
                            'required' => false,
                            'label'    => 'oro.calendar.calendarevent.attendees.label',
                        ],
                    ]
                )
                ->addEventSubscriber(new AttendeesSubscriber($this->attendeeRelationManager))
            )
            ->add('notifyInvitedUsers', 'hidden', ['mapped' => false])
            ->add(
                'createdAt',
                'datetime',
                [
                    'required'       => false,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'recurrence',
                'oro_calendar_event_recurrence',
                [
                    'required' => false,
                ]
            )
            ->add(
                'recurringEventId',
                'oro_entity_identifier',
                [
                    'required'      => false,
                    'property_path' => 'recurringEvent',
                    'class'         => 'OroCalendarBundle:CalendarEvent',
                    'multiple'      => false,
                ]
            )
            ->add(
                'originalStart',
                'datetime',
                [
                    'required'       => false,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'isCancelled',
                'checkbox',
                [
                    'required' => false,
                    'property_path' => 'cancelled',
                ]
            );

        /** @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees' */
        $builder->add(
            'invitedUsers',
            'oro_user_multiselect',
            [
                'required' => false,
                'mapped'   => false,
            ]
        );

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventSubscriber(new CalendarEventApiTypeSubscriber(
            $this->calendarEventManager,
            $this->requestStack
        ));
        $builder->addEventSubscriber(new ChildEventsSubscriber(
            $this->registry,
            $this->securityFacade
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'            => 'calendar_event',
                'csrf_protection'      => false,
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ]
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
