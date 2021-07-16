<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

/**
 * The formatter for currency values.
 */
class CurrencyFormatter implements FormatterInterface
{
    /** @var NumberFormatter */
    private $formatter;

    public function __construct(NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = [])
    {
        return $this->formatter->formatCurrency(
            $value,
            $formatterArguments['currency'] ?? null,
            (array)($formatterArguments['attributes'] ?? []),
            (array)($formatterArguments['textAttributes'] ?? []),
            (array)($formatterArguments['symbols'] ?? []),
            $formatterArguments['locale'] ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return 0;
    }
}
