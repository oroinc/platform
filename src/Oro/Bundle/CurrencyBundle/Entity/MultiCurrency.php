<?php
namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

class MultiCurrency
{
    use CurrencyAwareTrait;

    protected $value;
    protected $rate;

    /**
     * @param string $value
     * @param string $currency
     * @param float $rate | null
     * @return MultiCurrency
     */
    public static function create($value, $currency, $rate = null)
    {
        /* @var $multiCurrency self */
        $multiCurrency = new static();
        $multiCurrency->setValue($value)
            ->setCurrency($currency)
            ->setRate($rate);

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
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     * @return $this
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }
}
