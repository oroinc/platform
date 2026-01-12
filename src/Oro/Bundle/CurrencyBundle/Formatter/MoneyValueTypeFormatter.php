<?php

namespace Oro\Bundle\CurrencyBundle\Formatter;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter as BaseFormatter;

/**
 * Formats monetary values with proper rounding and locale-specific number formatting.
 *
 * This formatter is responsible for converting raw monetary values into properly
 * formatted strings suitable for display. It applies rounding based on the configured
 * rounding service and uses locale-aware number formatting to ensure values are
 * presented according to the user's locale settings.
 */
class MoneyValueTypeFormatter implements TypeFormatterInterface
{
    protected $formatter;
    protected $roundingService;

    /**
     * MoneyValueProperty constructor.
     */
    public function __construct(BaseFormatter $formatter, AbstractRoundingService $roundingService)
    {
        $this->formatter = $formatter;
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function formatType($value, $type)
    {
        if (MoneyValueType::TYPE !== $type) {
            return $value;
        }

        return $this->format($value);
    }

    /**
     * @param $value
     *
     * @return string
     *
     * @throws InvalidRoundingTypeException
     */
    public function format($value)
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        $roundedValue = $this->roundingService->round($value);
        return $this->formatter->formatDecimal(
            $roundedValue,
            [ 'fraction_digits' => $this->roundingService->getPrecision() ]
        );
    }
}
