<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The formatter for date values.
 */
class DateValueFormatter implements FormatterInterface
{
    /** @var DateTimeFormatter */
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
        return $this->formatter->formatDate($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.locale.formatter.datetime.default');
    }
}
