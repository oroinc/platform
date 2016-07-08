<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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

    /** @var array */
    protected $editableFieldsForRecurrence = [
        'title',
        'description',
        'contexts',
    ];

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
                'oro_calendar_event_attendees_select',
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
        $builder->addEventSubscriber(new ChildEventsSubscriber($this->registry, $this->securityFacade));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->getNormData() && $form->getNormData()->getRecurrence()) {
                foreach ($form->all() as $child) {
                    if (in_array($child->getName(), $this->editableFieldsForRecurrence)) {
                        continue;
                    }
                    if ($form->has($child->getName())) {
                        $options = $child->getConfig()->getOptions();
                        $options['disabled'] = true;
                        $form->add($child->getName(), $child->getConfig()->getType()->getName(), $options);
                    }
                }
            }
        }, 10);
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
                'intention'             => 'calendar_event',
                'csrf_protection'       => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getData() && $form->getData()->getRecurrence()) {
            /** @var FormView $childView */
            foreach ($view->children as $childView) {
                if (in_array($childView->vars['name'], $this->editableFieldsForRecurrence)) {
                    continue;
                }
                $childView->vars['disabled'] = true;
                if (in_array($childView->vars['name'], ['start', 'end'])) {
                    $childView->vars['attr']['data-required'] = false;
                }
                if ($childView->vars['name'] === 'reminders') {
                    $childView->vars['allow_add'] = false;
                    $childView->vars['allow_delete'] = false;
                }
            }
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
