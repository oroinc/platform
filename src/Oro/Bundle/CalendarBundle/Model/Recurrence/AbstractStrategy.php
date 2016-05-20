<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

abstract class AbstractStrategy implements StrategyInterface
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
        if ($recurrence->getEndTime() !== null) {
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
     * {@inheritdoc}
     */
    public function getCalculatedEndTime(Entity\Recurrence $recurrence)
    {
        $occurrences = $recurrence->getOccurrences();
        $currentEndTime = $recurrence->getEndTime();

        if (!empty($currentEndTime)) {
            $result = $currentEndTime;
        } elseif (!empty($occurrences)) {
            $result = $this->getLastOccurrence($recurrence);
        } else {
            $result = new \DateTime(Recurrence::MAX_END_DATE);
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
