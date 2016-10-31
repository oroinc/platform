<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Price implements CurrencyAwareInterface
{
    use CurrencyAwareTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @param string $value
     * @param string $currency
     * @return Price
     */
    public static function create($value, $currency)
    {
        /* @var $price self */
        $price = new static();
        $price->setValue($value)
            ->setCurrency($currency);

        return $price;
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
}
