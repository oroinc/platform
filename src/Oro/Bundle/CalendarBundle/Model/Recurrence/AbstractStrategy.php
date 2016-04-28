<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractStrategy
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * AbstractStrategy constructor.
     *
     * @param TranslatorInterface $translator
     * @param DateTimeFormatter $formatter
     */
    public function __construct(TranslatorInterface $translator, DateTimeFormatter $formatter)
    {
        $this->translator = $translator;
        $this->dateTimeFormatter = $formatter;
    }

    /**
     * Returns occurrences text pattern, if it is applicable for recurrence.
     *
     * @param Recurrence $recurrence
     *
     * @return string
     */
    public function getOccurrencesPattern(Recurrence $recurrence)
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
     * @param Recurrence $recurrence
     *
     * @return string
     */
    protected function getEndDatePattern(Recurrence $recurrence)
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
     * @param Recurrence $recurrence
     * @param string $translationId
     * @param integer $count
     * @param array $translationParameters
     *
     * @return string
     */
    protected function getFullRecurrencePattern(Recurrence $recurrence, $translationId, $count, $translationParameters)
    {
        $result = $this->translator->transChoice(
            $translationId,
            $count,
            $translationParameters
        );

        $result .= $this->getOccurrencesPattern($recurrence);
        $result .= $this->getEndDatePattern($recurrence);

        return $result;
    }
}
