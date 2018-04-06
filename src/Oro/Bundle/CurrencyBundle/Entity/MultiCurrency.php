<?php
namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\FormBundle\Entity\EmptyItem;

class MultiCurrency implements EmptyItem
{
    use CurrencyAwareTrait;

    protected $value;
    protected $baseCurrencyValue = null;

    /**
     * @param string      $value
     * @param string      $currency
     * @param string|null $baseCurrencyValue
     *
     * @return MultiCurrency
     */
    public static function create($value, $currency, $baseCurrencyValue = null)
    {
        /* @var $multiCurrency MultiCurrency */
        $multiCurrency = new static();
        $multiCurrency->setValue($value)
            ->setCurrency($currency)
            ->setBaseCurrencyValue($baseCurrencyValue);

        return $multiCurrency;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBaseCurrencyValue()
    {
        return $this->baseCurrencyValue;
    }

    /**
     * @param $baseCurrencyValue
     *
     * @return $this
     */
    public function setBaseCurrencyValue($baseCurrencyValue)
    {
        $this->baseCurrencyValue = $baseCurrencyValue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->value === null;
    }
}
