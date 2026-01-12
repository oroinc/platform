<?php

namespace Oro\Bundle\ReminderBundle\Form\Type\ReminderInterval;

use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting reminder interval units.
 *
 * This form type provides a choice field for selecting the unit of time for a reminder
 * interval (minute, hour, day, or week). It extends the Symfony {@see ChoiceType} and is
 * typically used as part of the {@see ReminderIntervalType} to allow users to specify how
 * often a reminder should be triggered.
 */
class UnitType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'  => [
                    'oro.reminder.interval.unit.minute.label' => ReminderInterval::UNIT_MINUTE,
                    'oro.reminder.interval.unit.hour.label' => ReminderInterval::UNIT_HOUR,
                    'oro.reminder.interval.unit.day.label' => ReminderInterval::UNIT_DAY,
                    'oro.reminder.interval.unit.week.label' => ReminderInterval::UNIT_WEEK,
                ],
                'expanded' => false,
                'multiple' => false,
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_reminder_interval_unit';
    }
}
