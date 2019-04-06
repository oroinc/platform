<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * The formatter for date time values.
 */
class DateTimeValueFormatter implements FormatterInterface
{
    /** @var DateTimeFormatter */
    private $formatter;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param DateTimeFormatter   $dateTimeFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(DateTimeFormatter $dateTimeFormatter, TranslatorInterface $translator)
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
