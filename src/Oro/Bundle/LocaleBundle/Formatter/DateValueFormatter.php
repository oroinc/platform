<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class DateValueFormatter implements FormatterInterface
{
    /** @var DateTimeFormatter */
    protected $formatter;

    /** @var TranslatorInterface */
    protected $translator;

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
    public function getFormatterName()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        return $this->formatter->formatDate($parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes()
    {
        return ['date'];
    }

    /**
     * {@inheritdoc}
     */
    public function isDefaultFormatter()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.locale.formatter.datetime.default');
    }
}
