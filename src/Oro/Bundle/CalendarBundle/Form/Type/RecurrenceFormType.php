<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurrenceFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'recurrenceType',
                'choice',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'empty_value' => false,
                    'choices' => Recurrence::getRecurrenceTypes(),
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
                    'choices' => Recurrence::getInstances(),
                ]
            )
            ->add(
                'dayOfWeek',
                'choice',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    'choices' => Recurrence::getDaysOfWeek(),
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
                'occurrences',
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
                'intention' => 'oro_calendar_event_recurrence',
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Recurrence',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event_recurrence';
    }
}
