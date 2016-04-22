<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class RecurrenceFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // @TODO implement recurrencePattern validator.
        $builder
            ->add(
                'recurrenceType',
                'choice',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'empty_value' => false,
                    'choices' => [
                        Recurrence::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
                        Recurrence::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
                        Recurrence::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
                        Recurrence::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
                        Recurrence::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
                        Recurrence::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
                    ],
                ]
            )
            ->add(
                'interval',
                'integer',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.interval.label',
                ]
            )
            ->add(
                'instance',
                'choice',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.instance.label',
                    'empty_value' => false,
                    'choices' => [
                        Recurrence::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
                        Recurrence::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
                        Recurrence::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
                        Recurrence::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
                        Recurrence::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
                    ],
                ]
            )
            ->add(
                'dayOfWeek',
                'choice',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    'choices' => [
                        Recurrence::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
                        Recurrence::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
                        Recurrence::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
                        Recurrence::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
                        Recurrence::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
                        Recurrence::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
                        Recurrence::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
                    ],
                ]
            )
            ->add(
                'dayOfMonth',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_month.label',
                ]
            )
            ->add(
                'monthOfYear',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.month_of_year.label',
                ]
            )
            ->add(
                'startTime',
                'datetime',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.start_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->add(
                'endTime',
                'datetime',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.end_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->add(
                // @TODO fix typo 'occurences' => 'occurrences' after it will be fixed in plugin.
                'occurences',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.occurrences.label',
                    'property_path' => 'occurrences',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'intention' => 'oro_calendar_recurrence',
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Recurrence',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_recurrence';
    }
}
