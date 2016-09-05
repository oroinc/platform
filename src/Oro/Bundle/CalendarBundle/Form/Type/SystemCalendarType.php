<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarType extends AbstractType
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /**
     * @param SecurityFacade       $securityFacade
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(SecurityFacade $securityFacade, SystemCalendarConfig $calendarConfig)
    {
        $this->securityFacade = $securityFacade;
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.systemcalendar.name.label'
                ]
            )
            ->add(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.systemcalendar.background_color.label',
                    'color_schema'       => 'oro_calendar.calendar_colors',
                    'empty_value'        => 'oro.calendar.systemcalendar.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
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
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                'intention'  => 'system_calendar',
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
        $form = $event->getForm();

        if ($this->calendarConfig->isPublicCalendarEnabled() && $this->calendarConfig->isSystemCalendarEnabled()) {
            $options = [
                'required'    => false,
                'label'       => 'oro.calendar.systemcalendar.public.label',
                'empty_value' => false,
                'choices'     => [
                    false => 'oro.calendar.systemcalendar.scope.organization',
                    true  => 'oro.calendar.systemcalendar.scope.system'
                ]
            ];
            /** @var SystemCalendar|null $data */
            $data = $event->getData();
            if ($data) {
                $isPublicGranted = $this->securityFacade->isGranted('oro_public_calendar_management');
                $isSystemGranted = $this->securityFacade->isGranted(
                    $data->getId() ? 'oro_system_calendar_update' : 'oro_system_calendar_create'
                );
                if (!$isPublicGranted || !$isSystemGranted) {
                    $options['read_only'] = true;
                    if (!$data->getId() && !$isSystemGranted) {
                        $options['data'] = true;
                    }
                    unset($options['choices'][$isSystemGranted]);
                }
            }
            $form->add('public', 'choice', $options);
        } elseif ($this->calendarConfig->isPublicCalendarEnabled()) {
            $form->add('public', 'hidden', ['data' => true]);
        } elseif ($this->calendarConfig->isSystemCalendarEnabled()) {
            $form->add('public', 'hidden', ['data' => false]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_system_calendar';
    }
}
