<?php

namespace Oro\Bundle\ReminderBundle\Form\Type\ReminderInterval;

use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
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
        return 'oro_reminder_interval_unit';
    }
}
