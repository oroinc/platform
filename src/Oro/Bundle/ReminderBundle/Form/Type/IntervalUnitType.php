<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ReminderBundle\Model\ReminderInterval;

class IntervalUnitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => array(
                    ReminderInterval::UNIT_MINUTE => 'oro.reminder.interval.unit.minute.label',
                    ReminderInterval::UNIT_HOUR   => 'oro.reminder.interval.unit.hour.label',
                    ReminderInterval::UNIT_DAY    => 'oro.reminder.interval.unit.day.label',
                    ReminderInterval::UNIT_WEEK   => 'oro.reminder.interval.unit.week.label',
                ),
                'expanded' => false,
                'multiple' => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder_interval_unit';
    }
}
