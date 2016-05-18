<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

abstract class AbstractStrategy
{
    /** @var Recurrence */
    protected $model;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * @param Recurrence $model
     * @param TranslatorInterface $translator
     * @param DateTimeFormatter $formatter
     */
    public function __construct(
        Recurrence $model,
        TranslatorInterface $translator,
        DateTimeFormatter $formatter
    ) {
        $this->model = $model;
        $this->translator = $translator;
        $this->dateTimeFormatter = $formatter;
    }

    /**
     * Returns occurrences text pattern, if it is applicable for recurrence.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getOccurrencesPattern(Entity\Recurrence $recurrence)
    {
        $occurrences = $recurrence->getOccurrences();
        $result = '';
        if ($occurrences > 0) {
            $result = $this->translator->transChoice(
                'oro.calendar.recurrence.patterns.occurrences',
                $occurrences,
                ['%count%' => $occurrences]
            );
        }

        return $result;
    }

    /**
     * Returns end date text pattern, if it is applicable for recurrence.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getEndDatePattern(Entity\Recurrence $recurrence)
    {
        $result = '';
        $maxEndDate = new \DateTime(Recurrence::MAX_END_DATE);
        if ($recurrence->getOccurrences() === null && $recurrence->getEndTime() < $maxEndDate) {
            $result = $this->translator->trans(
                'oro.calendar.recurrence.patterns.end_date',
                ['%date%' => $this->dateTimeFormatter->formatDate($recurrence->getEndTime())]
            );
        }

        return $result;
    }

    /**
     * Returns recurrence pattern text according to its translation and parameters.
     *
     * @param Entity\Recurrence $recurrence
     * @param string $translationId
     * @param integer $count
     * @param array $translationParameters
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getFullRecurrencePattern(
        Entity\Recurrence $recurrence,
        $translationId,
        $count,
        $translationParameters
    ) {
        $result = $this->translator->transChoice(
            $translationId,
            $count,
            $translationParameters
        );

        $result .= $this->getOccurrencesPattern($recurrence);
        $result .= $this->getEndDatePattern($recurrence);

        return $result;
    }

    /**
     * This method implements @see \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface::getCalculatedEndTime()
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime|null
     */
    public function getCalculatedEndTime(Entity\Recurrence $recurrence)
    {
        $occurrences = $recurrence->getOccurrences();
        $currentEndTime = $recurrence->getEndTime();

        if (empty($occurrences)) {
            $result = empty($currentEndTime) ? new \DateTime(Recurrence::MAX_END_DATE) : $currentEndTime;
        } else {
            $result = $this->getLastOccurrence($recurrence);
        }

        return $result;
    }

    /**
     * Returns last occurrence date according occurrences value.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    abstract public function getLastOccurrence(Entity\Recurrence $recurrence);
}
