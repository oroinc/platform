<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class SystemCalendarType extends AbstractType
{
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
                    'label'              => 'oro.calendar.systemcalendar.backgroundColor.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.form.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->add(
                'public',
                'choice',
                [
                    'required' => false,
                    'choices'  => [
                        true  => 'oro.calendar.systemcalendar.scope.organization.label',
                        false => 'oro.calendar.systemcalendar.scope.system.label',
                    ]
                ]
            );
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_system_calendar';
    }
}
