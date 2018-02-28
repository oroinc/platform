<?php

namespace Oro\Bundle\CurrencyBundle\Formatter\Property;

use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Formatter\MoneyValueTypeFormatter;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;
use Psr\Log\LoggerInterface;

class MoneyValueProperty extends AbstractProperty
{
    protected $formatter;
    protected $logger;

    /**
     * MoneyValueProperty constructor.
     *
     * @param MoneyValueTypeFormatter         $formatter
     */
    public function __construct(MoneyValueTypeFormatter $formatter, LoggerInterface $logger)
    {
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws InvalidRoundingTypeException
     */
    protected function format($value)
    {
        return $this->formatter->format($value);
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return float
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        try {
            $value = $record->getValue($this->get(self::NAME_KEY));
        } catch (\LogicException $e) {
            // default value
            $value = null;
            $this->logger->error(
                'Can\'t get value by name key.',
                ['exception'=> $e]
            );
        }

        return $value;
    }
}
