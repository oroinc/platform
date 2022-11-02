<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The formatter for date time values.
 */
class DateTimeValueFormatter implements FormatterInterface
{
    /** @var DateTimeFormatterInterface */
    private $formatter;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(DateTimeFormatterInterface $dateTimeFormatter, TranslatorInterface $translator)
    {
        $this->formatter = $dateTimeFormatter;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = [])
    {
        return $this->formatter->format($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.locale.formatter.datetime.default');
    }
}
