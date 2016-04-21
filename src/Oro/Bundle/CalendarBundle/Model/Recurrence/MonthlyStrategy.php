<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class MonthlyStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_MONTHLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        return 'monthly';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_monthly';
    }
}
