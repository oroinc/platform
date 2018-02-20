<?php

namespace Oro\Bundle\CurrencyBundle\Formatter;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter as BaseFormatter;

class MoneyValueTypeFormatter implements TypeFormatterInterface
{
    protected $formatter;
    protected $roundingService;

    /**
     * MoneyValueProperty constructor.
     *
     * @param BaseFormatter         $formatter
     * @param AbstractRoundingService $roundingService
     */
    public function __construct(BaseFormatter $formatter, AbstractRoundingService $roundingService)
    {
        $this->formatter = $formatter;
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
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
